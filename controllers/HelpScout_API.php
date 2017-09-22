<?php

/**
 * Help Scout API Controller
 *
 * @package HelpScout_Desk
 * @subpackage HS API
 */
class HelpScout_API extends HSD_Controller {
	const API_ENDPOINT = 'https://api.helpscout.net/v1/';
	const NONCE = 'hsd_api_nonce';
	const CUSTOMER_IDS = 'hsd_customer_ids_v3';
	const CACHE_KEY_PREFIX = 'hsd_api_cache_';
	const CACHE_TIMEOUT = 120; // 2 Minutes
	private static $mailbox;
	private static $api_key;
	private static $api;


	public static function init() {
		self::$mailbox = HSD_Settings::get_mailbox();
		self::$api_key = HSD_Settings::get_api_key();

		add_action( 'wp_ajax_hsd_reset_customer_ids', array( __CLASS__, 'maybe_reset_customer_ids' ) );
	}

	public static function api_request( $endpoint = '', $query = '', $refresh = false ) {
		$endput_and_query = $endpoint . '.json' . $query;
		$api_url = apply_filters( 'hsd_api_request_api_url', self::API_ENDPOINT . $endput_and_query, $endput_and_query, $query, $refresh );

		// Return cache if present.
		$cache = self::get_cache( $api_url );
		if ( $cache && ! $refresh ) {
			return $cache;
		}

		// Remote API request
		$params = array(
			'method' => 'GET',
			'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( self::$api_key . ':' . 'X' ),
				),
			'sslverify' => false,
			'timeout' => 15,
		);
		if ( HSD_DEV ) { error_log( 'api_url: ' . print_r( $api_url, true ) ); }
		$params = apply_filters( 'hsd_api_request_params', $params, $api_url, $query, $refresh );
		$response = wp_remote_request( $api_url, $params );
		$response_body = wp_remote_retrieve_body( $response );

		$response = apply_filters( 'hsd_api_request', $response_body, $endpoint, $query, $refresh );
		// Set cache and return request
		self::set_cache( $api_url, $response );
		return $response;
	}

	public static function api_post( $endpoint = '', $data = '', $method = 'POST', $reload = true ) {
		$endput = $endpoint . '.json';
		if ( $reload ) {
			$endput .= '?reload=1';
		}
		$api_url = apply_filters( 'hsd_api_post_api_url', self::API_ENDPOINT . $endput, $data );

		// Remote API request
		$params = array(
			'method' => $method,
			'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( self::$api_key . ':' . 'X' ),
					'Content-Type' => 'application/json',
				),
			'sslverify' => false,
			'timeout' => 15,
			'body' => $data,
		);
		$params = apply_filters( 'hsd_api_post_params', $params, $endput, $data );
		$response = wp_remote_post( $api_url, $params );
		$response_body = wp_remote_retrieve_body( $response );

		$response = apply_filters( 'hsd_api_post', $response_body, $endput, $data );
		return $response;
	}



	////////////////////
	// Conversations //
	////////////////////


	public static function get_conversation( $conversation_id = 0, $refresh = false ) {
		if ( ! $conversation_id ) {
			return false;
		}

		// Get conversations	recode_file(1q`, input, output)
		$response = self::api_request( 'conversations/'.$conversation_id, '', $refresh );
		$conversation_object = json_decode( $response );
		$conversation = json_decode( wp_json_encode( $conversation_object ), true ); // convert to array
		if ( HSD_DEV ) { error_log( 'conversation_object: ' . print_r( $conversation, true ) ); }

		if ( apply_filters( 'hsd_hide_drafts_and_notes', true ) ) {
			if ( ! empty( $conversation['item']['threads'] ) ) {
				foreach ( $conversation['item']['threads'] as $key => $thread ) {
					// remove notes
					if ( $thread['type'] === 'note' ) {
						unset( $conversation['item']['threads'][ $key ] );
					}
					// remove drafts
					if ( $thread['state'] !== 'published' ) {
						unset( $conversation['item']['threads'][ $key ] );
					}
				}
			}
		}
		return apply_filters( 'hsd_get_conversation', $conversation, $conversation_id, $refresh );
	}

	public static function get_conversations_by_user( $customer_id = 0, $refresh = false, $mailbox_id = 0 ) {
		if ( ! $customer_id ) {
			$customer_ids = self::find_customer_ids( 0, $refresh );
		} else {
			$customer_ids = array( $customer_id );
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		// Get conversations
		$conversations = array();
		foreach ( $customer_ids as $customer_id ) {
			$response = self::api_request( 'mailboxes/'.$mailbox_id.'/customers/'.$customer_id.'/conversations', '', $refresh );
			$conversations_object = json_decode( $response );
			if ( HSD_DEV ) { error_log( 'conversations response: ' . print_r( $conversations_object, true ) ); }
			if ( ! empty( $conversations_object->items ) ) {
				foreach ( $conversations_object->items as $key => $data ) {
					$conversations[] = (array) $data;
				}
			}
		}
		if ( HSD_DEV ) { error_log( 'conversations: ' . print_r( $conversations, true ) ); }
		return apply_filters( 'hsd_get_conversations_by_user', $conversations, $customer_id, $refresh );
	}


	public static function create_thread( $conversation_id = 0, $message = 0, $new_status = 'active', $mailbox_id = 0, $attachments = array() ) {
		if ( ! $conversation_id ) {
			return false;
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		if ( HSD_FREE ) {
			$message = wpautop( $message );
		}

		$conversation = self::get_conversation( $conversation_id );
		$customer = $conversation['item']['customer'];
		$status = ( $new_status ) ? $new_status : $conversation['item']['status'] ;
		$fields = apply_filters( 'hsd_create_thread_fields', array(
				'createdBy' => $customer,
				'type' => 'customer',
				'body' => $message,
				'status' => $status,
				'attachments' => $attachments,
		) );
		$params = apply_filters( 'hsd_create_thread_params', array(
			'method' => 'POST',
			'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( self::$api_key . ':' . 'X' ),
					'Accept'       => 'application/json',
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( wp_json_encode( $fields ) ),
				),
			'sslverify' => false,
			'timeout' => 15,
			'body' => wp_json_encode( $fields ),
		) );
		$api_url = apply_filters( 'hsd_create_thread_url', self::API_ENDPOINT . 'conversations/'.$conversation_id.'.json', $fields, $params );
		$response = wp_remote_request( $api_url, $params );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			if ( HSD_DEV ) { error_log( 'create_thread error: ' . print_r( $r_obj, true ) ); }
			return $response_body->error;
		}

		// clear cache
		self::delete_conversation_cache( $conversation_id );
		self::delete_conversations_cache( $customer['id'], $mailbox_id );

		do_action( 'hsd_create_thread', $response_body );
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
		if ( empty( $customer_ids ) ) {
			$customer = array( 'type' => 'customer', 'email' => self::find_email() );
		} else {
			$customer = array( 'type' => 'customer', 'id' => $customer_ids[0] );
		}

		$fields = apply_filters( 'hsd_create_conversation_fields', array(
				'mailbox' => array( 'id' => $mailbox_id ),
				'customer' => $customer,
				'subject' => $subject,
				'threads' => array( array( 'type' => 'customer', 'createdBy' => $customer, 'body' => $message, 'attachments' => $attachments ) ),
		) );
		$params = apply_filters( 'hsd_create_conversation_params', array(
			'method' => 'POST',
			'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( self::$api_key . ':' . 'X' ),
					'Accept'       => 'application/json',
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( wp_json_encode( $fields ) ),
				),
			'sslverify' => false,
			'timeout' => 15,
			'body' => wp_json_encode( $fields ),
		) );
		$api_url = apply_filters( 'hsd_create_conversation_url', self::API_ENDPOINT . 'conversations.json', $fields, $params );
		$response = wp_remote_request( $api_url, $params );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			if ( HSD_DEV ) { error_log( 'create_conversation error: ' . print_r( $r_obj, true ) ); }
			return $response_body->error;
		}

		// clear cache
		if ( isset( $customer['id'] ) ) {
			self::delete_conversations_cache( $customer['id'], $mailbox_id );
		}

		do_action( 'hsd_create_conversation', $response_body );
		return true;
	}


	public static function create_attachment( $file = array(), $mailbox_id = 0 ) {
		if ( ! is_array( $file ) ) {
			return false;
		}
		if ( $file['size'] < 1 ) {
			return false;
		}
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}

		$fields = apply_filters( 'hsd_create_attachment_fields', array(
				'mailbox' => array( 'id' => $mailbox_id ),
				'fileName' => $file['name'],
				'mimeType' => $file['type'],
				'data' => base64_encode( file_get_contents( $file['tmp_name'] ) ),
		) );
		$params = apply_filters( 'hsd_create_attachment_params', array(
			'method' => 'POST',
			'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( self::$api_key . ':' . 'X' ),
					'Accept'       => 'application/json',
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( wp_json_encode( $fields ) ),
				),
			'sslverify' => false,
			'timeout' => 15,
			'body' => wp_json_encode( $fields ),
		) );
		$api_url = apply_filters( 'hsd_create_attachment_url', self::API_ENDPOINT . 'attachments.json', $fields, $params );
		$response = wp_remote_request( $api_url, $params );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			if ( HSD_DEV ) { error_log( 'create_conversation error: ' . print_r( $r_obj, true ) ); }
			return $response_body->error;
		}
		do_action( 'hsd_create_attachment', $response_body );
		if ( ! isset( $response_body->item ) ) {
			return false;
		}
		return $response_body->item;
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
		$response = self::api_request( 'customers', '?email='.$email, $refresh );
		// convert to an array
		$customers = json_decode( $response );

		// Loop through response and build an array of customer ids
		$customer_ids = array();
		if ( ! empty( $customers->items ) ) {
			foreach ( $customers->items as $key => $data ) {
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
		$data_id = 'conversations/' . $conversation_id . '.json';
		delete_transient( self::get_cache_key( $data_id ) );
	}

	public static function delete_conversations_cache( $customer_id = 0, $mailbox_id = 0 ) {
		if ( ! $mailbox_id ) {
			$mailbox_id = self::$mailbox;
		}
		$data_id = 'mailboxes/' . $mailbox_id . '/customers/' . $customer_id . '/conversations.json';
		delete_transient( self::get_cache_key( $data_id ) );
	}
}
