<?php

/**
 * A base class from which all other controllers should be derived
 *
 * @package HelpScout_Desk
 * @subpackage Controller
 */
abstract class HSD_Controller extends HelpScout_Desk {
	const CRON_HOOK = 'hsd_cron';
	const DAILY_CRON_HOOK = 'hsd_daily_cron';
	const DEFAULT_TEMPLATE_DIRECTORY = 'hsd_templates';
	const SETTINGS_PAGE = 'help_scout_desk';
	const NONCE = 'helpscout_desks_controller_nonce';

	private static $template_path = self::DEFAULT_TEMPLATE_DIRECTORY;
	private static $shortcodes = array();

	public static function init() {
		if ( is_admin() ) {
			// On Activation
			add_action( 'hsd_plugin_activation_hook', array( __CLASS__, 'helpscout_desks_activated' ) );
		}

		// Register Shortcodes
		add_action( 'hsd_shortcode', array( __CLASS__, 'register_shortcode' ), 0, 3 );
		// Add shortcodes
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );

		// Cron
		add_filter( 'cron_schedules', array( __CLASS__, 'hsd_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'set_schedule' ), 10, 0 );

		add_filter( 'admin_footer_text', array( __CLASS__, 'please_rate_hs' ), 1, 2 );
	}

	public static function please_rate_hs( $footer_text ) {
		if ( self::is_hsd_admin() ) {
			$footer_text = sprintf( __( 'Please support the future of <strong>Help Scout</strong> by rating the free version <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%1$s" target="_blank">WordPress.org</a>. Have an awesome %2$s!', 'help-scout-desk' ), 'http://wordpress.org/support/view/plugin-reviews/help-scout?filter=5', date_i18n('l') );
		}
		return $footer_text;
	}

	public static function is_hsd_admin() {
		$screen = get_current_screen();
		if ( 'toplevel_page_help-scout-desk' === $screen->id || 'sprout-apps_page_help-scout-desk/help_scout_desk' === $screen->id ) {
			return true;
		}
		return false;
	}

	/////////////////
	// Shortcodes //
	/////////////////

	/**
	 * Wrapper for the add_shorcode function WP provides
	 * @param string the shortcode
	 * @param array $callback
	 * @param array $args FUTURE
	 */
	public static function register_shortcode( $tag = '', $callback = array(), $args = array() ) {
		// FUTURE $args
		self::$shortcodes[$tag] = $callback;
	}

	/**
	 * Loop through registered shortcodes and use the WP function.
	 * @return
	 */
	public static function add_shortcodes(){
		foreach ( self::$shortcodes as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}

	public static function get_shortcodes() {
		return self::$shortcodes;
	}


	/**
	 * Template path for templates/views, default to 'invoices'.
	 *
	 * @return string self::$template_path the folder
	 */
	public static function get_template_path() {
		return apply_filters( 'hsd_template_path', self::$template_path );
	}

	/**
	 * Fire actions based on plugin being updated.
	 * @return
	 */
	public static function helpscout_desks_activated() {
		add_option( 'hsd_do_activation_redirect', true );
		// Get the previous version number
		$hsd_version = get_option( 'hsd_current_version', self::HSD_VERSION );
		if ( version_compare( $hsd_version, self::HSD_VERSION, '<' ) ) { // If an upgrade create some hooks
			do_action( 'hsd_version_upgrade', $hsd_version );
			do_action( 'hsd_version_upgrade_'.$hsd_version );
		}
		// Set the new version number
		update_option( 'hsd_current_version', self::HSD_VERSION );
	}



	public static function register_resources() {
		$fereqjs = array( 'jquery' );
		$fereqcss = array();
		if ( file_exists( HSD_PATH . '/resources/front-end/plugins/redactor/redactor.min.js' ) ) {
			// Redactor
			wp_register_script( 'redactor', HSD_URL . '/resources/front-end/plugins/redactor/redactor.min.js', array( 'jquery' ), self::HSD_VERSION );
			wp_register_style( 'redactor', HSD_URL . '/resources/front-end/plugins/redactor/redactor.css', array(), self::HSD_VERSION );

			$fereqjs = array( 'jquery', 'redactor' );
			$fereqcss = array( 'redactor' );
		}

		// Templates
		wp_register_script( 'hsd', HSD_URL . '/resources/front-end/js/hsd.js', $fereqjs, self::HSD_VERSION );
		wp_register_style( 'hsd', HSD_URL . '/resources/front-end/css/hsd.style.css', $fereqcss, self::HSD_VERSION );

		// Admin
		wp_register_script( 'hsd_admin_js', HSD_URL . '/resources/admin/js/hsd.js', array( 'jquery' ), self::HSD_VERSION );
		wp_register_style( 'hsd_admin_css', HSD_URL . '/resources/admin/css/hsd.css', array(), self::HSD_VERSION );

	}

	public static function frontend_enqueue() {
		$hsd_js_object = array(
			'admin_ajax' => admin_url( 'admin-ajax.php' ),
			'sec' => wp_create_nonce( self::NONCE ),
			'post_id' => get_the_ID(),
			'readmore' => __( 'Expand message', 'help-scout-desk' ),
			'close' => __( 'Collapse message', 'help-scout-desk' ),
			'redactor' => false,
		);
		if ( file_exists( HSD_PATH . '/resources/front-end/plugins/redactor/redactor.min.js' ) ) {
			$hsd_js_object['redactor'] = true;
		}
		wp_localize_script( 'hsd', 'hsd_js_object', apply_filters( 'hsd_scripts_localization', $hsd_js_object ) );

	}

	public static function admin_enqueue() {
		wp_enqueue_script( 'hsd_admin_js' );
		wp_enqueue_style( 'hsd_admin_css' );
		$hsd_js_object = array(
			'sec' => wp_create_nonce( self::NONCE )
		);
		wp_localize_script( 'hsd_admin_js', 'hsd_js_object', apply_filters( 'hsd_scripts_localization', $hsd_js_object ) );
	}

	/**
	 * Filter WP Cron schedules
	 * @param  array $schedules
	 * @return array
	 */
	public static function hsd_cron_schedule( $schedules ) {
		$schedules['minute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute' )
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 Minutes' )
		);
		$schedules['halfhour'] = array(
			'interval' => 1800,
			'display' => __( 'Twice Hourly' )
		);
		return $schedules;
	}

	/**
	 * schedule wp events for wpcron.
	 */
	public static function set_schedule() {
		if ( self::DEBUG ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$interval = apply_filters( 'hsd_set_schedule', 'quarterhour' );
			wp_schedule_event( time(), $interval, self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::DAILY_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_CRON_HOOK );
		}
	}

	/**
	 * Display the template for the given view
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return void
	 */
	public static function load_view( $view, $args, $allow_theme_override = true ) {
		// whether or not .php was added
		if ( substr( $view, -4 ) != '.php' ) {
			$view .= '.php';
		}
		$file = HSD_PATH.'/views/'.$view;
		if ( $allow_theme_override && defined( 'TEMPLATEPATH' ) ) {
			$file = self::locate_template( array( $view ), $file );
		}
		$file = apply_filters( 'helpscout_desk_template_'.$view, $file );
		$args = apply_filters( 'load_view_args_'.$view, $args, $allow_theme_override );
		if ( ! empty( $args ) ) { extract( $args ); }
		if ( self::DEBUG ) {
			include $file;
		}
		else {
			include $file;
		}
	}

	/**
	 * Return a template as a string
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return string
	 */
	protected static function load_view_to_string( $view, $args, $allow_theme_override = true ) {
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @static
	 * @param array   $possibilities
	 * @param string  $default
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {
		$possibilities = apply_filters( 'helpscout_desk_template_possibilities', $possibilities );
		$possibilities = array_filter( $possibilities );
		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = self::get_template_path().'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, false ) ) {
			return $found;
		}

		// check for it in the templates directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( HSD_PATH.'/views/templates/'.$p ) ) {
				return HSD_PATH.'/views/templates/'.$p;
			}
		}

		// we don't have it
		return $default;
	}

	//////////////
	// Utility //
	//////////////

	public static function login_required( $redirect = '' ) {
		if ( ! get_current_user_id() && apply_filters( 'hsd_login_required', true ) ) {
			if ( ! $redirect && self::using_permalinks() ) {
				$schema = is_ssl() ? 'https://' : 'http://';
				$redirect = $schema.$_SERVER['SERVER_NAME'].htmlspecialchars( $_SERVER['REQUEST_URI'] );
				if ( isset( $_REQUEST ) ) {
					$redirect = urlencode( add_query_arg( $_REQUEST, esc_url_raw( $redirect ) ) );
				}
			}
			wp_redirect( wp_login_url( $redirect ) );
			exit();
		}
		return true; // explicit return value, for the benefit of the router plugin
	}

	/**
	 * Is current site using permalinks
	 * @return bool
	 */
	public static function using_permalinks() {
		return get_option( 'permalink_structure' ) != '';
	}

	/**
	 * Tell caching plugins not to cache the current page load
	 */
	public static function do_not_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/**
	 * Tell caching plugins to clear their caches related to a post
	 *
	 * @static
	 * @param int $post_id
	 */
	public static function clear_post_cache( $post_id ) {
		if ( function_exists( 'wp_cache_post_change' ) ) {
			// WP Super Cache

			$GLOBALS['super_cache_enabled'] = 1;
			wp_cache_post_change( $post_id );

		} elseif ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			// W3 Total Cache

			w3tc_pgcache_flush_post( $post_id );

		}
	}

	public static function ajax_fail( $message = '', $json = true ) {
		if ( $message == '' ) {
			$message = __( 'Something failed.', 'help-scout-desk' );
		}
		if ( $json ) { header( 'Content-type: application/json' ); }
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		if ( $json ) {
			echo json_encode( array( 'error' => 1, 'response' => $message ) );
		}
		else {
			echo $message;
		}
		exit();
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_weight( $a, $b ) {
		if ( ! isset( $a['weight'] ) || ! isset( $b['weight'] ) ) {
			return 0; }

		if ( $a['weight'] == $b['weight'] ) {
			return 0;
		}
		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}

	/**
	 * Turn all URLs in clickable links.
	 *
	 * @param string $value
	 * @param array  $protocols  http/https, ftp, mail, twitter
	 * @param array  $attributes
	 * @param string $mode       normal or all
	 * @return string
	 */
	public static function linkify( $value, $protocols = array('http', 'mail'), array $attributes = array() ) {

		// Link attributes
		$attr = '';
		foreach ( $attributes as $key => $val ) {
			$attr = ' ' . $key . '="' . htmlentities( $val ) . '"';
		}

		$links = array();

		// Extract existing links and tags
		$value = preg_replace_callback( 
				'~(<a .*?>.*?</a>|<.*?>)~i', 
				function ( $match ) use ( &$links ) { 
					return '<' . array_push( $links, $match[1] ) . '>'; 
				}, 
				$value
			);

		// Extract text links for each protocol
		foreach ( (array)$protocols as $protocol ) {
			switch ( $protocol ) {
				case 'http':
				case 'https':   $value = preg_replace_callback( '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ( $match[1] ) { $protocol = $match[1]; } $link = $match[2] ?: $match[3]; return '<' . array_push( $links, "<a $attr href=\"$protocol://$link\">$link</a>" ) . '>'; }, $value ); break;
				case 'mail':    $value = preg_replace_callback( '~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push( $links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>" ) . '>'; }, $value ); break;
				case 'twitter': $value = preg_replace_callback( '~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push( $links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\">{$match[0]}</a>" ) . '>'; }, $value ); break;
				default:        $value = preg_replace_callback( '~' . preg_quote( $protocol, '~' ) . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push( $links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>" ) . '>'; }, $value ); break;
			}
		}

		// Insert all link
		return preg_replace_callback( '/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value );
	}

}