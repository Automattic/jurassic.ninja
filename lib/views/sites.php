<?php

namespace jn;

global $wpdb;

$sites = $wpdb->get_results( 'select * from sites', \ARRAY_A );
$just_site_domains = array_map( function ( $site ) {
	return $site['domain'];
}, $sites );

$sp = new \ServerPilot( config( 'serverpilot' ) );
$sites_from_serverpilot = array_filter( $sp->app_list()->data, function ( $site ) {
	return 'jurassic.ninja' !== $site->name;
} );

?>
<table>
	<thead>
		<tr>
			<th> Site </th>
			<th> Exists in logs </th>
		</tr>
	</thead>
<?php
foreach ( $sites_from_serverpilot as $site ) {
	?>
	<tr>
		<td><?php echo esc_html( $site->domains[0] ); ?></td>
		<td><?php echo esc_html( in_array( $site->domains[0], $just_site_domains) ) ? 'Yes' : 'No' ; ?></td>
	</tr>
	<?php
}
	?>
</table>
