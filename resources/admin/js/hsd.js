jQuery.noConflict();

jQuery(function($) {

	/**
	 * License Activation
	 */
	$('#hsd_activate_license').on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $button = $( this ),
			$license_key = $('#hsd_license_key').val(),
			$license_message = $('#license_message');

		$button.hide();
		$button.after('<span class="spinner si_inline_spinner" style="visibility:visible;display:inline-block;"></span>');
		$.post( ajaxurl, { action: 'hsd_activate_license', license: $license_key, security: hsd_js_object.sec },
			function( data ) {
				if ( data.error ) {
					$button.show();
					$license_message.html('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					$license_message.html('<span class="inline_success_message">' + data.response + '</span>');
				}
				$('.spinner').hide();
			}
		);
	});

	/**
	 * License Deactivation
	 */
	$('#hsd_deactivate_license').on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $button = $( this ),
			$activate_button = $('#hsd_activate_license');
			$license_key = $('#hsd_license_key').val(),
			$license_message = $('#license_message');

		$button.hide();
		$button.after('<span class="spinner si_inline_spinner" style="visibility:visible;display:inline-block;"></span>');
		$.post( ajaxurl, { action: 'hsd_deactivate_license', license: $license_key, security: hsd_js_object.sec },
			function( data ) {
				if ( data.error ) {
					$button.show();
					$license_message.html('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					$activate_button.hide();
					$activate_button.removeAttr('disabled').addClass('button-primary').fadeIn();
					$license_message.html('<span class="inline_success_message">' + data.response + '</span>');
				}
				$('.spinner').hide();
			}
		);
	});


});