<?php

/**
 * Load the SI application
 * (function called at the bottom of this page)
 *
 * @package Help_Scout_Desk
 * @return void
 */
function helpscout_desk_load() {
	if ( class_exists( 'Help_Scout_Desk' ) ) {
		hsd_deactivate_plugin();
		return; // already loaded, or a name collision
	}

	do_action( 'helpscout_desk_preload' );

	//////////
	// Load //
	//////////

	// Master class
	require_once HSD_PATH.'/HelpScout_Desk.php';

	// controllers
	require_once HSD_PATH.'/controllers/_Controller.php';
	HSD_Controller::init();

	// l10n
	require_once HSD_PATH.'/languages/HSD_l10n.php';
	HSD_l10n::init();

	if ( ! class_exists( 'SA_Settings_API' ) ) {
		require_once HSD_PATH.'/controllers/_Settings.php';
		SA_Settings_API::init();
	}

	require_once HSD_PATH.'/controllers/Settings.php';
	HSD_Settings::init();

	require_once HSD_PATH.'/controllers/HelpScout_API.php';
	HelpScout_API::init();

	require_once HSD_PATH.'/controllers/Forms.php';
	HSD_Forms::init();

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Embeds.php' ) ) {
		require_once HSD_PATH.'/controllers/Embeds.php';
		HSD_Embeds::init();
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Conversations.php' ) ) {
		require_once HSD_PATH.'/controllers/Conversations.php';
		HSD_Conversations::init();
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/AJAX.php' ) ) {
		require_once HSD_PATH.'/controllers/AJAX.php';
		HSD_AJAX::init();
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Tags.php' ) ) {
		require_once HSD_PATH.'/controllers/Tags.php';
		HSD_Tags::init();
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Customers.php' ) ) {
		require_once HSD_PATH.'/controllers/Customers.php';
		HSD_Customers::init();
	}

	require_once HSD_PATH.'/controllers/Beacon.php';
	HSD_Beacon::init();

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Email_Login.php' ) ) {
		require_once HSD_PATH.'/controllers/Email_Login.php';
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/integrations/edd/HelpScout_EDD_App.php' ) ) {

		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			require_once HSD_PATH.'/controllers/integrations/edd/HelpScout_EDD_App.php';
			require_once HSD_PATH.'/controllers/integrations/edd/HelpScout_EDD_App_Handler.php';
			require_once HSD_PATH.'/controllers/integrations/edd/HelpScout_EDD_App_AJAX.php';
			HelpScout_EDD_App::init();
		}

		if ( class_exists( 'WooCommerce' ) ) {
			require_once HSD_PATH.'/controllers/integrations/woo/HelpScout_Woo_App.php';
			require_once HSD_PATH.'/controllers/integrations/woo/HelpScout_Woo_App_Handler.php';
			require_once HSD_PATH.'/controllers/integrations/woo/HelpScout_Woo_App_AJAX.php';
			HelpScout_Woo_App::init();
		}

		if ( class_exists( 'WP_eCommerce' ) ) {
			require_once HSD_PATH.'/controllers/integrations/wpsc/HelpScout_WPSC_App.php';
			require_once HSD_PATH.'/controllers/integrations/wpsc/HelpScout_WPSC_App_Handler.php';
			require_once HSD_PATH.'/controllers/integrations/wpsc/HelpScout_WPSC_App_AJAX.php';
			HelpScout_WPSC_App::init();
		}
	}

	if ( ! HSD_FREE && file_exists( HSD_PATH.'/controllers/Updates.php' ) ) {
		require_once HSD_PATH.'/controllers/Updates.php';
		HSD_Updates::init();
	}

	require_once HSD_PATH.'/template-tags/help-scout-desk.php';
	do_action( 'helpscout_desk_loaded' );
}

/**
 * Minimum supported version of WordPress
 */
define( 'HSD_SUPPORTED_WP_VERSION', version_compare( get_bloginfo( 'version' ), '4.0', '>=' ) );
/**
 * Minimum supported version of PHP
 */
define( 'HSD_SUPPORTED_PHP_VERSION', version_compare( phpversion(), '5.4', '>=' ) );

/**
 * Compatibility check
 */
if ( HSD_SUPPORTED_WP_VERSION && HSD_SUPPORTED_PHP_VERSION ) {
	add_action( 'plugins_loaded', 'helpscout_desk_load', 120 );
} else {
	/**
	 * Disable SI and add fail notices if compatibility check fails
	 * @return string inserted within the WP dashboard
	 */
	hsd_deactivate_plugin();
	add_action( 'admin_head', 'hsd_fail_notices' );
	function hsd_fail_notices() {
		if ( ! HSD_SUPPORTED_WP_VERSION ) {
			printf( '<div class="error"><p><strong>Help Scout Desk</strong> requires WordPress 4.0 or higher (you have %s). Please upgrade WordPress and activate the Help Scout Desk Plugin again.</p></div>', get_bloginfo( 'version' ) );
		}
		if ( ! HSD_SUPPORTED_PHP_VERSION ) {
			printf( '<div class="error"><p><strong>Help Scout Desk</strong> requires PHP version 5.4 or higher to be installed on your server (you have %s). Talk to your web host about using a secure version of PHP.</p></div>', phpversion() );
		}
	}
}
