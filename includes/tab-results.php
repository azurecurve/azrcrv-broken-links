<?php
/*
	results tab on settings page
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
 * Results tab.
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

// Distinct post types present in the current result set, for the filter control.
$result_post_types = array();
foreach ( $results as $result_row ) {
	$result_post_types[ $result_row['post_type'] ] = true;
}
$result_post_types = array_keys( $result_post_types );
sort( $result_post_types );

$filter_options = '<option value="">' . esc_html__( 'All post types', 'azrcrv-bl' ) . '</option>';
foreach ( $result_post_types as $result_post_type ) {
	$post_type_object = get_post_type_object( $result_post_type );
	$label            = $post_type_object ? $post_type_object->labels->name : $result_post_type;
	$filter_options   .= '<option value="' . esc_attr( $result_post_type ) . '">' . esc_html( $label ) . '</option>';
}

$results_rows = '';
if ( empty( $results ) ) {
	$results_rows = '<tr class="azrcrv-bl-no-results"><td colspan="4">' . esc_html__( 'No results to show yet — run a check to see broken links here.', 'azrcrv-bl' ) . '</td></tr>';
} else {
	foreach ( $results as $result_row ) {

		$post_type_object = get_post_type_object( $result_row['post_type'] );
		$post_type_label  = $post_type_object ? $post_type_object->labels->singular_name : $result_row['post_type'];

		$status_class = 'azrcrv-bl-status-' . esc_attr( $result_row['status'] );
		$status_label = array(
			'working' => esc_html__( 'Working', 'azrcrv-bl' ),
			'broken'  => esc_html__( 'Broken', 'azrcrv-bl' ),
			'unknown' => esc_html__( 'Could not be checked', 'azrcrv-bl' ),
		);
		$status_text  = isset( $status_label[ $result_row['status'] ] ) ? $status_label[ $result_row['status'] ] : $result_row['status'];

		$edit_link = get_edit_post_link( $result_row['post_id'] );

		$title_cell = $edit_link
			? '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $result_row['post_title'] ) . '</a>'
			: esc_html( $result_row['post_title'] );

		$results_rows .= '
			<tr data-post-type="' . esc_attr( $result_row['post_type'] ) . '">
				<td>' . esc_html( $post_type_label ) . '</td>
				<td>' . $title_cell . '</td>
				<td><a href="' . esc_url( $result_row['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $result_row['url'] ) . '</a></td>
				<td class="' . $status_class . '">' . esc_html( $status_text ) . '</td>
			</tr>';
	}
}

$tab_results_label = esc_html__( 'Results', 'azrcrv-bl' );
$tab_results        = '
<p>
	<a href="' . esc_url( $run_scan_url ) . '" class="button button-primary">' . esc_html__( 'Check for broken links', 'azrcrv-bl' ) . '</a>
	&nbsp;
	<span class="description">' . $last_run_text . '</span>
</p>

<p id="azrcrv-bl-filter-row"' . ( empty( $results ) ? ' style="display:none;"' : '' ) . '>
	<label for="azrcrv-bl-post-type-filter">' . esc_html__( 'Filter by post type:', 'azrcrv-bl' ) . '</label>
	<select id="azrcrv-bl-post-type-filter">
		' . $filter_options . '
	</select>
</p>

<table class="wp-list-table widefat fixed striped azrcrv-bl-results-table" id="azrcrv-bl-results-table">
	<thead>
		<tr>
			<th>' . esc_html__( 'Post Type', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Found In', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Link', 'azrcrv-bl' ) . '</th>
			<th>' . esc_html__( 'Status', 'azrcrv-bl' ) . '</th>
		</tr>
	</thead>
	<tbody>
		' . $results_rows . '
	</tbody>
</table>';
