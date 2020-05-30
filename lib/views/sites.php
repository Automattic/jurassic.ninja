<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../db-stuff.php';

if ( settings_problems() ) {
	?>
	<p>
		<?php
			echo esc_html__( 'This section is disabled until you fix settings problems shown above in the notice.', 'jurassic-ninja' );
		?>
	</p>
	<?php
	exit( 1 );
}
$db_sites = managed_sites();

$just_site_domains = array_column( $db_sites, 'domain' );

$db_sites_indexed = array_combine( $just_site_domains, $db_sites );

/**
 * Filters the array of apps returned by ServerPilot's API
 *
 * @param array $apps Apps returned by ServerPilot's API
 */
$serverpilot_apps = apply_filters( 'jurassic_ninja_serverpilot_apps_list', provisioner()->get_app_list() );

?>
<p>
	<?php
	/* translators: Number is integer and can be zero */
	printf( esc_html__( 'There are %s launched instances right now.', 'jurassic-ninja' ), count( $serverpilot_apps ) );
	?>
<table class="fixed widefat striped">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Local Id', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'System user', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Managed by this Jurassic Ninja instance', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Is shortlived site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Created on', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Checked in on', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Last logged in on', 'jurassic-ninja' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ( $serverpilot_apps as $site ) {
	$domain = figure_out_main_domain( $site->domains );
	$in_logs = array_key_exists( $domain, $db_sites_indexed );
	$db_id = $in_logs ? $db_sites_indexed[ $domain ]['id'] : '';
	$created = $in_logs ? $db_sites_indexed[ $domain ]['created'] : '';
	$sysusername = $in_logs ? $db_sites_indexed[ $domain ]['username'] : '';
	$last_logged_in = $in_logs ? $db_sites_indexed[ $domain ]['last_logged_in'] : '';
	$checked_in = $in_logs ? $db_sites_indexed[ $domain ]['checked_in'] : '';
	$is_shortlived_site = $in_logs ? $db_sites_indexed[ $domain ]['shortlived'] : false;
	?>
	<tr class="active">
		<td class="column-columnname"><?php echo esc_html( $db_id ); ?></td>
		<td class="column-columnname">
			<a target="_blank" href="<?php echo 'http://' . esc_attr( $domain ); ?>" rel="noopener nofollow"<strong><?php echo esc_html( $domain ); ?></strong></a>
		</td>
		<td class="column-columnname"><a rel="noreferrer noopener" target="_blank" href="<?php echo esc_attr( "https://manage.serverpilot.io/#sysusers/$sysusername" ); ?>"><?php esc_html( $sysusername ); ?></a></td>
		<td class="column-columnname"><?php echo $in_logs ? esc_html__( 'Yes', 'jurassic-ninja' ) : esc_html__( 'No', 'jurassic-ninja' ); ?></td>
		<td class="column-columnname"><?php echo $in_logs && $is_shortlived_site ? esc_html__( 'Yes', 'jurassic-ninja' ) : esc_html__( 'No', 'jurassic-ninja' ); ?></td>
		<td class="column-columnname"><?php echo $in_logs ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $created ) ) ) : ''; ?></td>
		<td class="column-columnname"><?php echo $in_logs && $checked_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $checked_in ) ) ) : ''; ?></td>
		<td class="column-columnname"><?php echo $in_logs && $last_logged_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $last_logged_in ) ) ) : ''; ?></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>
