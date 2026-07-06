<?php
/*
	settings functions
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\BrokenLinks;

/**
 * Prevent direct access.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Get options including defaults.
 */
function get_option_with_defaults( $option_name ) {

	$defaults = array(
		'post_types'      => array( 'post', 'page' ),
		'exclude_pattern' => '',
	);

	$options = get_option( $option_name, $defaults );

	$options = wp_parse_args( $options, $defaults );

	return $options;

}

/**
 * Display Settings page.
 */
function display_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'azrcrv-bl' ) );
	}

	// Retrieve plugin configuration options from database.
	$options = get_option_with_defaults( PLUGIN_HYPHEN );

	// Retrieve last scan results, if any.
	$results = get_option( PLUGIN_HYPHEN . '-results', array() );

	echo '<div id="' . esc_attr( PLUGIN_HYPHEN ) . '-general" class="wrap">';

		echo '<h1>';
			echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="' . esc_url_raw( plugins_url( '../assets/images/logo.svg', __FILE__ ) ) . '" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
			echo esc_html( get_admin_page_title() );
		echo '</h1>';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['settings-updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible">
					<p><strong>' . esc_html__( 'Settings have been saved.', 'azrcrv-bl' ) . '</strong></p>
				</div>';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['scan-complete'] ) ) {
		echo '<div class="notice notice-success is-dismissible">
					<p><strong>' . esc_html__( 'Scan complete — see the Check Links tab for results.', 'azrcrv-bl' ) . '</strong></p>
				</div>';
	}

		require_once 'tab-check-links.php';
		require_once 'tab-settings.php';
		require_once 'tab-instructions.php';
		require_once 'tab-other-plugins.php';
		require_once 'tabs-output.php';
	?>

	</div>
	<?php
}

/**
 * Save settings.
 */
function save_options() {
	// Check that user has proper security level.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action', 'azrcrv-bl' ) );
	}
	// Check that nonce field created in configuration form is present.
	if ( ! empty( $_POST ) && check_admin_referer( PLUGIN_HYPHEN, PLUGIN_HYPHEN . '-nonce' ) ) {

		// Retrieve original plugin options array.
		$options = get_option_with_defaults( PLUGIN_HYPHEN );

		$option_name = 'post_types';
		if ( isset( $_POST[ $option_name ] ) && is_array( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = array_map( 'sanitize_key', wp_unslash( $_POST[ $option_name ] ) );
		} else {
			$options[ $option_name ] = array();
		}

		$option_name = 'exclude_pattern';
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) );
		}

		// Store updated options array to database.
		update_option( PLUGIN_HYPHEN, $options );

		// Redirect the page to the configuration form that was processed.
		wp_safe_redirect( add_query_arg( 'page', PLUGIN_HYPHEN . '&settings-updated', admin_url( 'admin.php' ) ) );
		exit;
	}
}

/**
 * Manually run the broken link scan. Triggered only by an administrator
 * clicking "Check for broken links" on the Results tab; never scheduled,
 * never triggered from the front end.
 */
function run_scan() {
	// Check that user has proper security level.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action', 'azrcrv-bl' ) );
	}

	// Check that nonce passed in the "Check for broken links" link is present and valid.
	check_admin_referer( PLUGIN_HYPHEN . '-run-scan' );

	$report = check_for_broken_links();

	update_option( PLUGIN_HYPHEN . '-results', $report['rows'] );
	update_option(
		PLUGIN_HYPHEN . '-results-summary',
		array(
			'checked' => $report['checked'],
			'working' => $report['working'],
			'broken'  => $report['broken'],
			'unknown' => $report['unknown'],
		)
	);
	update_option( PLUGIN_HYPHEN . '-results-last-run', current_time( 'mysql' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'          => PLUGIN_HYPHEN,
				'scan-complete' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
