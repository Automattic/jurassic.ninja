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

			if ( $settings_problems ) {
				$settings_problems = settings_problems();
				$settings_url = menu_page_url( 'jurassic_ninja', false );
				?>
				<?php echo esc_html__( 'You need to get to ' ); ?>
				<a href=<?php echo esc_html( $settings_url ); ?>><?php echo esc_html__( 'Jurassic Ninja Settings' ); ?></a>
				<?php
					echo sprintf( esc_html__( ' and configure %s to be able to launch sites.' ), esc_html( list_in_words( $settings_problems ) ) );
			}
			?>
		</ul>
	</div>
<?php
}

/**
 * Given an array of strings, returns and enumeration in text, comma separated.
 * @param  array  $list The strings to join.
 * @return string       The sentence
 */
function list_in_words( $list = [] ) {
	$last = array_pop( $list );
	$s = join( ', ', $list );
	$s .= count( $list ) ? __( ' and ' ) . $last : $last ;
	return $s;
}

function errors() {
	global $errors;
	return $errors;
}

function push_error( $err ) {
	global $errors;
	$errors[] = $err;
}

