<?php
/*
	style functions
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
 * Register admin styles.
 */
function register_admin_styles() {
	wp_register_style( PLUGIN_HYPHEN . '-admin-styles', esc_url_raw( plugins_url( '../assets/css/admin.css', __FILE__ ) ), array(), '2.0.0' );
	wp_register_style( 'azrcrv-admin-standard-styles', esc_url_raw( plugins_url( '../assets/css/admin-standard.css', __FILE__ ) ), array(), '22.3.2' );
	wp_register_style( 'azrcrv-pluginmenu-admin-styles', esc_url_raw( plugins_url( '../assets/css/admin-pluginmenu.css', __FILE__ ) ), array(), '22.3.2' );
}

/**
 * Enqueue admin styles.
 */
function enqueue_admin_styles() {
	global $pagenow;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['page'] ) && ( $_GET['page'] == PLUGIN_HYPHEN || $_GET['page'] == 'azrcrv-plugin-menu' ) ) {
		wp_enqueue_style( PLUGIN_HYPHEN . '-admin-styles' );
		wp_enqueue_style( 'azrcrv-admin-standard-styles' );
		wp_enqueue_style( 'azrcrv-pluginmenu-admin-styles' );
	}
}

/**
 * This plugin has no front-end output (checking for broken links is a manual,
 * admin-only action), so it registers no front-end styles and has no
 * shortcode-detection logic to decide whether to load any.
 */
