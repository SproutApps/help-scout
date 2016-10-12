<?php

/**
 * @package Help_Scout_Desk
 * @version 4.0
 */

/*
 * Plugin Name: Help Scout Desk
 * Plugin URI: https://sproutapps.co/help-scout-desk/
 * Description: Allows for Help Scout conversations to be easily displayed on your site, with the ability to create new conversations and reply to existing ones. Learn more at <a href="https://sproutapps.co">Sprout Apps</a>.
 * Author: Sprout Apps
 * Version: 4.0
 * Author URI: https://sproutapps.co
 * Text Domain: help-scout-desk
 * Domain Path: languages
*/


/**
 * SI directory
 */
define( 'HSD_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
/**
 * Plugin File
 */
define( 'HSD_PLUGIN_FILE', __FILE__ );

/**
 * SI URL
 */
define( 'HSD_URL', plugins_url( '', __FILE__ ) );
/**
 * URL to resources directory
 */
define( 'HSD_RESOURCES', plugins_url( 'resources/', __FILE__ ) );


/**
 * Load plugin
 */
require_once HSD_PATH . '/load.php';

/**
 * do_action when plugin is activated.
 * @package Help_Scout_Desk
 * @ignore
 */
register_activation_hook( __FILE__, 'hsd_plugin_activated' );
function hsd_plugin_activated() {
	do_action( 'hsd_plugin_activation_hook' );
}
/**
 * do_action when plugin is deactivated.
 * @package Help_Scout_Desk
 * @ignore
 */
register_deactivation_hook( __FILE__, 'hsd_plugin_deactivated' );
function hsd_plugin_deactivated() {
	do_action( 'hsd_plugin_deactivation_hook' );
}

function hsd_deactivate_plugin() {
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	}
}
