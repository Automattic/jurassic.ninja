<?php

namespace jn;

$errors = [];

function add_error_notices() {
	add_action( 'admin_notices', 'jn\admin_noticies' );
}

function admin_noticies() {
	$settings_problems = settings_problems();
	if ( ! count( errors() ) && ! count( $settings_problems ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<h4><?php echo esc_html__( 'Jurassic ninja' ); ?></h4>
		<ul>
			<?php
			foreach ( errors() as $error ) {
			?>
				<li>
				<?php echo esc_html( $error->get_error_message() ); ?>
				</li>
			<?php
			}
			?>
			<?php
			if ( $settings_problems ) {
				$s = join( ', ', settings_problems() );
				$config_url = menu_page_url( 'jurassic_ninja', false );
				$e = sprintf( __( "You need to first <a href='%1\$s'>configure</a> %2\$s to be able to launch sites" ), $config_url,  $s );
				echo $e;
			}
			?>
		</ul>
	</div>
<?php
}

function errors() {
	global $errors;
	return $errors;
}

function push_error( $err ) {
	global $errors;
	$errors[] = $err;
}

