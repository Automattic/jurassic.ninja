<?php

namespace jn;

$errors = [];

function add_error_notices() {
	add_action( 'admin_notices', 'jn\admin_notices_errors' );
}

function admin_notices_errors() {
	$config_errors = config_errors();
	if ( ! count( errors() ) && ! count( $config_errors ) ) {
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
			if ( $config_errors ) {
				$s = join( ', ', config_errors() );
				$config_url = menu_page_url( 'jurassic_ninja', false );
				$e = sprintf( __( "You need to first <a href='%s'>configure</a> %s to be able to launch sites" ), $config_url,  $s );
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

