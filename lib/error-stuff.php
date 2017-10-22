<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

$errors = [];

/**
 * Registers a hook to admin_notices
 */
function add_error_notices() {
	add_action( 'admin_notices', 'jn\admin_noticies' );
}

/**
 * Will show a persistent admin notice for errors and messages in wp-admin
 * @return [type] [description]
 */
function admin_noticies() {
	// The function_exists() check is just for the case when the plugin has just been activated
	// but composer dependencies were not installed.
	$settings_problems = function_exists( 'jn\settings_problems' ) ? settings_problems() : null;
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
				$settings_url = menu_page_url( 'jurassic_ninja_settings', false );
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

/**
 * Returns an array of WP_Errors pushed to this plugin's own stack of errors
 * @return [Array] array of WP_Error
 */
function errors() {
	global $errors;
	return $errors;
}

/**
 * Push an error to the stack of errors that will be shown on the admin notices
 * @param  WP_Error  $err An error to be shown in the admin notice.
 */
function push_error( $err ) {
	global $errors;
	$errors[] = $err;
}

