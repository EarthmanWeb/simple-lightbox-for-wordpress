if ( !!window.SLB && SLB.has_child('View.extend_theme') ) {(function($) {
SLB.View.extend_theme('slb_default', {
	/**
	 * Transition event handlers
	 */
	'transition': {
		/**
		 * Open event
		 * @param View.Viewer v Viewer instance
		 * @param deferred dfr Resolved when transition is complete
		 * @return promise Resolved when transition is complete
		 */
		'open': function(v, dfr) {
			// Reset layout and overlay state on open
			var l = v.get_layout().hide(),
				o = v.get_overlay().hide();

			// Clean UI
			var thm = this;
			var d = v.dom_get();
			d.find('.slb_content').css({width: '', height: ''}).find(this.get_tag_selector()).hide();
			d.find('.slb_details').height(0);
			// Show viewer DOM
			d.show({'always': function() {
				var pos = {'top_base': $(document).scrollTop()};
				if ( document.documentElement.clientWidth > thm.get_breakpoint('small') ) {
					/* Standard screen */
					// Center vertically
					pos.top = ( pos.top_base + $(window).height() / 2 ) - ( l.height() / 2 );
					if ( pos.top < pos.top_base ) {
						pos.top = pos.top_base;
					}
				} else {
					/* Small screen */
					// Position at top
					pos.top = pos.top_base;
				}
				// Show overlay
				o.fadeIn({'always': function() {
					// Position lightbox
					l.css(pos);
					dfr.resolve();
				}});
			}});
			return dfr.promise();
		},
		/**
		 * Close event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'close': function(v, dfr) {
			// Viewer elements
			var l = v.get_layout(),
				c = l.find('.slb_content');
			// Reset procedure
			var reset = function() {
				// Reset state
				c.width('').height('');
				l.css('opacity', '');
				dfr.resolve();
			};
			if ( v.animation_enabled() && document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				/* Standard */
				var anims = {
					'layout': { opacity: 0, top: $(document).scrollTop() + ( $(window).height() / 2 ) },
					'content': { width: 0, height: 0 },
					'speed': 'fast'
				};
				// Shrink & fade out viewer
				var pos = l.animate(anims.layout, anims.speed).promise();
				var size = c.animate(anims.content, anims.speed).promise();
				$.when(pos, size).done(function() {
					// Fade out overlay
					v.get_overlay().fadeOut({'always': function() {
						reset();
					}});
				});
			} else {
				l.css('opacity', 0);
				reset();
			}
			return dfr.promise();
		},
		/**
		 * Item loading event
		 * @param View.Viewer v Viewer instance
		 * @param deferred dfr Resolved when animation is complete
		 * @return promise Resolved when transition is complete
		 */
		'load': function(v) {
			// Only show spinner on initial open, not during navigation
			if ( !v.init ) {
				v.get_layout().find('.slb_loading').show();
			}
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				return v.get_layout().fadeIn().promise();
			} else {
				return v.get_layout().show().promise();
			}
		},
		/**
		 * Item unloaded event
		 * Keep old content visible for smooth cross-fade/slide transitions
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'unload': function(v, dfr) {
			var l = v.get_layout(),
				det = l.find('.slb_details'),
				c = l.find('.slb_content');
			// Collapse details
			det.css({height: 0});
			// Create snapshot from image data saved by Viewer.unload()
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				var imgData = v.get_attribute('_transition_img');
				if ( imgData && imgData.src ) {
					$('<img>', {
						'src': imgData.src,
						'class': 'slb_transition_old'
					}).css({
						position: 'absolute',
						top: 0,
						left: 0,
						width: imgData.width,
						height: imgData.height,
						'z-index': 3,
						'object-fit': 'contain'
					}).appendTo(c);
					v.set_attribute('_transition_img', null);
				}
			}
			dfr.resolve();
			return dfr.promise();
		},
		/**
		 * Item loading completed event
		 * @param View.Viewer v Viewer instance
		 * @param jQuery.Deferred dfr Deferred instance to be resolved when animation is complete
		 * @return jQuery.Promise Resolved when transition is complete
		 */
		'complete': function(v, dfr) {
			// Elements
			var l = v.get_layout(),
				loader = l.find('.slb_loading'),
				det = l.find('.slb_details'),
				det_data = det.find('.slb_data'),
				c = l.find('.slb_content'),
				c_tag = c.find( this.get_tag_selector() );
			// Transition
			if ( document.documentElement.clientWidth > this.get_breakpoint('small') ) {
				var trans_speed = 400;
				var transition_type = v.get_attribute('transition_type', 'fade');
				var thm = this;

				// Check for displayable content using data values
				var item = v.get_item();
				var has_title = item && item.get_title && !!item.get_title();
				var has_group_status = v.get_attribute('group_status', true) && item && item.get_group && !item.get_group().is_single();
				var det_has_content = has_title || has_group_status;

				// Resize viewer to fit item
				var dims_item = this.get_item_dimensions();
				var dims_det = {'height': 0};
				if ( det_has_content ) {
					det.width(dims_item.width);
					dims_det.height = det_data.outerHeight();
					det.width('');
				}

				// Vertical positioning (centered)
				var pos = { 'top_base': $(document).scrollTop() };
				pos.top = pos.top_base + ( $(window).height() / 2 ) - ( ( dims_det.height + dims_item.height ) / 2 );
				if ( pos.top < pos.top_base ) {
					pos.top = pos.top_base;
				}

				// Hide loading indicator
				loader.hide();

				// Get transition elements
				var content_tag = c.find( thm.get_tag_selector('item', 'content') );
				var old_img = c.find('.slb_transition_old');

				// Run container resize and content transition in parallel
				var resize_done = $.when(
					l.animate(pos, trans_speed).promise(),
					c.animate(dims_item, trans_speed).promise()
				);
				var content_done = $.Deferred();

				if ( old_img.length ) {
					if ( transition_type === 'slide' ) {
						// Slide direction follows navigation: 'next' moves content leftward
						// (old exits left, new enters from right); 'prev' reverses it
						// (old exits right, new enters from left).
						var nav_dir = v.get_attribute('_nav_direction', 'next');
						var new_start = ( nav_dir === 'prev' ) ? '-100%' : '100%';
						var old_exit  = ( nav_dir === 'prev' ) ? '100%' : '-100%';
						// Position new content off-screen on the entry side, then slide both in parallel
						content_tag.css({position: 'absolute', top: 0, left: new_start, width: '100%', 'z-index': 2}).show();
						old_img.animate({left: old_exit}, trans_speed);
						content_tag.animate({left: '0%'}, trans_speed, function() {
							content_tag.css({position: '', top: '', left: '', width: '', 'z-index': ''});
							old_img.remove();
							content_done.resolve();
						});
					} else {
						// Cross-fade: old (z-index 3) fades out, revealing new beneath
						content_tag.css({'z-index': 1}).show();
						old_img.animate({opacity: 0}, trans_speed, function() {
							content_tag.css({'z-index': ''});
							old_img.remove();
							content_done.resolve();
						});
					}
				} else {
					// First open — simple fade in
					content_tag.fadeIn(trans_speed, function() {
						content_done.resolve();
					});
				}

				// Show details after transitions complete
				$.when(resize_done, content_done.promise()).done(function() {
					c_tag.show();
					if ( det_has_content ) {
						det.animate({'height': det_data.outerHeight()}, 'slow').promise().done(function() {
							det.height('');
							dfr.resolve();
						});
					} else {
						dfr.resolve();
					}
				});
			} else {
				loader.hide();
				c_tag.show();
				det.height('');
				dfr.resolve();
			}
			return dfr.promise();
		}
	}
});
})(jQuery);
}