<?php
/*
	instructions tab on settings page
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
 * Instructions tab.
 */
$tab_instructions_label = esc_html__( 'Instructions', 'azrcrv-bl' );
$tab_instructions       = '
<table class="form-table azrcrv-settings">

	<tr>

		<td scope="row" colspan=2>

			<p>' . esc_html__( 'Broken Links is a manual checking tool: it never runs automatically or on a schedule, and it has no effect on the front end of your site. It checks ordinary links and images, as well as BBCode link, url, and img shortcodes.', 'azrcrv-bl' ) . '</p>

			<ol>
				<li>' . esc_html__( 'On the Settings tab, choose which post types should be checked, and optionally exclude URLs containing a particular piece of text (for example, a domain you know is temporarily unreachable).', 'azrcrv-bl' ) . '</li>
				<li>' . esc_html__( 'Save your settings.', 'azrcrv-bl' ) . '</li>
				<li>' . esc_html__( 'On the Check Links tab, click "Check for broken links" whenever you want to run a check. Depending on how many posts, links, and images your site has, this may take a little while to complete.', 'azrcrv-bl' ) . '</li>
				<li>' . esc_html__( 'Once the check finishes, the Check Links tab shows a summary of how many links were checked, working, broken, or could not be checked, and lists every broken or unreachable item — working links are counted in the summary but not listed individually. Each row shows the post type, the page it was found in (click the title to edit it), the type of link (Link, Image, BBCode Link, BBCode URL, or BBCode Image), and its status. Use the post type and link type filters to narrow the list.', 'azrcrv-bl' ) . '</li>
			</ol>

			<p>' . esc_html__( 'A link marked "Could not be checked" means the request timed out or the destination could not be reached during the check — it is not necessarily broken, and you may want to check it again or visit it manually.', 'azrcrv-bl' ) . '</p>

		</td>

	</tr>

</table>';
