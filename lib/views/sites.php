<?php

namespace jn;

require_once __DIR__ . '/../db-stuff.php';

$db_sites = db()->get_results( 'select * from sites', \ARRAY_A );

$just_site_domains = array_column( $db_sites, 'domain' );

$db_sites_indexed = array_combine( $just_site_domains, $db_sites );

$server_pilot_apps = array_filter( sp()->app_list()->data, function ( $site ) {
	return 'jurassic.ninja' !== $site->name;
} );

?>
<table class="fixed widefat">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html_e( '#' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'Site' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'System user' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'Exists in logs' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'Created on' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'Checked in on' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html_e( 'Last logged in on' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ( $server_pilot_apps as $site ) {
	$domain = $site->domains[0];
	$in_logs = array_key_exists( $domain, $db_sites_indexed );
	$db_id = $in_logs ? $db_sites_indexed[ $domain ]['id'] : '';
	$created = $in_logs ? $db_sites_indexed[ $domain ]['created'] : '';
	$sysusername = $in_logs ? $db_sites_indexed[ $domain ]['username'] : '';
	$last_logged_in = $in_logs ? $db_sites_indexed[ $domain ]['last_logged_in'] : '';
	$checked_in = $in_logs ? $db_sites_indexed[ $domain ]['checked_in'] : '';
	?>
	<tr class="active">
		<td class="column-columnname"><?php echo esc_html( $db_id ); ?></td>
		<td class="column-columnname">
			<a target="_blank" href="<?php echo 'http://' . esc_attr( $domain ); ?>" rel="noopener nofollow"<strong><?php echo esc_html( $domain ); ?></strong></a>
		</td>
		<td class="column-columnname"><?php echo esc_html( $sysusername ); ?></td>
		<td class="column-columnname"><?php echo $in_logs ? esc_html_e( 'Yes' ) : esc_html_e( 'No' ); ?></td>
		<td class="column-columnname"><?php echo $in_logs ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $created ) ) ) : ''; ?></td>
		<td class="column-columnname"><?php echo $in_logs && $checked_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $checked_in ) ) ) : ''; ?></td>
		<td class="column-columnname"><?php echo $in_logs && $last_logged_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $last_logged_in ) ) ) : ''; ?></td>
	</tr>
	<?php
}
	?>
	</tbody>
</table>
