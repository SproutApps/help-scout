<?php


/**
 * Help Scout API Controller
 *
 * @package HelpScout_Desk
 * @subpackage HSD Admin Settings
 */
class HSD_Settings extends HSD_Controller {
	const API_KEY = 'hs_api_key';
	const MAILBOX = 'hs_mailbox';
	protected static $api_key;
	protected static $mailbox;

	public static function init() {
		// Store options
		self::$api_key = get_option( self::API_KEY, '' );
		self::$mailbox = get_option( self::MAILBOX, '' );

		// Register Settings
		self::register_settings();

	}

	public static function get_api_key() {
		return self::$api_key;
	}

	public static function get_mailbox() {
		return self::sanitize_mailbox_id( self::$mailbox );
	}


	//////////////
	// Settings //
	//////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Option page
		$args = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => 'Help Scout Desk Settings',
			'menu_title' => 'Help Scout Desk',
			'tab_title' => 'Getting Started',
			'weight' => 20,
			'reset' => false,
			'section' => self::SETTINGS_PAGE,
			);

		if ( HSD_FREE ) {
			$args['title'] = 'Help Scout Settings';
			$args['menu_title'] = 'Help Scout Plugin';
		}

		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'hsd_site_settings' => array(
				'title' => 'Help Scout Setup',
				'weight' => 10,
				'callback' => array( __CLASS__, 'display_general_section' ),
				'settings' => array(
					self::API_KEY => array(
						'label' => __( 'API Key', 'help-scout-desk' ),
						'option' => array(
							'description' => __( 'To locate your API key, login to your Help Scout account and click the <b>User Profile</b> menu in the top-right corner. Visit <b>API Keys</b> and click to <b>Generate an API key</b>.', 'help-scout-desk' ),
							'type' => 'text',
							'default' => self::$api_key,
						),
					),
					self::MAILBOX => array(
						'label' => __( 'Mailbox ID', 'help-scout-desk' ),
						'option' => array(
							'description' => __( 'When opening a mailbox within Help Scout, open the mailbox and click Settings in the bottom left corner of the mailbox filters list and click in Edit Mailbox. In the URL of the resulting settings screen, is your mailbox ID. Example, https://secure.helpscout.net/settings/mailbox/<b>123456</b>/', 'help-scout-desk' ),
							'type' => 'text',
							'default' => self::sanitize_mailbox_id( self::$mailbox ),
						),
						'sanitize_callback' => array( __CLASS__, 'sanitize_mailbox_id' ),
					),
				),
			),
			'hsd_options' => array(
				'title' => 'Options / Settings',
				'weight' => 20,
				'callback' => array( __CLASS__, 'section_desc' ),
				'settings' => array(),
			),
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	//////////////////////
	// General Settings //
	//////////////////////

	public static function display_general_section() {
		echo '<p>'._e( 'Enter Help Scout API Information.', 'help-scout-desk' ).'</p>';
	}

	public static function section_desc() {
		_e( 'Make sure to setup your Help Scout API key and Mailbox ID before proceeding to these options / settings.', 'help-scout-desk' );
	}

	///////////////
	// Sanitize //
	///////////////

	public static function sanitize_mailbox_id( $option = '' ) {
		// strip everything but the numbers incase they through the entire url
		// in as the option.
		return preg_replace( '/[^0-9]/', '', $option );
	}
}
