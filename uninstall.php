<?php

// Check that code was called from ClassicPress with uninstallation constant declared
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Options to remove.
// Hard-coded rather than using the PLUGIN_HYPHEN constant: WordPress/ClassicPress
// calls uninstall.php standalone, without loading the main plugin file first, so
// that constant is never defined here.
$options = array(
	'azrcrv-bl',
	'azrcrv-bl-results',
	'azrcrv-bl-results-summary',
	'azrcrv-bl-results-last-run',
);

// Remove from single site
if ( ! is_multisite() ) {
	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

// Remove from multi site
else {
	global $wpdb;

	$site_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_site_id = get_current_site_id();

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );

		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	switch_to_blog( $original_site_id );

	foreach ( $options as $option ) {
		delete_site_option( $option );
	}
}

