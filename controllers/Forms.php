<?php


/**
 * Help Scout API Controller
 *
 * @package Help_Scout_Desk
 * @subpackage Help
 */
class HSD_Forms extends HSD_Controller {
	const SUBMISSION_SUCCESS_QV = 'thread_success';
	const SUBMISSION_ERROR_QV = 'submission_error';
	const FORM_SHORTCODE = 'hsd_form';
	const FORM_SHORTCODE_DEP = 'hds_form'; // deprecated typo

	public static function init() {
		do_action( 'hsd_shortcode', self::FORM_SHORTCODE_DEP, array( __CLASS__, 'submission_form' ) );
		do_action( 'hsd_shortcode', self::FORM_SHORTCODE, array( __CLASS__, 'submission_form' ) );
		// process conversation form
		add_action( 'parse_request', array( __CLASS__, 'maybe_process_form' ) );

		// refresh data after submission
		add_filter( 'hsd_scripts_localization', array( __CLASS__, 'add_refresh_qv' ) );
	}

	/**
	 * Show the reply/creation form
	 * @param  array $atts
	 * @param  string $content used to show a message after a message is received.
	 * @return
	 */
	public static function submission_form( $atts, $content = '' ) {
		if ( ! HelpScout_API::is_customer() ) {
			do_action( 'helpscout_desk_sc_form_not_customer' );
			return;
		}

		if ( '' === $content ) {
			$content = sprintf( esc_html__( 'Thank you, message received. <a href="%s">Send another message</a>.', 'help-scout-desk' ), remove_query_arg( self::SUBMISSION_SUCCESS_QV ) );
		}

		// Don't show the form if not on the conversation view
		if ( isset( $_GET[ self::SUBMISSION_SUCCESS_QV ] ) && $_GET[ self::SUBMISSION_SUCCESS_QV ] ) {
			return self::load_view_to_string( 'shortcodes/success_message', array(
				'message' => $content,
			), true );
		}
		$error = false;
		if ( isset( $_GET[ self::SUBMISSION_ERROR_QV ] ) && $_GET[ self::SUBMISSION_ERROR_QV ] ) {
			$error = urldecode( $_GET[ self::SUBMISSION_ERROR_QV ] );
		}

		$mailbox_id = ( isset( $atts['mid'] ) ) ? $atts['mid'] : HSD_Settings::get_mailbox();

		// Show the form
		wp_enqueue_script( 'hsd' );
		wp_enqueue_style( 'hsd' );
		return self::load_view_to_string( 'shortcodes/conversation_form', array(
				'nonce' => wp_create_nonce( HSD_Controller::NONCE ),
				'mid' => $mailbox_id,
				'error' => $error,
				'conversation_view' => ( isset( $_GET['conversation_id'] ) && $_GET['conversation_id'] != '' ),
		), true );
	}


	/**
	 * Maybe process the submission
	 * @return
	 */
	public static function maybe_process_form() {
		$nonce_value = ( isset( $_REQUEST['hsd_nonce'] ) ) ? $_REQUEST['hsd_nonce'] : false ;
		if ( ! $nonce_value ) {
			return;
		}

		if ( ! wp_verify_nonce( $nonce_value, HSD_Controller::NONCE ) ) {
			return;
		}

		do_action( 'hsd_submission_form' );

		$error = false;
		if ( ! isset( $_POST['message'] ) || $_POST['message'] == '' ) {
			$error = esc_html__( 'Message Required.', 'help-scout-desk' );
		}
		if ( ! isset( $_GET['conversation_id'] ) ) {
			if ( ! isset( $_POST['subject'] ) || $_POST['subject'] == '' ) {
				$error = esc_html__( 'Subject Required.', 'help-scout-desk' );
			}
		}
		if ( ! $error ) {
			$success = self::process_form_submission();
			if ( $success === true ) {
				$redirect_url = null;
				do_action( 'hsd_form_submitted_without_error', $success );
				wp_redirect( remove_query_arg( self::SUBMISSION_ERROR_QV, add_query_arg( self::SUBMISSION_SUCCESS_QV, true ), esc_url_raw( apply_filters( 'si_hsd_thread_submitted_error_redirect_url', $redirect_url ) ) ) );
				exit();
			}
			// toggle return message
			$error = $success;
		}
		$redirect_url = null;
		do_action( 'hsd_form_submitted_with_error', $success );
		wp_redirect( remove_query_arg( self::SUBMISSION_SUCCESS_QV, add_query_arg( self::SUBMISSION_ERROR_QV, urlencode( $error ) ), esc_url_raw( apply_filters( 'si_hsd_thread_submitted_redirect_url', $redirect_url ) ) ) );
		exit();

	}

	/**
	 * Process the form submission
	 * @return
	 */
	public static function process_form_submission() {
		$attachments = array();
		if ( ! empty( $_FILES ) && isset( $_FILES['message_attachment'] ) ) {
			$attach_count = count( $_FILES['message_attachment']['name'] );
			for ( $n = 0; $n < $attach_count; $n++ ) {
				$file_data = array(
					'name' => $_FILES['message_attachment']['name'][ $n ],
					'type' => $_FILES['message_attachment']['type'][ $n ],
					'tmp_name' => $_FILES['message_attachment']['tmp_name'][ $n ],
					'error' => $_FILES['message_attachment']['error'][ $n ],
					'size' => $_FILES['message_attachment']['size'][ $n ],
				);
				$attachment = HelpScout_API::create_attachment( $file_data, esc_attr( $_POST['mid'], 'help-scout-desk' ) );
				if ( $attachment !== false ) {
					$attachments[] = (array) $attachment;
				}
			}
		}
		if ( isset( $_POST['hsd_conversation_id'] ) && $_POST['hsd_conversation_id'] != '' ) {
			do_action( 'hsd_form_submitted_to_create_thread' );
			$new_status = ( isset( $_POST['close_thread'] ) ) ? 'closed' : 'active' ;
			$new_thread = HelpScout_API::create_thread( $_GET['conversation_id'], stripslashes( $_POST['message'] ), $new_status, esc_attr( $_POST['mid'], 'help-scout-desk' ), $attachments );
		} else {
			do_action( 'hsd_form_submitted_to_create_conversation' );
			$new_thread = HelpScout_API::create_conversation( stripslashes( $_POST['subject'] ), stripslashes( $_POST['message'] ), esc_attr( $_POST['mid'], 'help-scout-desk' ), $attachments );
		}
		return apply_filters( 'hsd_process_form_submission_new_thread', $new_thread );
	}

	/**
	 * Add the conversation id to the js object
	 * @param array $hsd_js_object
	 */
	public static function add_refresh_qv( $hsd_js_object ) {
		$hsd_js_object['refresh_data'] = 0;
		$hsd_js_object['current_page'] = max( 1, absint( get_query_var( 'page' ) ) );
		if ( isset( $_GET[ self::SUBMISSION_SUCCESS_QV ] ) && $_GET[ self::SUBMISSION_SUCCESS_QV ] ) {
			$hsd_js_object['refresh_data'] = 1;
		}
		return $hsd_js_object;
	}
}
