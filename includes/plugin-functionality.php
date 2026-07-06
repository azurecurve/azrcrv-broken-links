<?php
/*
	plugin functionality
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
 * Post types that exist purely for internal/structural purposes and are
 * never meaningful to link-check, even though they have show_ui => true.
 */
const EXCLUDED_POST_TYPES = array(
	'attachment',
	'revision',
	'nav_menu_item',
	'custom_css',
	'customize_changeset',
	'oembed_cache',
	'user_request',
	'wp_block',
	'wp_template',
	'wp_template_part',
	'wp_global_styles',
	'wp_navigation',
);

/**
 * Get the full set of post types that can be offered for selection/scanning.
 *
 * Uses show_ui rather than public, so that post types which are editable in
 * the dashboard but not registered as publicly-queryable (a common pattern
 * for internal/structured content types) are still selectable — not just
 * post types with their own front-end URLs.
 *
 * @return array Post type objects, keyed by slug.
 */
function get_selectable_post_types() {

	$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );

	foreach ( EXCLUDED_POST_TYPES as $excluded ) {
		unset( $post_types[ $excluded ] );
	}

	return $post_types;
}

/**
 * Check a single URL and report whether it looks reachable.
 *
 * Uses the WordPress HTTP API (so proxy/SSL/timeout filters set elsewhere on
 * the site are respected) instead of get_headers(), and distinguishes a
 * genuinely broken link from one that simply couldn't be checked.
 *
 * @param string $url URL to check.
 *
 * @return string One of 'working', 'broken', or 'unknown'.
 */
function check_url( $url ) {

	$args = array(
		'timeout'     => 10,
		'redirection' => 5,
		'sslverify'   => false,
	);

	$response = wp_remote_head( $url, $args );

	// Some servers don't support HEAD requests correctly; fall back to GET.
	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 400 ) {
		$response = wp_remote_get( $url, $args );
	}

	if ( is_wp_error( $response ) ) {
		return 'unknown';
	}

	$status_code = wp_remote_retrieve_response_code( $response );

	if ( $status_code >= 200 && $status_code < 400 ) {
		return 'working';
	}

	return 'broken';
}

/**
 * Get the post types that should be scanned, as configured in settings.
 *
 * @return array Post type slugs.
 */
function get_scan_post_types() {

	$options    = get_option_with_defaults( PLUGIN_HYPHEN );
	$post_types = $options['post_types'];

	$selectable_post_types = array_keys( get_selectable_post_types() );

	return array_values( array_intersect( $post_types, $selectable_post_types ) );
}

/**
 * Normalise an extracted URL so it can actually be requested.
 *
 * BBCode links/images are commonly entered without a scheme (e.g.
 * "www.example.com/"), and site-relative HTML links start with "/" — both
 * need resolving to an absolute URL before check_url() can do anything
 * useful with them.
 *
 * @param string $url Raw URL as found in content.
 *
 * @return string|null Absolute URL, or null if it isn't something that can
 *                      be checked over HTTP (anchors, mailto:, tel:, etc).
 */
function normalise_url( $url ) {

	$url = trim( $url );

	if ( '' === $url || '#' === substr( $url, 0, 1 ) ) {
		return null;
	}

	$lowercase_url = strtolower( $url );

	foreach ( array( 'mailto:', 'tel:', 'javascript:' ) as $unsupported_scheme ) {
		if ( 0 === strpos( $lowercase_url, $unsupported_scheme ) ) {
			return null;
		}
	}

	// Protocol-relative, e.g. "//example.com/thing".
	if ( 0 === strpos( $url, '//' ) ) {
		return 'https:' . $url;
	}

	// Site-relative, e.g. "/about-us".
	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}

	// Already absolute.
	if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
		return $url;
	}

	// No scheme and not relative — e.g. a bare "www.example.com/" as commonly
	// entered in BBCode shortcodes. Assume https.
	return 'https://' . $url;
}

/**
 * Extract links, images, and BBCode link/url/img shortcodes from a block of
 * post content.
 *
 * @param string $content Post content to search.
 *
 * @return array List of { url, link_type } pairs, de-duplicated.
 */
function extract_links_from_content( $content ) {

	$found = array();

	$add = function ( $raw_url, $link_type ) use ( &$found ) {
		$url = normalise_url( $raw_url );
		if ( null === $url ) {
			return;
		}
		$found[ $link_type . '|' . $url ] = array(
			'url'       => $url,
			'link_type' => $link_type,
		);
	};

	// HTML links: <a href="...">.
	preg_match_all( '/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $content, $match );
	foreach ( $match['href'] as $url ) {
		$add( $url, 'Link' );
	}

	// HTML images: <img src="...">.
	preg_match_all( '/<img[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', $content, $match );
	foreach ( $match['src'] as $url ) {
		$add( $url, 'Image' );
	}

	// BBCode [url]...[/url] and [url=...]...[/url].
	preg_match_all( '/\[url\](?<url>.*?)\[\/url\]/is', $content, $match );
	foreach ( $match['url'] as $url ) {
		$add( $url, 'BBCode URL' );
	}
	preg_match_all( '/\[url=(?<url>[^\]]+)\].*?\[\/url\]/is', $content, $match );
	foreach ( $match['url'] as $url ) {
		$add( $url, 'BBCode URL' );
	}

	// BBCode [link]...[/link] and [link=...]...[/link].
	preg_match_all( '/\[link\](?<url>.*?)\[\/link\]/is', $content, $match );
	foreach ( $match['url'] as $url ) {
		$add( $url, 'BBCode Link' );
	}
	preg_match_all( '/\[link=(?<url>[^\]]+)\].*?\[\/link\]/is', $content, $match );
	foreach ( $match['url'] as $url ) {
		$add( $url, 'BBCode Link' );
	}

	// BBCode [img]...[/img] and [img=caption]...[/img] — unlike link/url, the
	// URL is always the tag body; the "=" attribute (when present) is a
	// caption/alt, not the URL.
	preg_match_all( '/\[img(?:=[^\]]*)?\](?<url>.*?)\[\/img\]/is', $content, $match );
	foreach ( $match['url'] as $url ) {
		$add( $url, 'BBCode Image' );
	}

	return array_values( $found );
}

/**
 * Run a manual broken link check across the configured post types.
 *
 * This is only ever invoked from an explicit "Check for broken links"
 * admin action (see run_scan() in functions-settings.php) — never from a
 * cron job and never from front-end shortcode output.
 *
 * Every extracted link, image, and BBCode shortcode is checked and counted
 * toward the totals — only the returned $rows are filtered down to
 * non-working entries (broken and unknown), since only those are shown in
 * the Check Links table.
 *
 * @return array {
 *     @type array $rows    Result rows (broken + unknown only): post_id,
 *                          post_title, post_type, link_type, url, status.
 *     @type int   $checked Total number of items checked.
 *     @type int   $working Total number of working items.
 *     @type int   $broken  Total number of broken items.
 *     @type int   $unknown Total number of items that could not be checked.
 * }
 */
function check_for_broken_links() {

	$post_types = get_scan_post_types();

	$rows    = array();
	$checked = 0;
	$working = 0;
	$broken  = 0;
	$unknown = 0;

	$report = array(
		'rows'    => $rows,
		'checked' => $checked,
		'working' => $working,
		'broken'  => $broken,
		'unknown' => $unknown,
	);

	if ( empty( $post_types ) ) {
		return $report;
	}

	$options         = get_option_with_defaults( PLUGIN_HYPHEN );
	$exclude_pattern = trim( $options['exclude_pattern'] );

	global $wpdb;

	$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$sql = "SELECT ID, post_title, post_type, post_content
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
			AND post_type IN ( {$placeholders} )";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$prepared_sql = $wpdb->prepare( $sql, $post_types );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$posts = $wpdb->get_results( $prepared_sql );

	if ( $posts ) {
		foreach ( $posts as $post ) {

			$links = extract_links_from_content( $post->post_content );

			foreach ( $links as $link ) {

				$url       = $link['url'];
				$link_type = $link['link_type'];

				if ( strlen( $exclude_pattern ) > 0 && false !== strpos( $url, $exclude_pattern ) ) {
					continue;
				}

				$status = check_url( $url );

				++$checked;

				switch ( $status ) {
					case 'working':
						++$working;
						break;
					case 'broken':
						++$broken;
						break;
					default:
						++$unknown;
						break;
				}

				// Only broken/unknown items are worth showing in the table;
				// working links are still counted above but not listed.
				if ( 'working' !== $status ) {
					$rows[] = array(
						'post_id'    => $post->ID,
						'post_title' => $post->post_title,
						'post_type'  => $post->post_type,
						'link_type'  => $link_type,
						'url'        => $url,
						'status'     => $status,
					);
				}
			}
		}
	}

	return array(
		'rows'    => $rows,
		'checked' => $checked,
		'working' => $working,
		'broken'  => $broken,
		'unknown' => $unknown,
	);
}
