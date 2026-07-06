<?php
/*
	settings tab on settings page
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
 * Settings tab.
 */

$selectable_post_types = get_selectable_post_types();

$post_type_checkboxes = '';
foreach ( $selectable_post_types as $post_type_slug => $post_type_object ) {
	$checked                = in_array( $post_type_slug, $options['post_types'], true ) ? ' checked="checked"' : '';
	$post_type_checkboxes  .= '
			<label for="post_types-' . esc_attr( $post_type_slug ) . '" style="display: inline-block; margin-right: 16px;">
				<input name="post_types[]" type="checkbox" id="post_types-' . esc_attr( $post_type_slug ) . '" value="' . esc_attr( $post_type_slug ) . '"' . $checked . ' />
				' . esc_html( $post_type_object->labels->name ) . '
			</label>';
}

$tab_settings_label = esc_html__( 'Settings', 'azrcrv-bl' );
$tab_settings       = '
<table class="form-table azrcrv-settings">

	<tr>

		<th scope="row" colspan="2">

			<label for="explanation">
				' . esc_html__( 'Broken Links checks the content of the post types selected below for links, images, and BBCode link/url/img shortcodes, and reports which ones appear to be broken or could not be checked. The check only ever runs when you click "Check for broken links" on the Check Links tab; it is never scheduled and never runs on the front end.', 'azrcrv-bl' ) . '
			</label>

		</th>

	</tr>

	<tr>

		<th scope="row">

			' . esc_html__( 'Post types to check', 'azrcrv-bl' ) . '

		</th>

		<td>

			<fieldset>
				<legend class="screen-reader-text">
						' . esc_html__( 'Post types to check', 'azrcrv-bl' ) . '
				</legend>

				' . $post_type_checkboxes . '

			</fieldset>

		</td>

	</tr>

	<tr>
		<th scope="row">

			' . esc_html__( 'Exclude URLs containing', 'azrcrv-bl' ) . '

		</th>

		<td>

			<input name="exclude_pattern" type="text" id="exclude_pattern" value="' . esc_attr( $options['exclude_pattern'] ) . '" class="regular-text" />

			<p>
				<span class="description">' . esc_html__( 'Optional. Links containing this text will be skipped, e.g. a domain you know is only temporarily unreachable.', 'azrcrv-bl' ) . '</span>
			</p>

		</td>

	</tr>

</table>';
