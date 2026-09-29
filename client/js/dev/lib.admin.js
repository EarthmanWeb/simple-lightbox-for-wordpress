/**
 * Admin
 * @package Simple Lightbox for WordPress
 * @subpackage Admin
 * @author Archetyped
 * @author Earthman Media
 */

/* global SLB, postboxes, pagenow */

if ( !!window.SLB && !!SLB.attach ) { (function ($) {

SLB.attach('Admin', {
	/**
	 * Initialization routines
	 */
	init: function() {
		if ( postboxes ) {
			postboxes.add_postbox_toggles(pagenow);
		}
	},
});

$(document).ready(function() {
	SLB.Admin.init();
});

})(jQuery);}