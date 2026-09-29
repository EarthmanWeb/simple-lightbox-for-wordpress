=== Simple Lightbox for WordPress ===
Contributors: Archetyped, EarthmanWeb
License: GPLv2
Tags: lightbox, gallery, photography, image, slideshow
Requires at least: 5.3
Tested up to: 6.9
Requires PHP: 5.6.20
Stable tag: 3.0.10

The highly customizable lightbox for WordPress

== Description ==
Simple Lightbox for WordPress is a fork of [Simple Lightbox](https://github.com/archetyped/simple-lightbox) by Archetyped, maintained by [Earthman Media](https://github.com/EarthmanWeb). It is a very simple and customizable lightbox that is easy to add to your WordPress website.

#### Features

Options for customizing the lightbox behavior are located in the **Appearance > Lightbox** admin menu (or just click the **Settings** link below the plugin's name when viewing the list of installed plugins)

* Automatically activate links (no manual coding required)
* Automatically resize lightbox to fit in window
* Customize lightbox with **themes**
* Mobile-optimized responsive themes included
* Customizable lightbox animations
* Infinitely customizable with **add-ons**
* Supports WordPress **image attachment** links
* Supports links in **widgets**
* Keyboard Navigation
* Display media metadata (caption, description, etc.) in lightbox
* Enable Lightbox depending on Page Type (Home, Pages, Archive, etc.)
* Group image links (play as a slideshow)
* Group image links by Post (separate slideshow for each post on page)

#### Usage

1.  Insert links to images/image attachments into your posts/pages

**That's it! The image will be displayed in a lightbox automatically.**

* For more usage tips, go to [Simple Lightbox for WordPress on GitHub](https://github.com/EarthmanWeb/simple-lightbox-for-wordpress)
* See [Simple Lightbox's documentation](https://github.com/EarthmanWeb/simple-lightbox-for-wordpress/wiki) for in-depth information on using and customizing SLB.

#### Developer API: Register External Galleries

Themes and plugins can register image galleries for lightbox display using `slb_register_gallery()`. This is useful when your images come from directory scans, APIs, or other dynamic sources rather than WordPress content.

**Function signature:**

`slb_register_gallery( string $group, array $urls, array $options = [] )`

* `$group` — Gallery group identifier (images in the same group navigate together as a slideshow)
* `$urls` — Array of image URLs
* `$options` — Optional. Accepts `container_class` (string) to add a CSS class to the hidden wrapper div

**Returns:** HTML string containing hidden, SLB-activated anchor tags. Echo the result in `wp_footer` or wherever the hidden links should live in the DOM.

**Important:** Call `slb_register_gallery()` on `template_redirect` (or earlier than `wp_enqueue_scripts`) so SLB detects the cached media items and enqueues its viewer JS. Then echo the return value in `wp_footer` to output the hidden link markup.

**Example — register a directory of images as a lightbox gallery:**

`// 1. Register early so SLB enqueues its JS`
`add_action( 'template_redirect', function() {`
`    $images = glob( get_stylesheet_directory() . '/slideshow/*.jpg' );`
`    $urls   = array_map( function( $f ) {`
`        return get_stylesheet_directory_uri() . '/slideshow/' . basename( $f );`
`    }, $images );`
`    slb_register_gallery( 'my-slideshow', $urls );`
`} );`

`// 2. Output the hidden links in the footer`
`add_action( 'wp_footer', function() {`
`    $images = glob( get_stylesheet_directory() . '/slideshow/*.jpg' );`
`    $urls   = array_map( function( $f ) {`
`        return get_stylesheet_directory_uri() . '/slideshow/' . basename( $f );`
`    }, $images );`
`    echo slb_register_gallery( 'my-slideshow', $urls );`
`}, 5 );`

`// 3. Trigger the lightbox from any element via JS`
`// document.querySelector('.phf-slb-group-my-slideshow a').click();`

== Installation ==

1.  Install and activate SLB
2.  Verify that your site's theme uses the `wp_head()`, `wp_footer()`, & `the_content()` template tags (standard in any professional theme)

== Upgrade Notice ==

= 2.8.0 =
Faster link processing & other optimizations (WordPress 5.3+ & PHP 7.2+ required).

= 2.7.0 =
Fixes & improvements. PHP 5.4+ Required.

== Frequently Asked Questions ==

Get more information on [Simple Lightbox for WordPress on GitHub](https://github.com/EarthmanWeb/simple-lightbox-for-wordpress)

== Screenshots ==

1.  Lightbox Customization Options
2.  Light Theme
3.  Dark Theme

== Changelog ==

= 3.0.10 =

* Fix: Black theme loading spinner tiled across the lightbox container (legacy GIF background removed; CSS spinner only)
* Update: Remove GitHub-hosted update checker (updates ship via WordPress.org)

= 3.0.4 =

* Feature: Add `register_gallery()` API for external gallery registration
* Feature: Add `slb_register_gallery()` global helper for theme/plugin integration
* Allows themes and plugins to register image galleries for lightbox display without embedding visible gallery markup

= 3.0.3 =

* Chore: Add version bump script and prepare-commit-msg hook for automated releases

= 3.0.2 =

* Fix: Resolve WP 6.7+ _load_textdomain_just_in_time notice by deferring early __() call
* Fix: Move textdomain loading to init priority 0 to ensure availability before option registration

= 3.0.1 =

* Add: Transparent CSS spinner replacing GIF-based loading indicator
* Add: Configurable overlay background color with WordPress color picker
* Add: Show/hide option for slideshow item counter
* Add: Automated pre-loading of next image in slideshow groups
* Add: Slide-in transition as alternative to fade
* Add: Configurable transition type (Fade/Slide) via select dropdown
* Fix: Smooth cross-fade and slide transitions with parallel container resize
* Fix: Title gate — "Show Image Title in Caption" off hides all auto-detected titles
* Fix: Details panel no longer animates when no visible content

= 3.0.0 =

* Fork: GPL v2 fork by Earthman Media (EarthmanWeb/simple-lightbox-for-wordpress)
* Update: Plugin name, author, URIs, and text domain for fork identity
* Add: README_LEGAL.md for GPL v2 compliance documentation

= 2.9.6 =

* Fix: Defer textdomain loading to init hook
* Fix: Replace deprecated parent callable syntax (PHP 8.x)
* Fix: Use correct string interpolation syntax (PHP 8.x)
* Fix: Guard null parameter to trim() (PHP 8.x)
* Fix: Declare handler cache group non-persistent
* Fix: Handle whitespace/newlines in link tags
* Optimize: Read local files directly, avoid HTTP loopback
* Add: Plugin Update Checker (PUC) for GitHub-hosted updates

= 2.9.5 =

* Optimize: Parameterize DB queries.
* Update: Confirm WordPress 6.9 compatibility.

= 2.9.4 =

* Optimize: Escape special characters in DOM attributes.
* Update: Confirm WordPress 6.2-6.8 compatibility.

= 2.9.3 =

* Hotfix: WordPress 6.1 `wp_rand()` bug (32-bit platforms) ([#974](https://github.com/EarthmanWeb/simple-lightbox-for-wordpress/issues/974))
* Update: Confirm WordPress 6.1 compatibility
* Optimize: Media item cache key generation
* Optimize: Prune build tasks

= 2.9.2 =

* Optimize: Symbolic link handing for file/directory paths.

= 2.9.1 =

* Fix: Validate hook priority values (Let's Getz Prioritized)

= 2.9.0 =

* Add: Support WebP image format
* Add: Support AVIF image format
* Add: Documentation link to readme file
* Optimize: Code cleanup/refactoring
* Optimize: WPCS validation (Phase 1)
* Optimize: Activate links after all other filters
* Optimize: Plugin metadata retrieval
* Update: Confirm WordPress 6.0 compatibility
* Update: Build dependencies
* Update: GitHub issue templates

[See full changelog](https://github.com/EarthmanWeb/simple-lightbox-for-wordpress/releases)