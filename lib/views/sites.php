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

$serverpilot_server_id = settings( 'serverpilot_server_id' );
$db_sites = managed_sites();
$db_spare_sites = spare_sites();

$db_sites_indexed = array_combine( array_column( $db_sites, 'domain' ), $db_sites );
$db_spare_sites_indexed = array_combine( array_column( $db_spare_sites, 'domain' ), $db_spare_sites );

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
	printf( esc_html__( 'There are %s launched sites right now.', 'jurassic-ninja' ), count( $db_sites ) );
	?>
<table class="fixed widefat striped">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Local Id', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'System user', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Is shortlived site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Created on', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Checked in on', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Launched by', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Last logged in on', 'jurassic-ninja' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
$unmanaged = array();
foreach ( $serverpilot_apps as $site ) {
	$domain = figure_out_main_domain( $site->domains );
	$in_logs = array_key_exists( $domain, $db_sites_indexed );
	if ( array_key_exists( $domain, $db_spare_sites_indexed ) ) {
		continue;
	}
	if ( ! $in_logs ) {
		$unmanaged[] = $site;
		continue;
	}
	$db_id = $db_sites_indexed[ $domain ]['id'];
	$created = $db_sites_indexed[ $domain ]['created'];
	$sysusername = $db_sites_indexed[ $domain ]['username'];
	$last_logged_in = $db_sites_indexed[ $domain ]['last_logged_in'];
	$checked_in = $db_sites_indexed[ $domain ]['checked_in'];
	$launched_by = $db_sites_indexed[ $domain ]['launched_by'];
	$is_shortlived_site = $db_sites_indexed[ $domain ]['shortlived'];
	?>
	<tr class="active">
		<td class="column-columnname"><?php echo esc_html( $db_id ); ?></td>
		<td class="column-columnname">
			<a target="_blank" href="<?php echo 'http://' . esc_attr( $domain ); ?>" rel="noopener nofollow"<strong><?php echo esc_html( $domain ); ?></strong></a>
		</td>
		<td class="column-columnname"><a rel="noreferrer noopener" target="_blank" href="<?php echo esc_attr( "https://manage.serverpilot.io/servers/$serverpilot_server_id/users/$site->sysuserid" ); ?>"><?php echo esc_html( $sysusername ); ?></a></td>
		<td class="column-columnname"><?php echo $is_shortlived_site ? esc_html__( 'Yes', 'jurassic-ninja' ) : esc_html__( 'No', 'jurassic-ninja' ); ?></td>
		<td class="column-columnname"><?php echo esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $created ) ) ); ?></td>
		<td class="column-columnname"><?php echo $checked_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $checked_in ) ) ) : ''; ?></td>
		<td class="column-columnname"><?php echo $launched_by ? esc_html( $launched_by ) : 'anonymous'; ?></td>
		<td class="column-columnname"><?php echo $last_logged_in ? esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $last_logged_in ) ) ) : ''; ?></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>
<h3>Spare sites</h3>
<p>
<?php
/* translators: Number is integer and can be zero */
printf( esc_html__( 'There are %s spare sites right now.', 'jurassic-ninja' ), count( $db_spare_sites ) );
?>
</p>
<table class="fixed widefat striped">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Local Id', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'App Id', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'System user', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Created on', 'jurassic-ninja' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ( $db_spare_sites as $site ) {
	?>
	<tr class="active">
		<td class="column-columnname"><?php echo esc_html( $site['id'] ); ?></td>
		<td class="column-columnname"><a rel="noreferrer noopener" target="_blank" href="<?php echo esc_attr( "https://manage.serverpilot.io/apps/$site[app_id]/settings" ); ?>"><?php echo esc_html( $site['app_id'] ); ?></a></td>
		<td class="column-columnname">
			<?php echo esc_html( $site['domain'] ); ?>
		</td>
		<td class="column-columnname"><?php echo esc_html( $site['username'] ); ?></td>
		<td class="column-columnname"><?php echo esc_html( mysql2date( 'l, F j - g:i a', get_date_from_gmt( $site['created'] ) ) ); ?></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>

<h3>Unamanaged sites</h3>
<p>
<?php
/* translators: Number is integer and can be zero */
printf( esc_html__( 'There are %s ServerPilot apps not managed by this site.', 'jurassic-ninja' ), count( $unmanaged ) );
?>
</p>
<table class="fixed widefat striped">
	<thead>
		<tr>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'App Id', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'Site', 'jurassic-ninja' ); ?> </th>
			<th class="manage-column column-columnname"><?php echo esc_html__( 'System user', 'jurassic-ninja' ); ?> </th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ( $unmanaged as $app ) {
	?>
	<tr class="active">
		<td class="column-columnname"><?php echo esc_html( $app->id ); ?></td>
		<td class="column-columnname">
			<?php echo esc_html( figure_out_main_domain( $app->domains ) ); ?>
		</td>
		<td class="column-columnname"><a rel="noreferrer noopener" target="_blank" href="<?php echo esc_attr( "https://manage.serverpilot.io/servers/$serverpilot_server_id/users/$app->sysuserid" ); ?>"><?php echo esc_html( $app->sysuserid ); ?></a></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>
