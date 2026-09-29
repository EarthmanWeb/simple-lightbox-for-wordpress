<?php
/**
 * Simple Lightbox
 *
 * @package Simple Lightbox for WordPress
 * @author Archetyped <support@archetyped.com>
 * @author Earthman Media <https://github.com/EarthmanWeb>
 * @copyright 2025 Archetyped
 * @copyright 2026 Earthman Media
 *
 * Plugin Name: Simple Lightbox for WordPress
 * Plugin URI: https://github.com/EarthmanWeb/simple-lightbox-for-wordpress
 * Description: The highly customizable lightbox for WordPress
 * Version: 3.0.10
 * Requires at least: 5.3
 * Requires PHP: 5.6.20
 * Text Domain: simple-lightbox-for-wordpress
 * Domain Path: /l10n
 * Author: Earthman Media
 * Author URI: https://github.com/EarthmanWeb
 * Support URI: https://github.com/EarthmanWeb/simple-lightbox-for-wordpress/issues
*/

require_once dirname( __FILE__ ) . '/includes/class-requirements-check.php';

/* @var array Plugin Requirements */
$slb_requirements = new SLB_Requirements_Check(
	array(
		'name' => 'Simple Lightbox',
		'file' => __FILE__,
		'uri'  => array(
			'reference' => 'https://github.com/EarthmanWeb/simple-lightbox-for-wordpress/wiki/Requirements',
		),
	)
);

// Check requirements before initializing plugin.
if ( $slb_requirements->passes() ) {
	/**
	 * Initialize SLB
	 *
	 * @return void
	 */
	function slb_init() {
		require_once dirname( __FILE__ ) . '/load.php';
	}
	add_action( 'init', 'slb_init', 1 );
}

unset( $slb_requirements );
