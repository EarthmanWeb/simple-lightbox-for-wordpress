<?php
/**
 * Functions
 * Provides global access to specific functionality
 * @package Simple Lightbox for WordPress
 * @author Archetyped
 * @author Earthman Media
 */

/* Template Tags */

/**
 * Activate links in user-defined content
 * @param string $content
 * @return string Updated content with activated links
 */
function slb_activate( $content, $group = null ) {
	// Validate
	if ( empty( $content ) ) {
		return $content;
	}
	// Activate links
	$content = $GLOBALS['slb']->activate_links( $content, $group );
	return $content;
}

/**
 * Register a gallery of image URLs for lightbox display.
 *
 * Returns hidden HTML with SLB-activated anchor tags. Echo the result
 * wherever the hidden links should live in the DOM (e.g. wp_footer).
 *
 * @param string $group   Gallery group identifier.
 * @param array  $urls    Array of image URLs.
 * @param array  $options Optional. See SLB_Lightbox::register_gallery().
 * @return string HTML with hidden, lightbox-activated links.
 */
function slb_register_gallery( $group, array $urls, array $options = [] ) {
	if ( ! isset( $GLOBALS['slb'] ) ) {
		return '';
	}
	return $GLOBALS['slb']->register_gallery( $group, $urls, $options );
}
