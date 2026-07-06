<?php
/*
	check links tab on settings page
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
 * Check Links tab.
 *
 * $results and $options are set in display_options() before this file is
 * required.
 */

$run_scan_url = wp_nonce_url(
	add_query_arg(
		'action',
		PLUGIN_UNDERSCORE . '_run_scan',
		admin_url( 'admin-post.php' )
	),
	PLUGIN_HYPHEN . '-run-scan'
);

$last_run = get_option( PLUGIN_HYPHEN . '-results-last-run', '' );

$last_run_text = ( strlen( $last_run ) > 0 )
	? sprintf(
		/* translators: %s: date and time the scan last ran. */
		esc_html__( 'Last checked: %s', 'azrcrv-bl' ),
		esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_run ) )
	)
	: esc_html__( 'This has not been run yet.', 'azrcrv-bl' );

// Totals from the last run. $results only ever contains broken/unknown rows
// (working items are still counted during the scan, just not listed), so the
// totals summary is read from its own stored option rather than derived from
// $results.
$summary = get_option(
	PLUGIN_HYPHEN . '-results-summary',
	array(
		'checked' => 0,
		'working' => 0,
		'broken'  => 0,
		'unknown' => 0,
	)
);

$totals_line = sprintf(
	/* translators: 1: links checked, 2: links working, 3: links broken, 4: links that could not be checked. */
	esc_html__( '%1$d links checked / %2$d links working / %3$d links broken / %4$d could not be checked.', 'azrcrv-bl' ),
	absint( $summary['checked'] ),
	absint( $summary['working'] ),
	absint( $summary['broken'] ),
	absint( $summary['unknown'] )
);

// Distinct post types present in the current result set, for the filter control.
$result_post_types = array();
foreach ( $results as $result_row ) {
	$result_post_types[ $result_row['post_type'] ] = true;
}
$result_post_types = array_keys( $result_post_types );
sort( $result_post_types );

$post_type_filter_options = '<option value="">' . esc_html__( 'All post types', 'azrcrv-bl' ) . '</option>';
foreach ( $result_post_types as $result_post_type ) {
	$post_type_object          = get_post_type_object( $result_post_type );
	$label                     = $post_type_object ? $post_type_object->labels->name : $result_post_type;
	$post_type_filter_options .= '<option value="' . esc_attr( $result_post_type ) . '">' . esc_html( $label ) . '</option>';
}

// Distinct link types present in the current result set, for the filter control.
$result_link_types = array();
foreach ( $results as $result_row ) {
	$result_link_types[ $result_row['link_type'] ] = true;
}
$result_link_types = array_keys( $result_link_types );
sort( $result_link_types );

$link_type_filter_options = '<option value="">' . esc_html__( 'All link types', 'azrcrv-bl' ) . '</option>';
foreach ( $result_link_types as $result_link_type ) {
	$link_type_filter_options .= '<option value="' . esc_attr( $result_link_type ) . '">' . esc_html( $result_link_type ) . '</option>';
}

$results_rows = '';
if ( empty( $results ) ) {
	$results_rows = '<tr class="azrcrv-bl-no-results"><td colspan="5">' . esc_html__( 'No broken or unreachable links found — run a check to see results here.', 'azrcrv-bl' ) . '</td></tr>';
} else {
	foreach ( $results as $result_row ) {

		$post_type_object = get_post_type_object( $result_row['post_type'] );
		$post_type_label  = $post_type_object ? $post_type_object->labels->singular_name : $result_row['post_type'];

		$status_class = 'azrcrv-bl-status-' . esc_attr( $result_row['status'] );
		$status_label = array(
			'broken'  => esc_html__( 'Broken', 'azrcrv-bl' ),
			'unknown' => esc_html__( 'Could not be checked', 'azrcrv-bl' ),
		);
		$status_text  = isset( $status_label[ $result_row['status'] ] ) ? $status_label[ $result_row['status'] ] : $result_row['status'];

		$edit_link = get_edit_post_link( $result_row['post_id'] );

		// A post can genuinely have an empty title (untitled/imported content);
		// without a fallback the cell would render as literally nothing, with
		// no visible link to click. Match WordPress core's own Posts list
		// convention of showing "(no title)" instead.
		$post_title = ( '' !== trim( (string) $result_row['post_title'] ) )
			? $result_row['post_title']
			: __( '(no title)', 'azrcrv-bl' );

		$title_cell = $edit_link
			? '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $post_title ) . '</a>'
			: esc_html( $post_title );

		$results_rows .= '
			<tr data-post-type="' . esc_attr( $result_row['post_type'] ) . '" data-link-type="' . esc_attr( $result_row['link_type'] ) . '">
				<td>' . esc_html( $post_type_label ) . '</td>
				<td>' . $title_cell . '</td>
				<td>' . esc_html( $result_row['link_type'] ) . '</td>
				<td><a href="' . esc_url( $result_row['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $result_row['url'] ) . '</a></td>
				<td class="' . $status_class . '">' . esc_html( $status_text ) . '</td>
			</tr>';
	}
}

$tab_results_label = esc_html__( 'Check Links', 'azrcrv-bl' );
$tab_results        = '
<p>
	<a href="' . esc_url( $run_scan_url ) . '" class="button button-primary">' . esc_html__( 'Check for broken links', 'azrcrv-bl' ) . '</a>
	&nbsp;
	<span class="description">' . $last_run_text . '</span>
</p>

<p class="azrcrv-bl-totals"><strong>' . $totals_line . '</strong></p>

<p id="azrcrv-bl-filter-row"' . ( empty( $results ) ? ' style="display:none;"' : '' ) . '>
	<label for="azrcrv-bl-post-type-filter">' . esc_html__( 'Filter by post type:', 'azrcrv-bl' ) . '</label>
	<select id="azrcrv-bl-post-type-filter">
		' . $post_type_filter_options . '
	</select>
	&nbsp;&nbsp;
	<label for="azrcrv-bl-link-type-filter">' . esc_html__( 'Filter by link type:', 'azrcrv-bl' ) . '</label>
	<select id="azrcrv-bl-link-type-filter">
		' . $link_type_filter_options . '
	</select>
</p>

<table class="wp-list-table widefat fixed striped azrcrv-bl-results-table" id="azrcrv-bl-results-table">
	<thead>
		<tr>
			<th>' . esc_html__( 'Post Type', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Found In', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Link Type', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Link', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Status', 'azrcrv-bl' ) . '</th>
		</tr>
	</thead>
	<tbody>
		' . $results_rows . '
	</tbody>
</table>';
