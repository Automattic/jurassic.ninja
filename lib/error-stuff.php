<?php

namespace jn;

$errors = [];

function add_error_notices() {
	add_action( 'admin_notices', 'jn\admin_notices_errors' );
}

function admin_notices_errors() {
	if ( ! count( errors() ) ) {
		return;
	}
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php echo esc_html_e( 'There were some Jurassic errors' ); ?></storng>
		</p>
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
		</ul>
	</div>
<?php
}

function push_error( $err ) {
	global $errors;
	$errors[] = $err;
}

function errors() {
	global $errors;
	return $errors;
}