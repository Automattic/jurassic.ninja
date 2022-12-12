<?php
/**
 * CRM for everyone.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_init',
	function () {

		$defaults = array(
			'jpcrm' => false,
			'jpcrm-build' => false,
            'jpcrm-trunk' => false,
			'jpcrm-version' => false,
			'jpcrm-populate-crm-data' => false,
			'jpcrm-populate-woo-data' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app, $features, $domain ) use ( $defaults ) {

				$features = array_merge( $defaults, $features );

				// Don't install if Jetpack CRM isn't selected.
				if ( ! $features['jpcrm'] ) {
					return;
				}

				if ( $features['jpcrm-version'] ) {

					// Install specified version of Jetpack CRM from WP.org repo.
					debug( '%s: Installing Jetpack CRM version %s from WP.org repo', $domain, $features['jpcrm-version'] );
					add_jpcrm_from_wporg( $features['jpcrm-version'] );

				} elseif ( $features['jpcrm-build'] ) {

					// Install custom build of Jetpack CRM.
					debug( '%s: Installing Jetpack CRM from %s', $domain, $features['jpcrm-build'] );
					add_jpcrm_from_custom_build( $features['jpcrm-build'] );

				} elseif ( $features['jpcrm-trunk'] ) {

                    // Install trunk build of Jetpack CRM.
                    debug( '%s: Installing Jetpack CRM from %s', $domain, 'trunk' );
                    add_jpcrm_from_custom_build( 'trunk' );

                }else {

					// Install current version of Jetpack CRM from WP.org repo.
					debug( '%s: Installing Jetpack CRM from WP.org', $domain );
					add_directory_plugin( 'zero-bs-crm' );

				}

				if ( $features['jpcrm-populate-crm-data'] || $features['jpcrm-populate-woo-data'] ) {
					add_jpcrm_sdg();
					if ( $features['jpcrm-populate-crm-data'] ) {
						populate_crm_data();
					}
					if ( $features['jpcrm-populate-woo-data'] ) {
						populate_woo_data();
					}
				}

			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {

				if ( isset( $json_params['jpcrm'] ) ) {
					$features['jpcrm'] = $json_params['jpcrm'];
				}

				if ( isset( $json_params['jpcrm-version'] ) ) {
					$features['jpcrm-version'] = $json_params['jpcrm-version'];
				}

				if ( isset( $json_params['jpcrm-build'] ) ) {
					$features['jpcrm-build'] = $json_params['jpcrm-build'];
				}

                if ( isset( $json_params['jpcrm-trunk'] ) ) {
                    $features['jpcrm-trunk'] = $json_params['jpcrm-trunk'];
                }

				if ( isset( $json_params['jpcrm-populate-crm-data'] ) ) {
					$features['jpcrm-populate-crm-data'] = $json_params['jpcrm-populate-crm-data'];
				}

				if ( isset( $json_params['jpcrm-populate-woo-data'] ) ) {
					$features['jpcrm-populate-woo-data'] = $json_params['jpcrm-populate-woo-data'];
				}

				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Installs and activates a specified version of Jetpack CRM from the WP.org plugin repo.
 *
 * @param string $version Version of Jetpack CRM.
 */
function add_jpcrm_from_wporg( $version ) {

	// Verify we have a valid version number.
	if ( ! version_compare( $version, '1.0.0', '>=' ) ) {
		return new \WP_Error( 'bad_version_number', 'Bad version number.', array( 'status' => 404 ) );
	}

	$cmd = "wp plugin install zero-bs-crm --version=$version --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);

}

/**
 * Installs and activates a specified branch of Jetpack CRM from our custom build URL.
 *
 * @param string $branch name of branch to use.
 */
function add_jpcrm_from_custom_build( $branch ) {

	// phpcs:disable Squiz.PHP.CommentedOutCode.Found

	/*
	 * Require commit SHA-1 hash (40 char long hex).
	 * if ( ! preg_match( '/^[A-Fa-f0-9]{40}$/', $build ) ) {
	 * return new \WP_Error( 'bad_commit_hash', 'Invalid commit hash.', array( 'status' => 404 ) );
	 * }
	 */
	// phpcs:enable

	$clean_branch = str_replace( '/', '_', $branch );

	// note that this public link is in a public repo
	$jpcrm_build_base_url = 'https://jetpackcrm-builds.s3.amazonaws.com/builds/';
	$jpcrm_build_url = $jpcrm_build_base_url . 'zero-bs-crm-' . $clean_branch . '.zip';

	$cmd = "wp plugin install $jpcrm_build_url --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Installs and activates Jetpack CRM Sample Data Generator.
 */
function add_jpcrm_sdg() {
	$jpcrm_sdg_url = 'https://jetpackcrm-builds.s3.amazonaws.com/jpcrm-sdg/jpcrm-sdg.zip';

	$cmd = "wp plugin install $jpcrm_sdg_url --activate";

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Populates Jetpack CRM with data from JPCRM SDG.
 */
function populate_crm_data() {
	$cmd = 'wp jpcrmsdg --objtype=all';

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Populates Woo with data from JPCRM SDG.
 */
function populate_woo_data() {
	$cmd = 'wp jpcrmsdg --objtype=woo';

	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Register a shortcode which renders Jetpack Licensing controls suitable for SpecialOps usage.
 */
\add_shortcode(
	'jn_jpcrm_options',
	function () {
		ob_start();
		?>
			<style>
			.jn-jpcrm-options ul {
				list-style-type: none;
			}
			.jn-jpcrm-options input[type="text"] {
				margin-left: 25px;
			}
			</style>
			<div class="jn-jpcrm-options" style="display:none;">
				<ul>
					<li><label><input type="radio" name="jpcrm-options" checked /> WP.org</label></li>
					<li><label><input type="radio" name="jpcrm-options" data-feature="jpcrm-version" /> Version: <input type="text" id="jpcrm-version" placeholder="4.10.1"></label></li>
                    <li><label><input type="radio" name="jpcrm-options" data-feature="jpcrm-trunk" /> GH trunk</label></li>
					<li><label><input type="radio" name="jpcrm-options" data-feature="jpcrm-build" /> Build: <input type="text" id="jpcrm-build" placeholder="fix/314/rationalise_pi"></label></li>
					<li><label><input type="checkbox" name="jpcrm-options" data-feature="jpcrm-populate-crm-data" /> Populate CRM data</label></li>
					<li style="display:none"><label><input type="checkbox" name="jpcrm-options" data-feature="jpcrm-populate-woo-data" /> Populate Woo data</label></li>
				</ul>
			</div>
			<script>
				// hide/show "populate Woo data" option depending on Woo plugin selection
				function jpcrm_toggle_woo_populate_button(e) {
					if (e.target.dataset['feature'] && e.target.dataset['feature'] === 'woocommerce') {
						if (!e.target.checked) {
							document.querySelector('[data-feature="jpcrm-populate-woo-data"]').checked = false;
						}
						document.querySelector('[data-feature="jpcrm-populate-woo-data"]').parentElement.parentElement.style.display = e.target.checked ? '' : 'none';
					}
				}

				// select radio button associated with input
				function jpcrm_select_associated_radio_button(e) {
					e.target.parentElement.children[0].checked = true;
				}

				document.querySelector('.entry-content').addEventListener('click', jpcrm_toggle_woo_populate_button);
				document.querySelectorAll('#jpcrm-version, #jpcrm-build').forEach(i => i.addEventListener('click', jpcrm_select_associated_radio_button));

			</script>
		<?php
		return ob_get_clean();
	}
);
