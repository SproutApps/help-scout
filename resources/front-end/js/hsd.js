var $ = jQuery.noConflict();
jQuery(function($) {
	
	$(window).load(function(){
	});

	$(document).ready(function($){
		var $shortcode_html = $("#hsd_conversations_table");

		if ( hsd_js_object.redactor ) {
			$('#hsd_message').redactor({
				linkSize: 1000,
				minHeight: 200 // pixels
			});
		}

		$('#hsd_message_form').on('submit', function(e) {
			var $button = $('#hsd_submit');
			$button.hide();
			$button.after('<span class="submit_loading loading"></span>');
			
		});

		if ( hsd_js_object.conversation_id === undefined ) {
			$.ajax({
				url: hsd_js_object.admin_ajax,
				type: "POST",
    			xhrFields: {
					withCredentials: true
				},
				data: {
					action: 'hsd_shortcodes',
					type: 'conversation_table',
					mid: $shortcode_html.data( 'mailbox-id' ),
					refresh_data: hsd_js_object.refresh_data,
					current_page: hsd_js_object.current_page,
					shortcodes_nonce: hsd_js_object.sec,
					post_id: hsd_js_object.post_id,
				},
				success: function(result){
					$shortcode_html.removeClass('loading');
					$shortcode_html.append( result );
					$shortcode_html.trigger( 'hsd_success_conversations_table', [ result ] );
				}
			});
		}
		else {
			$.ajax({
				url: hsd_js_object.admin_ajax,
				type: "POST",
    			xhrFields: {
					withCredentials: true
				},
				data: {
					action: 'hsd_shortcodes',
					type: 'single_conversation',
					mid: $shortcode_html.data( 'mailbox-id' ),
					refresh_data: hsd_js_object.refresh_data,
					current_page: hsd_js_object.current_page,
					shortcodes_nonce: hsd_js_object.sec,
					post_id: hsd_js_object.post_id,
					conversation_id: hsd_js_object.conversation_id
				},
				success: function(result){
					$shortcode_html.trigger( 'hsd_success_conversation', [ result ] );
					$shortcode_html.removeClass('loading');
					$shortcode_html.append( result );

					$('.conversation_body .message').readmore({
						speed: 75,
						maxHeight: 100,
						moreLink: '<a href="#" class="button-readmore">'+hsd_js_object.readmore+'</a>',
						lessLink: '<a href="#" class="button-readmore">'+hsd_js_object.close+'</a>'
					});

					var $item_status = $('#hsd_support_conversation').data( 'item-status' );
					if ( $item_status === 'closed' ) {
						$('#close_thread_check').hide();
					};

					// convert gist links asynch with gist embeds
					$('.conversation_body a[href*="gist.github.com"]').each(function () {
						var link = $(this);
						var href = $(this).attr('href');
						var id = href.substr(href.lastIndexOf('/') + 1);

						link.html("<p>Loading Gist...</p>");

						$.ajax({
								url: "https://gist.github.com/" + id + ".json",
								dataType: "jsonp",
								cache: true,
								success: function (data) {
										if (data && data.div) {
												if (!$("link[href='" + data.stylesheet + "']").length) {
														$("head").append("<link href=\"" + data.stylesheet + "\" rel=\"stylesheet\" />");
												}
												link.html(data.div);
										}
								},
								error: function () {
										link.html("<p>Gist Load Failed</p>");
								}
						});
					});
					$shortcode_html.trigger( 'hsd_post_success_conversation', [ result ] );
				}
			});
		};
	});

});



/*!
 * Readmore.js jQuery plugin
 * Author: @jed_foster
 * Project home: jedfoster.github.io/Readmore.js
 * Licensed under the MIT license
 */

;(function($) {

	var readmore = 'readmore',
			defaults = {
				speed: 100,
				maxHeight: 200,
				heightMargin: 16,
				moreLink: '<a href="#">Read More</a>',
				lessLink: '<a href="#">Close</a>',
				embedCSS: true,
				sectionCSS: 'display: block; width: 100%;',
				startOpen: false,
				expandedClass: 'readmore-js-expanded',
				collapsedClass: 'readmore-js-collapsed',

				// callbacks
				beforeToggle: function(){},
				afterToggle: function(){}
			},
			cssEmbedded = false;

	function Readmore( element, options ) {
		this.element = element;

		this.options = $.extend( {}, defaults, options);

		$(this.element).data('max-height', this.options.maxHeight);
		$(this.element).data('height-margin', this.options.heightMargin);

		delete(this.options.maxHeight);

		if(this.options.embedCSS && ! cssEmbedded) {
			var styles = '.readmore-js-toggle, .readmore-js-section { ' + this.options.sectionCSS + ' } .readmore-js-section { overflow: hidden; }';

			(function(d,u) {
				var css=d.createElement('style');
				css.type = 'text/css';
				if(css.styleSheet) {
						css.styleSheet.cssText = u;
				}
				else {
						css.appendChild(d.createTextNode(u));
				}
				d.getElementsByTagName('head')[0].appendChild(css);
			}(document, styles));

			cssEmbedded = true;
		}

		this._defaults = defaults;
		this._name = readmore;

		this.init();
	}

	Readmore.prototype = {

		init: function() {
			var $this = this;

			$(this.element).each(function() {
				var current = $(this),
						maxHeight = (current.css('max-height').replace(/[^-\d\.]/g, '') > current.data('max-height')) ? current.css('max-height').replace(/[^-\d\.]/g, '') : current.data('max-height'),
						heightMargin = current.data('height-margin');

				if(current.css('max-height') != 'none') {
					current.css('max-height', 'none');
				}

				$this.setBoxHeight(current);

				if(current.outerHeight(true) <= maxHeight + heightMargin) {
					// The block is shorter than the limit, so there's no need to truncate it.
					return true;
				}
				else {
					current.addClass('readmore-js-section ' + $this.options.collapsedClass).data('collapsedHeight', maxHeight);

					var useLink = $this.options.startOpen ? $this.options.lessLink : $this.options.moreLink;
					current.after($(useLink).on('click', function(event) { $this.toggleSlider(this, current, event) }).addClass('readmore-js-toggle'));

					if(!$this.options.startOpen) {
						current.css({height: maxHeight});
					}
				}
			});

			$(window).on('resize', function(event) {
				$this.resizeBoxes();
			});
		},

		toggleSlider: function(trigger, element, event)
		{
			event.preventDefault();

			var $this = this,
					newHeight = newLink = sectionClass = '',
					expanded = false,
					collapsedHeight = $(element).data('collapsedHeight');

			if ($(element).height() <= collapsedHeight) {
				newHeight = $(element).data('expandedHeight') + 'px';
				newLink = 'lessLink';
				expanded = true;
				sectionClass = $this.options.expandedClass;
			}

			else {
				newHeight = collapsedHeight;
				newLink = 'moreLink';
				sectionClass = $this.options.collapsedClass;
			}

			// Fire beforeToggle callback
			$this.options.beforeToggle(trigger, element, expanded);

			$(element).animate({'height': newHeight}, {duration: $this.options.speed, complete: function() {
					// Fire afterToggle callback
					$this.options.afterToggle(trigger, element, expanded);

					$(trigger).replaceWith($($this.options[newLink]).on('click', function(event) { $this.toggleSlider(this, element, event) }).addClass('readmore-js-toggle'));

					$(this).removeClass($this.options.collapsedClass + ' ' + $this.options.expandedClass).addClass(sectionClass);
				}
			});
		},

		setBoxHeight: function(element) {
			var el = element.clone().css({'height': 'auto', 'width': element.width(), 'overflow': 'hidden'}).insertAfter(element),
					height = el.outerHeight(true);

			el.remove();

			element.data('expandedHeight', height);
		},

		resizeBoxes: function() {
			var $this = this;

			$('.readmore-js-section').each(function() {
				var current = $(this);

				$this.setBoxHeight(current);

				if(current.height() > current.data('expandedHeight') || (current.hasClass($this.options.expandedClass) && current.height() < current.data('expandedHeight')) ) {
					current.css('height', current.data('expandedHeight'));
				}
			});
		},

		destroy: function() {
			var $this = this;

			$(this.element).each(function() {
				var current = $(this);

				current.removeClass('readmore-js-section ' + $this.options.collapsedClass + ' ' + $this.options.expandedClass).css({'max-height': '', 'height': 'auto'}).next('.readmore-js-toggle').remove();

				current.removeData();
			});
		}
	};

	$.fn[readmore] = function( options ) {
		var args = arguments;
		if (options === undefined || typeof options === 'object') {
			return this.each(function () {
				if ($.data(this, 'plugin_' + readmore)) {
					var instance = $.data(this, 'plugin_' + readmore);
					instance['destroy'].apply(instance);
				}

				$.data(this, 'plugin_' + readmore, new Readmore( this, options ));
			});
		} else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
			return this.each(function () {
				var instance = $.data(this, 'plugin_' + readmore);
				if (instance instanceof Readmore && typeof instance[options] === 'function') {
					instance[options].apply( instance, Array.prototype.slice.call( args, 1 ) );
				}
			});
		}
	}
})(jQuery);