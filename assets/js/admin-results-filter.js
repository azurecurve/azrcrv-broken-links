/**
 * Broken Links — real-time post type and link type filters for the results
 * table.
 *
 * Filters rows client-side only; the scan itself is never re-run by this
 * script, and no network request is made when either filter changes. Both
 * filters apply together (a row must match the selected post type AND the
 * selected link type to remain visible); an empty selection matches
 * everything for that filter.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	'use strict';

	var postTypeFilter = document.getElementById( 'azrcrv-bl-post-type-filter' );
	var linkTypeFilter = document.getElementById( 'azrcrv-bl-link-type-filter' );
	var table          = document.getElementById( 'azrcrv-bl-results-table' );

	if ( ! table || ( ! postTypeFilter && ! linkTypeFilter ) ) {
		return;
	}

	var rows = table.querySelectorAll( 'tbody tr[data-post-type]' );

	function applyFilters() {
		var selectedPostType = postTypeFilter ? postTypeFilter.value : '';
		var selectedLinkType = linkTypeFilter ? linkTypeFilter.value : '';

		rows.forEach( function ( row ) {
			var matchesPostType = ( selectedPostType === '' || row.getAttribute( 'data-post-type' ) === selectedPostType );
			var matchesLinkType = ( selectedLinkType === '' || row.getAttribute( 'data-link-type' ) === selectedLinkType );

			row.style.display = ( matchesPostType && matchesLinkType ) ? '' : 'none';
		} );
	}

	if ( postTypeFilter ) {
		postTypeFilter.addEventListener( 'change', applyFilters );
	}
	if ( linkTypeFilter ) {
		linkTypeFilter.addEventListener( 'change', applyFilters );
	}

} );
