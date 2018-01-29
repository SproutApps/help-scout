<?php

/**
 * @package Help_Scout_Desk
 * @version 4.1.2
 */

/*
 * Plugin Name: Help Scout
 * Plugin URI: https://helpscout.net/wordpress-plugin/
 * Description: Allows for Help Scout conversations to be easily created on your site, with full beacon support. If you're wanting to display conversations on your site checkout <a href="https://sproutapps.co/help-scout-desk-wordpress-plugin/?utm_medium=link&utm_campaign=hsfree&utm_source=wordpress.org">Help Scout Desk</a>.
 * Author: Sprout Apps
 * Version: 4.1.2
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

define( 'HSD_FREE', true );
define( 'SUPPORT_URL', 'http://docs.sproutapps.co/collection/166-help-scout' );

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
