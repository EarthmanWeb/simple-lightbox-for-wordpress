<?php
/**
 * Custom Slideshows
 *
 * Self-contained feature module: lets an admin assign individual posts/pages
 * (grouped by post type) to per-post overrides of the global lightbox slideshow
 * options. Owns its own admin submenu, option storage, admin UI, save handler,
 * and the front-end interception of the client options.
 *
 * Deliberately standalone — wired into the plugin with two lines (instantiate in
 * load.php, register the submenu in SLB_Lightbox::admin_menus()). Nothing else in
 * the plugin depends on it, and it depends only on the public `slb_client_options`
 * filter exposed by SLB_Options::build_client_output().
 *
 * @package Simple Lightbox for WordPress
 * @subpackage Admin
 * @author Earthman Media
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SLB_Custom_Slideshows {

	/**
	 * Option key holding all slideshow groups.
	 *
	 * @var string
	 */
	const OPTION = 'slb_custom_slideshows';

	/**
	 * Parent menu slug (the promoted top-level Lightbox menu).
	 *
	 * @var string
	 */
	const PARENT_SLUG = 'slb_options';

	/**
	 * Submenu page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'slb_custom_slideshows';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE = 'slb_custom_slideshows_save';

	/**
	 * Client option ids that may be overridden per post, with their input type.
	 *
	 * @var array<string,string>
	 */
	protected $fields = array(
		'slideshow_duration'  => 'number',
		'slideshow_autostart' => 'bool',
		'ui_transition_type'  => 'select',
	);

	/**
	 * Wire up hooks. Admin UI hooks only fire in admin; the client filter is
	 * always registered (it is a no-op on non-singular views).
	 */
	public function __construct() {
		// Priority 12: after SLB_Admin::init_menus() (11) has registered the
		// parent top-level "Lightbox" menu, so add_submenu_page() resolves it.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 12 );
		add_action( 'admin_post_' . self::NONCE, array( $this, 'handle_save' ) );
		add_filter( 'slb_client_options', array( $this, 'filter_client_options' ) );
	}

	/* -- Menu -- */

	/**
	 * Register the Custom Slideshows submenu under the Lightbox menu using the
	 * standard WordPress API. Added after the default "Settings" submenu, so it
	 * appears second; Settings stays first and is the menu's default screen.
	 */
	public function register_menu() {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Custom Slideshows', 'simple-lightbox-for-wordpress' ),
			__( 'Custom Slideshows', 'simple-lightbox-for-wordpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/* -- Storage -- */

	/**
	 * Get all saved groups.
	 *
	 * @return array<int,array> Each: post_type, post_ids[], overrides[].
	 */
	public function get_groups() {
		$groups = get_option( self::OPTION, array() );
		return is_array( $groups ) ? array_values( $groups ) : array();
	}

	/* -- Admin UI -- */

	/**
	 * Render the admin page: a repeater of slideshow groups.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access Denied', 'simple-lightbox-for-wordpress' ) );
		}

		$groups        = $this->get_groups();
		$post_types    = $this->get_public_post_types();
		$transitions   = $this->get_transition_choices();
		$saved         = isset( $_GET['updated'] ) ? (bool) absint( $_GET['updated'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification

		// Posts grouped by type for the type-filtered picker (id => [type => label]).
		$posts_by_type = $this->get_posts_by_type( array_keys( $post_types ) );
		?>
		<div class="wrap slb">
			<h1><?php esc_html_e( 'Custom Slideshows', 'simple-lightbox-for-wordpress' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Assign posts to a slideshow group to override the global lightbox options for those posts. Leave a field blank to use the site default.', 'simple-lightbox-for-wordpress' ); ?>
			</p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Custom slideshows saved.', 'simple-lightbox-for-wordpress' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="slb-cs-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE ); ?>" />
				<?php wp_nonce_field( self::NONCE ); ?>

				<div id="slb-cs-groups"></div>

				<p>
					<button type="button" class="button" id="slb-cs-add"><?php esc_html_e( '+ Add slideshow group', 'simple-lightbox-for-wordpress' ); ?></button>
				</p>

				<?php submit_button( __( 'Save Custom Slideshows', 'simple-lightbox-for-wordpress' ) ); ?>
			</form>
		</div>

		<?php
		$this->render_styles();
		$this->render_script(
			array(
				'groups'       => $groups,
				'postTypes'    => $post_types,
				'postsByType'  => $posts_by_type,
				'transitions'  => $transitions,
				'i18n'         => array(
					'remove'      => __( 'Remove group', 'simple-lightbox-for-wordpress' ),
					'postType'    => __( 'Post type', 'simple-lightbox-for-wordpress' ),
					'posts'       => __( 'Posts', 'simple-lightbox-for-wordpress' ),
					'searchPosts' => __( 'Search posts…', 'simple-lightbox-for-wordpress' ),
					'duration'    => __( 'Slide duration (seconds)', 'simple-lightbox-for-wordpress' ),
					'autostart'   => __( 'Autostart slideshow', 'simple-lightbox-for-wordpress' ),
					'transition'  => __( 'Transition', 'simple-lightbox-for-wordpress' ),
					'siteDefault' => __( 'Site default', 'simple-lightbox-for-wordpress' ),
					'on'          => __( 'On', 'simple-lightbox-for-wordpress' ),
					'off'         => __( 'Off', 'simple-lightbox-for-wordpress' ),
					'noMatches'   => __( 'No matches', 'simple-lightbox-for-wordpress' ),
					'pickType'    => __( 'Select a post type first', 'simple-lightbox-for-wordpress' ),
				),
			)
		);
	}

	/**
	 * Inline styles for the admin repeater.
	 */
	protected function render_styles() {
		?>
		<style>
			.slb-cs-group { background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:16px; margin:0 0 16px; max-width:780px; }
			.slb-cs-group__row { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start; }
			.slb-cs-field { display:flex; flex-direction:column; gap:4px; }
			.slb-cs-field label { font-weight:600; font-size:12px; }
			.slb-cs-field--posts { flex:1 1 100%; }
			.slb-cs-group__head { display:flex; justify-content:flex-end; margin:0 0 8px; }
			.slb-cs-tok { position:relative; }
			.slb-cs-tok__tokens { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px; }
			.slb-cs-tok__token { display:inline-flex; align-items:center; gap:6px; padding:3px 8px; background:#2271b1; color:#fff; border-radius:12px; font-size:12px; line-height:1.4; }
			.slb-cs-tok__token button { all:unset; cursor:pointer; font-size:14px; line-height:1; color:#fff; opacity:.85; }
			.slb-cs-tok__token button:hover { opacity:1; }
			.slb-cs-tok__search { width:100%; max-width:360px; }
			.slb-cs-tok__suggest { position:absolute; z-index:5; left:0; right:0; margin:2px 0 0; padding:0; list-style:none; max-height:220px; overflow-y:auto; background:#fff; border:1px solid #ccd0d4; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,.12); max-width:360px; }
			.slb-cs-tok__suggest li { padding:6px 10px; cursor:pointer; font-size:13px; }
			.slb-cs-tok__suggest li:hover, .slb-cs-tok__suggest li[aria-selected="true"] { background:#f0f6fc; }
			.slb-cs-tok__empty { padding:6px 10px; color:#757575; font-size:12px; }
		</style>
		<?php
	}

	/**
	 * Inline controller script. Pure vanilla JS, no plugin/theme dependency —
	 * replicates the token-picker UX but filtered by the selected post type and
	 * storing structured group rows.
	 *
	 * @param array $data Bootstrapped data for the UI.
	 */
	protected function render_script( $data ) {
		?>
		<script>
		( function () {
			var DATA = <?php echo wp_json_encode( $data ); ?>;
			var groupsEl = document.getElementById( 'slb-cs-groups' );
			var addBtn   = document.getElementById( 'slb-cs-add' );
			if ( ! groupsEl || ! addBtn ) { return; }

			var idx = 0;

			function el( tag, attrs, kids ) {
				var n = document.createElement( tag );
				attrs = attrs || {};
				Object.keys( attrs ).forEach( function ( k ) {
					if ( k === 'class' ) { n.className = attrs[k]; }
					else if ( k === 'text' ) { n.textContent = attrs[k]; }
					else { n.setAttribute( k, attrs[k] ); }
				} );
				( kids || [] ).forEach( function ( c ) { if ( c ) { n.appendChild( c ); } } );
				return n;
			}

			// Build the type-filtered token picker for one group.
			function buildPicker( i, type, selectedIds ) {
				var wrap   = el( 'div', { 'class': 'slb-cs-tok' } );
				var tokens = el( 'div', { 'class': 'slb-cs-tok__tokens' } );
				var search = el( 'input', { type: 'text', 'class': 'slb-cs-tok__search', placeholder: DATA.i18n.searchPosts, autocomplete: 'off' } );
				var sugg   = el( 'ul', { 'class': 'slb-cs-tok__suggest', role: 'listbox', hidden: 'hidden' } );
				var hidden = el( 'input', { type: 'hidden', name: 'groups[' + i + '][post_ids]', value: ( selectedIds || [] ).join( ',' ) } );

				function ids() { return hidden.value.split( ',' ).map( function ( s ) { return s.trim(); } ).filter( Boolean ); }
				function commit( a ) { hidden.value = a.join( ',' ); }
				function opts() { return DATA.postsByType[ type ] || {}; }

				function renderTokens() {
					tokens.innerHTML = '';
					var o = opts();
					ids().forEach( function ( id ) {
						var label = o[ id ] || ( '#' + id );
						var chip = el( 'span', { 'class': 'slb-cs-tok__token', text: label } );
						var x = el( 'button', { type: 'button', 'aria-label': DATA.i18n.remove, text: '×' } );
						x.addEventListener( 'click', function () { commit( ids().filter( function ( s ) { return s !== id; } ) ); renderTokens(); } );
						chip.appendChild( x );
						tokens.appendChild( chip );
					} );
				}
				function addId( id ) { var a = ids(); if ( a.indexOf( String( id ) ) === -1 ) { a.push( String( id ) ); commit( a ); renderTokens(); } }
				function close() { sugg.hidden = true; sugg.innerHTML = ''; }
				function open() {
					if ( ! type ) { sugg.innerHTML = ''; sugg.appendChild( el( 'li', { 'class': 'slb-cs-tok__empty', text: DATA.i18n.pickType } ) ); sugg.hidden = false; return; }
					var q = search.value.trim().toLowerCase();
					var chosen = ids();
					var o = opts();
					var matches = Object.keys( o ).filter( function ( id ) {
						if ( chosen.indexOf( id ) !== -1 ) { return false; }
						return o[ id ].toLowerCase().indexOf( q ) !== -1;
					} ).slice( 0, 20 );
					sugg.innerHTML = '';
					if ( ! matches.length ) {
						sugg.appendChild( el( 'li', { 'class': 'slb-cs-tok__empty', text: q ? DATA.i18n.noMatches : DATA.i18n.searchPosts } ) );
					} else {
						matches.forEach( function ( id ) {
							var li = el( 'li', { role: 'option', text: o[ id ] } );
							li.addEventListener( 'mousedown', function ( e ) { e.preventDefault(); addId( id ); search.value = ''; open(); search.focus(); } );
							sugg.appendChild( li );
						} );
					}
					sugg.hidden = false;
				}

				search.addEventListener( 'focus', open );
				search.addEventListener( 'input', open );
				search.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { close(); } } );
				wrap.addEventListener( 'mouseleave', close );

				wrap.appendChild( tokens );
				wrap.appendChild( search );
				wrap.appendChild( sugg );
				wrap.appendChild( hidden );

				// Expose a reset for when the post type changes.
				wrap._reset = function ( newType ) { type = newType; commit( [] ); renderTokens(); close(); };
				renderTokens();
				return wrap;
			}

			function buildGroup( group ) {
				var i = idx++;
				group = group || { post_type: '', post_ids: [], overrides: {} };
				var ov = group.overrides || {};

				var card = el( 'div', { 'class': 'slb-cs-group' } );

				// Header (remove button).
				var head = el( 'div', { 'class': 'slb-cs-group__head' } );
				var rm = el( 'button', { type: 'button', 'class': 'button-link-delete', text: DATA.i18n.remove } );
				rm.addEventListener( 'click', function () { card.remove(); } );
				head.appendChild( rm );
				card.appendChild( head );

				// Post type select.
				var typeSel = el( 'select', { name: 'groups[' + i + '][post_type]' } );
				Object.keys( DATA.postTypes ).forEach( function ( pt ) {
					var o = el( 'option', { value: pt, text: DATA.postTypes[ pt ] } );
					if ( pt === group.post_type ) { o.setAttribute( 'selected', 'selected' ); }
					typeSel.appendChild( o );
				} );
				var typeField = el( 'div', { 'class': 'slb-cs-field' }, [ el( 'label', { text: DATA.i18n.postType } ), typeSel ] );

				// Posts picker.
				var picker = buildPicker( i, group.post_type, ( group.post_ids || [] ).map( String ) );
				var postsField = el( 'div', { 'class': 'slb-cs-field slb-cs-field--posts' }, [ el( 'label', { text: DATA.i18n.posts } ), picker ] );

				typeSel.addEventListener( 'change', function () { picker._reset( typeSel.value ); } );

				// Override fields.
				var durInput = el( 'input', { type: 'number', min: '0', step: '0.5', name: 'groups[' + i + '][overrides][slideshow_duration]', placeholder: DATA.i18n.siteDefault, value: ( ov.slideshow_duration != null ? ov.slideshow_duration : '' ) } );
				var durField = el( 'div', { 'class': 'slb-cs-field' }, [ el( 'label', { text: DATA.i18n.duration } ), durInput ] );

				var autoSel = el( 'select', { name: 'groups[' + i + '][overrides][slideshow_autostart]' } );
				[ [ '', DATA.i18n.siteDefault ], [ '1', DATA.i18n.on ], [ '0', DATA.i18n.off ] ].forEach( function ( pair ) {
					var o = el( 'option', { value: pair[0], text: pair[1] } );
					if ( String( ov.slideshow_autostart != null ? ov.slideshow_autostart : '' ) === pair[0] ) { o.setAttribute( 'selected', 'selected' ); }
					autoSel.appendChild( o );
				} );
				var autoField = el( 'div', { 'class': 'slb-cs-field' }, [ el( 'label', { text: DATA.i18n.autostart } ), autoSel ] );

				var transSel = el( 'select', { name: 'groups[' + i + '][overrides][ui_transition_type]' } );
				var transChoices = [ [ '', DATA.i18n.siteDefault ] ].concat( DATA.transitions );
				transChoices.forEach( function ( pair ) {
					var o = el( 'option', { value: pair[0], text: pair[1] } );
					if ( String( ov.ui_transition_type != null ? ov.ui_transition_type : '' ) === pair[0] ) { o.setAttribute( 'selected', 'selected' ); }
					transSel.appendChild( o );
				} );
				var transField = el( 'div', { 'class': 'slb-cs-field' }, [ el( 'label', { text: DATA.i18n.transition } ), transSel ] );

				var row = el( 'div', { 'class': 'slb-cs-group__row' }, [ typeField, durField, autoField, transField ] );
				card.appendChild( row );
				card.appendChild( postsField );
				groupsEl.appendChild( card );
			}

			addBtn.addEventListener( 'click', function () { buildGroup(); } );

			( DATA.groups && DATA.groups.length ? DATA.groups : [] ).forEach( buildGroup );
		} )();
		</script>
		<?php
	}

	/* -- Save -- */

	/**
	 * Persist submitted groups. Sanitizes each group to a strict shape; blank
	 * override fields are dropped so they fall through to the site default.
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access Denied', 'simple-lightbox-for-wordpress' ) );
		}
		check_admin_referer( self::NONCE );

		$raw   = isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ? wp_unslash( $_POST['groups'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$clean = array();

		foreach ( $raw as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$post_type = isset( $group['post_type'] ) ? sanitize_key( $group['post_type'] ) : '';
			$post_ids  = array();
			if ( ! empty( $group['post_ids'] ) ) {
				foreach ( explode( ',', (string) $group['post_ids'] ) as $pid ) {
					$pid = absint( $pid );
					if ( $pid > 0 ) {
						$post_ids[] = $pid;
					}
				}
			}
			$post_ids = array_values( array_unique( $post_ids ) );

			// Skip empty groups (no posts assigned).
			if ( empty( $post_type ) || empty( $post_ids ) ) {
				continue;
			}

			$overrides    = array();
			$raw_override = isset( $group['overrides'] ) && is_array( $group['overrides'] ) ? $group['overrides'] : array();

			if ( isset( $raw_override['slideshow_duration'] ) && '' !== trim( (string) $raw_override['slideshow_duration'] ) ) {
				$overrides['slideshow_duration'] = max( 0, (float) $raw_override['slideshow_duration'] );
			}
			if ( isset( $raw_override['slideshow_autostart'] ) && '' !== $raw_override['slideshow_autostart'] ) {
				$overrides['slideshow_autostart'] = ( '1' === (string) $raw_override['slideshow_autostart'] );
			}
			if ( isset( $raw_override['ui_transition_type'] ) && '' !== trim( (string) $raw_override['ui_transition_type'] ) ) {
				$overrides['ui_transition_type'] = sanitize_key( $raw_override['ui_transition_type'] );
			}

			$clean[] = array(
				'post_type' => $post_type,
				'post_ids'  => $post_ids,
				'overrides' => $overrides,
			);
		}

		update_option( self::OPTION, $clean );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* -- Front-end interception -- */

	/**
	 * Overlay a matching group's overrides onto the global client options.
	 * No-op unless on a singular view whose post is assigned to a group.
	 *
	 * @param array $options Global client options from SLB_Options.
	 * @return array
	 */
	public function filter_client_options( $options ) {
		if ( ! is_singular() ) {
			return $options;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return $options;
		}

		foreach ( $this->get_groups() as $group ) {
			if ( empty( $group['post_ids'] ) || ! in_array( $post_id, array_map( 'absint', $group['post_ids'] ), true ) ) {
				continue;
			}
			if ( ! empty( $group['overrides'] ) && is_array( $group['overrides'] ) ) {
				foreach ( $group['overrides'] as $key => $value ) {
					if ( array_key_exists( $key, $this->fields ) ) {
						$options[ $key ] = $value;
					}
				}
			}
			break; // First matching group wins.
		}

		return $options;
	}

	/* -- Data helpers -- */

	/**
	 * Public post types as slug => label (excludes attachments).
	 *
	 * @return array<string,string>
	 */
	protected function get_public_post_types() {
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$choices = array();
		foreach ( $types as $slug => $obj ) {
			if ( 'attachment' === $slug ) {
				continue;
			}
			$choices[ $slug ] = $obj->labels->singular_name ? $obj->labels->singular_name : $obj->label;
		}
		return $choices;
	}

	/**
	 * Build a post-id => title map per post type for the type-filtered picker.
	 *
	 * @param array $type_slugs Post type slugs.
	 * @return array<string,array<int,string>>
	 */
	protected function get_posts_by_type( $type_slugs ) {
		$out = array();
		foreach ( $type_slugs as $slug ) {
			$out[ $slug ] = array();
			$posts        = get_posts(
				array(
					'post_type'      => $slug,
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'numberposts'    => 500,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'suppress_filters' => false,
				)
			);
			foreach ( $posts as $post ) {
				$out[ $slug ][ (string) $post->ID ] = $post->post_title ? $post->post_title : ( '#' . $post->ID );
			}
		}
		return $out;
	}

	/**
	 * Transition choices [value, label].
	 *
	 * Sourced from the global `ui_transition_type` option definition (see the
	 * options array in SLB_Lightbox::_options(), controller.php) so the list
	 * never drifts from the values the lightbox actually supports.
	 *
	 * @return array<int,array<int,string>>
	 */
	protected function get_transition_choices() {
		$choices = array();

		if ( isset( $GLOBALS['slb'] ) && is_object( $GLOBALS['slb'] ) && isset( $GLOBALS['slb']->options ) ) {
			$option = $GLOBALS['slb']->options->get( 'ui_transition_type' );
			if ( is_object( $option ) && method_exists( $option, 'get_data' ) ) {
				$items = $option->get_data( 'options' );
				if ( is_array( $items ) ) {
					foreach ( $items as $value => $label ) {
						$choices[] = array( (string) $value, (string) $label );
					}
				}
			}
		}

		// Fallback to the known set if the option could not be resolved.
		if ( empty( $choices ) ) {
			$choices = array(
				array( 'fade', __( 'Fade', 'simple-lightbox-for-wordpress' ) ),
				array( 'slide', __( 'Slide', 'simple-lightbox-for-wordpress' ) ),
			);
		}

		return $choices;
	}
}
