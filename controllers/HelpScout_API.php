<?php

/**
 * Help Scout API Controller
 *
 * @package HelpScout_Desk
 * @subpackage HS API
 */
class HelpScout_API extends HSD_Controller {
	const API_ENDPOINT = 'https://api.helpscout.net/v2/';
	const NONCE = 'hsd_api_nonce';
	const CUSTOMER_IDS = 'hsd_customer_ids_v3';
	const CACHE_KEY_PREFIX = 'hsd_api_cache_';
	const CACHE_TIMEOUT = 5 * MINUTE_IN_SECONDS;

	const REDIRECT_QV = 'hs_oauth_verification';
	const TOKEN_TRANS = 'hs_oauthtoken';

	private static $mailbox;
	private static $api;


	public static function init() {
		self::$mailbox = HSD_Settings::get_mailbox();
		add_action( 'wp_ajax_hsd_reset_customer_ids', array( __CLASS__, 'maybe_reset_customer_ids' ) );
	}

	public static function get_oath_token() {
		$token = get_transient( self::TOKEN_TRANS );
		if ( ! $token ) {
			$token = self::maybe_refresh_token();
		}
		return $token;
	}

	public static function save_oauth_token( $data = object ) {
		set_transient( self::TOKEN_TRANS, $data->access_token, (int) $data->expires_in );
		return;
	}

	///////////
	// oAuth //
	///////////

	public static function maybe_refresh_token() {
		$auth = array(
			'client_id' => HSD_Settings::get_app_id(),
			'client_secret' => HSD_Settings::get_secret(),
			'grant_type' => 'client_credentials',
		);
		$params = array(
			'method' => 'POST',
			'sslverify' => false,
			'timeout' => 15,
			'body' => $auth,
		 );

		$raw_response = wp_remote_post( self::API_ENDPOINT . 'oauth2/token', $params );
		$response = wp_remote_retrieve_body( $raw_response );
		$data = json_decode( $response );

		self::save_oauth_token( $data );
		return $data->access_token;
	}


	public static function get_redirect_url() {
		return add_query_arg( array( self::REDIRECT_QV => '1' ), home_url() );
	}

	/////////////////
	// API Helpers //
	/////////////////

	/**
	 * API Request from Help Scout API
	 * @param  string  $endpoint
	 * @param  string  $query    string of query vars
	 * @param  boolean $refresh  refresh cache
	 * @return object|string     API json, or string if error
	 */
	public static function api_request( $endpoint = '', $query = '', $refresh = false ) {

		// Build endpoint
		$endput_and_query = str_replace( self::API_ENDPOINT, '', $endpoint ) . $query;
		$api_url = apply_filters( 'hsd_v2_api_request_api_url', self::API_ENDPOINT . $endput_and_query, $endput_and_query, $query, $refresh );

		// Return cache if present.
		$cache = self::get_cache( $api_url );
		if ( $cache && ! $refresh ) {
			return $cache;
		}

		// Remote API request
		$params = apply_filters( 'hsd_v2_api_request_params', array(
			'method' => 'GET',
			'headers' => array(
					'Authorization' => 'Bearer ' . self::get_oath_token(),
				),
			'sslverify' => false,
			'timeout' => 15,
		), $endpoint, $api_url, $query, $refresh );

		$raw_response = wp_remote_request( $api_url, $params );
		$response = wp_remote_retrieve_body( $raw_response );
		$response = apply_filters( 'hsd_v2_api_request', $response, $endpoint, $api_url, $query, $refresh );

		// Error check
		$response_code = wp_remote_retrieve_response_code( $raw_response );

		if ( 200 !== $response_code ) {
			return $response;
		}

		// Set cache and return request
		self::set_cache( $api_url, $response );

		return $response;
	}

	public static function api_post( $endpoint = '', $data = array(), $method = 'POST' ) {

		$api_url = apply_filters( 'hsd_v2_api_post_api_url', self::API_ENDPOINT . $endpoint, $data, $method );

		// Remote API request
		$params = apply_filters( 'hsd_v2_api_post_params', array(
			'method' => $method,
			'headers' => array(
					'Authorization' => 'Bearer ' . self::get_oath_token(),
					'Accept'       => 'application/json',
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( wp_json_encode( $data ) ),
				),
			'sslverify' => false,
			'timeout' => 15,
			'body' => wp_json_encode( $data ),
		), $api_url, $data );

		$raw_response = wp_remote_post( $api_url, $params );
		$response = wp_remote_retrieve_body( $raw_response );

		$response = apply_filters( 'hsd_v2_api_post', $response, $api_url, $data, $method );

		if ( isset( $response->error ) ) {
			return $response->error;
		}

		return $raw_response;
	}



	////////////////////
	// Conversations //
	////////////////////


	public static function get_complete_conversation( $conversation_id = 0, $refresh = false ) {

		if ( ! $conversation_id ) {
			return false;
		}

		$con_and_threads = array();

		// Get conversation
		$conversation_response = self::api_request( 'conversations/' . $conversation_id, '?embed=threads', $refresh );
		$con_and_threads['conversation'] = json_decode( wp_json_encode( json_decode( $conversation_response ) ), true );

		// Security Check
		if ( ! in_array( $con_and_threads['conversation']['primaryCustomer']['id'], self::find_customer_ids() ) ) {
			wp_die( sprintf( '<span class="hsd_error">%s</span>', __( 'Customer ID Mismatch', 'help-scout-desk' ) ) );
		}

		// Does the API include the full primaryCustomer object yet?
		if ( isset( $con_and_threads['conversation']['primaryCustomer']['firstName'] ) ) {
			$con_and_threads['customer'] = $con_and_threads['conversation']['primaryCustomer'];
		} else {
			// if not get the full customer
			$query = $con_and_threads['conversation']['_links']['primaryCustomer']['href'];
			$customer_response = self::api_request( $query, '', $refresh );
			$con_and_threads['customer'] = json_decode( wp_json_encode( json_decode( $customer_response ) ), true );
		}

		// Does the callback include all the threads, some don't.
		if ( isset( $con_and_threads['conversation']['primaryCustomer']['firstName'] ) ) {

			$con_and_threads['threads'] = $con_and_threads['_embedded']['threads'];

		} else {

			// if not Get Threads
			$query = $con_and_threads['conversation']['_links']['threads']['href'];
			$threads_response = self::api_request( $query, '', $refresh );
			$threads_response = json_decode( wp_json_encode( json_decode( $threads_response ) ), true );

			if ( empty( $threads_response['_embedded']['threads'] ) ) {
				wp_die( sprintf( '<span class="hsd_error">%s</span>', __( 'EMPTY API RETURN', 'help-scout-desk' ) ) );
			}

			$con_and_threads['threads'] = $threads_response['_embedded']['threads'];
		}

		if ( apply_filters( 'hsd_hide_drafts_and_notes', true ) ) {

			foreach ( $con_and_threads['threads'] as $key => $thread ) {

				// remove non messages
				if ( $thread['type'] === 'note' ) {
					unset( $con_and_threads['threads'][ $key ] );
					continue;
				}

				// remove drafts
				if ( isset( $thread['state'] ) && $thread['state'] !== 'published' ) {
					unset( $con_and_threads['threads'][ $key ] );
					continue;
				}
			}
		}

		return apply_filters( 'hsd_get_complete_conversation', $con_and_threads, $conversation_id, $refresh );
	}

	public static function get_full_conversations_by_user( $customer_id = 0, $refresh = false, $mailbox_id = 0, $page = 1, $status = 'all' ) {
		if ( ! $customer_id ) {
			$customer_ids = self::find_customer_ids( $customer_id, $refresh );
		} else {
			$customer_ids = array( $customer_id );
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		// Get conversations
		$conversations = array();
		foreach ( $customer_ids as $customer_id ) {

			$query = sprintf( '?query=(customerIds:%2$s)&mailbox=%1$s&status=%4$s&sortField=modifiedAt&sortOrder=desc&page=%3$s', $mailbox_id, $customer_id, $page, $status );
			$response = self::api_request( 'conversations', $query, $refresh );
			$response = json_decode( $response );

			if ( ! empty( $response->_embedded->conversations ) ) {

				foreach ( $response->_embedded->conversations as $key => $data ) {

					$conversations[] = self::get_complete_conversation( $data->id );

				}
			}

			$conversations['page'] = $response->page;
		}

		return apply_filters( 'hsd_get_full_conversations_by_user', $conversations, $customer_id, $refresh );
	}


	public static function create_thread( $conversation_id = 0, $message = 0, $new_status = 'active', $mailbox_id = 0, $attachments_data = array() ) {

		if ( ! $conversation_id ) {
			return false;
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		if ( HSD_FREE ) {
			$message = wpautop( $message );
		}

		$customer_ids = self::find_customer_ids();

		// create a new customer if one wasn't found.
		$customer = ( empty( $customer_ids ) ) ? array( 'email' => self::find_email() ) : array( 'id' => $customer_ids[0] ) ;

		$fields = apply_filters( 'hsd_create_thread_fields', array(
				'customer' => $customer,
				'text' => $message,
				'attachments' => $attachments_data,
		) );

		$raw_response = self::api_post( 'conversations/'.$conversation_id.'/customer', $fields );

		$response_code = wp_remote_retrieve_response_code( $raw_response );

		if ( 201 !== $response_code ) {
			return false;
		}

		// TODO handle status update via update conversation

		// clear cache
		self::delete_conversation_cache( $conversation_id );
		self::delete_conversations_cache( $customer['id'], $mailbox_id );

		do_action( 'hsd_create_thread', $raw_response );
		return true;
	}


	public static function create_conversation( $subject = '', $message = '', $mailbox_id = 0, $attachments = array() ) {

		if ( '' === $message ) {
			return false;
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		if ( HSD_FREE ) {
			$message = wpautop( $message );
		}

		$customer_ids = self::find_customer_ids();

		// create a new customer if one wasn't found.
		$customer = ( empty( $customer_ids ) ) ? array( 'email' => self::find_email() ) : array( 'id' => $customer_ids[0] ) ;

		$fields = apply_filters( 'hsd_create_conversation_fields', array(
				'mailboxId' => $mailbox_id,
				'customer' => $customer,
				'type' => 'email',
				'status' => 'active',
				'subject' => $subject,
				'threads' => array( array( 'type' => 'customer', 'customer' => $customer, 'text' => $message, 'attachments' => $attachments ) ),
		) );

		$raw_response = self::api_post( 'conversations', $fields );

		$response_code = wp_remote_retrieve_response_code( $raw_response );

		if ( 201 !== $response_code ) {
			return false;
		}

		// clear cache
		if ( isset( $customer['id'] ) ) {
			self::delete_conversations_cache( $customer['id'], $mailbox_id );
		} elseif ( isset( $customer['email'] ) ) {
			self::delete_conversations_cache( $customer['email'], $mailbox_id );
		}

		do_action( 'hsd_create_conversation', $raw_response );
		return true;
	}


	////////////
	// Users //
	////////////

	public static function find_customer_ids( $user_id = 0, $refresh = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$customer_ids = self::get_user_customer_ids( $user_id );
		$customer_ids = apply_filters( 'hsd_find_customer_ids_pre', $customer_ids, $user_id, $refresh );
		// If no customer ids are assigned than try to find them.
		if ( empty( $customer_ids ) || $refresh ) {

			$customer_ids = self::find_customer_ids_by_email( self::find_email( $user_id ), $refresh );

			// Store the customer ids
			if ( ! empty( $customer_ids ) ) {
				self::set_user_customer_ids( $user_id, $customer_ids );
			}
		}
		return apply_filters( 'hsd_find_customer_ids', $customer_ids, $user_id, $refresh );
	}

	public static function is_customer() {
		return apply_filters( 'hsd_is_customer', true );
	}

	public static function find_email( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$user_email = '';
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			$user_email = $user->user_email;
		}
		return apply_filters( 'hsd_api_find_email', $user_email, $user_id );
	}

	/**
	 * Find customer id from an API request
	 * @param  string $email
	 * @return array
	 */
	public static function find_customer_ids_by_email( $email = '', $refresh = false ) {
		if ( $email === '' ) {
			return array();
		}
		// Query for the customers with the same email address
		$query = sprintf( '?query=(email:"%1$s")', $email, self::$mailbox );
		$response = self::api_request( 'customers', $query, $refresh );
		// convert to an array
		$response = json_decode( $response );
		// Loop through response and build an array of customer ids
		$customer_ids = array();
		if ( ! empty( $response->_embedded->customers ) ) {
			foreach ( $response->_embedded->customers as $key => $data ) {
				$customer_ids[] = $data->id;
			}
		}
		return $customer_ids;
	}

	public static function get_user_customer_ids( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$customer_ids = get_user_meta( $user_id, self::CUSTOMER_IDS, true );
		return apply_filters( 'hsd_get_user_customer_ids', $customer_ids, $user_id );
	}

	public static function set_user_customer_ids( $user_id = 0, $customer_ids = array() ) {
		if ( ! $user_id ) {
			$customer_ids = array();
			do_action( 'hsd_cant_set_user_customer_ids', $user_id, $customer_ids );
		} else {
			$customer_ids = update_user_meta( $user_id, self::CUSTOMER_IDS, $customer_ids );

			do_action( 'hsd_set_user_customer_ids', $user_id, $customer_ids );
		}
		return $customer_ids;
	}

	public static function maybe_reset_customer_ids() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'hsd_customer_ids_v3',
						'compare' => 'EXISTS',
					),
				),
			);
		$wp_user_query = new WP_User_Query( $args );
		$hsd_users = $wp_user_query->get_results();
		if ( ! is_array( $hsd_users ) ) {
			return;
		}
		foreach ( $hsd_users as $hsd_user ) {
			delete_user_meta( $hsd_user->ID, self::CUSTOMER_IDS );
		}

	}

	//////////////
	// Caching //
	//////////////

	public static function get_cache_key( $data_name ) {
		$data_name = md5( serialize( $data_name ) );
		return substr( self::CACHE_KEY_PREFIX . $data_name, 0, 45 );
	}

	public static function get_cache( $data_name = '' ) {
		if ( HSD_DEV ) { // If dev than don't cache.
			return false;
		}
		$cache = get_transient( self::get_cache_key( $data_name ) );
		// If cache is empty return false.
		return ( ! empty( $cache ) ) ? $cache : false;
	}

	public static function set_cache( $data_name = '', $data = array() ) {
		set_transient( self::get_cache_key( $data_name ), $data, self::CACHE_TIMEOUT ); // cache for two minutes.
		return $data;
	}

	public static function delete_cache( $data_name = '' ) {
		delete_transient( self::get_cache_key( $data_name ) );
	}

	public static function delete_conversation_cache( $conversation_id = 0 ) {
		$data_id = 'conversations/' . $conversation_id;
		delete_transient( self::get_cache_key( $data_id ) );
	}

	public static function delete_conversations_cache( $customer_id = 0, $mailbox_id = 0 ) {
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}
		$endpoint = sprintf( 'conversations?query=(customerIds:%2$s)&mailbox=%1$s&status=all&sortField=modifiedAt&sortOrder=desc&page=1', $mailbox_id, $customer_id );
		delete_transient( self::get_cache_key( $endpoint ) );
	}
}
