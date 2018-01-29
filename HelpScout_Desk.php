<?php


/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package SI
 */

if ( ! defined( 'HSD_FREE' ) ) {
	define( 'HSD_FREE', false ); }

if ( ! defined( 'HSD_DEV' ) ) {
	define( 'HSD_DEV', false ); }

if ( ! defined( 'SUPPORT_URL' ) ) {
	define( 'SUPPORT_URL', 'http://docs.sproutapps.co/collection/14-help-scout-desk' );
}

abstract class HelpScout_Desk {
	/**
	 * Application app-domain
	 */
	const APP_DOMAIN = 'help-scout-desk';

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'help-scout-desk';
	/**
	 * Application text-domain
	 */
	const PLUGIN_URL = 'https://sproutapps.co';
	/**
	 * Current version. Should match help-scout-desk.php plugin version.
	 */
	const HSD_VERSION = '4.1.2';
	/**
	 * DB Version
	 */
	const DB_VERSION = 1;
	/**
	 * Application Name
	 */
	const PLUGIN_NAME = 'Help Scout Desk';
	const PLUGIN_FILE = HSD_PLUGIN_FILE;
	/**
	 * HSD_DEV constant within the wp-config to turn on SI debugging
	 * <code>
	 * define( 'HSD_DEV', TRUE/FALSE )
	 * </code>
	 */
	const DEBUG = HSD_DEV;

	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return string|void
	 */
	public static function __( $string ) {
		// deprecated
		return __( apply_filters( 'hsd_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return void
	 */
	public static function _e( $string ) {
		// deprecated
		return _e( apply_filters( 'hsd_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc__( $string ) {
		// deprecated
		return esc_attr__( $string, self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc_e( $string ) {
		// deprecated
		return esc_attr_e( $string, self::TEXT_DOMAIN );
	}
}
