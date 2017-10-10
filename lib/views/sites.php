<?php

namespace jn;

require_once __DIR__ . '/../db-stuff.php';

$sites = db()->get_results( 'select * from sites', \ARRAY_A );

$just_site_domains = array_map( function ( $site ) {
	return $site['domain'];
}, $sites );

$sp = new \ServerPilot( config( 'serverpilot' ) );
$sites_from_serverpilot = array_filter( $sp->app_list()->data, function ( $site ) {
	return 'jurassic.ninja' !== $site->name;
} );

?>
<table class="fixed widefat">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html( 'Site' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html( 'System user' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html( 'Exists in logs' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ( $sites_from_serverpilot as $site ) {
	$in_logs = in_array( $site->domains[0], $just_site_domains, true );
	?>
	<tr class="active">
		<td class="column-columnname">
			<a target="_blank" href="<?php echo 'http://' . esc_attr( $site->domains[0] ); ?>" rel="noopener nofollow"<strong><?php echo esc_html( $site->domains[0] ); ?></strong></a>
		</td>
		<td class="column-columnname"><?php echo esc_html( $site->name ); ?></td>
		<td class="column-columnname"><?php echo esc_html( $in_logs ? __( 'Yes' ) : _( 'No' ) ) ; ?></td>
	</tr>
	<?php
}
	?>
	</tbody>
</table>
