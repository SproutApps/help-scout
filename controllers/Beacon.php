<?php


/**
 * Help Scout API Controller
 *
 * @package Help_Scout_Desk
 * @subpackage Help
 */
class HSD_Beacon extends HSD_Controller {
	const BEACON_OPTION = 'help_scout_beacon';
	protected static $beacon_embed;

	public static function init() {
		self::$beacon_embed = get_option( self::BEACON_OPTION, false );

		// Register Settings
		self::register_settings();

		// front-end view
		add_action( 'wp_footer', array( __CLASS__, 'add_beacon' ) );
	}

	private static function embed_code() {
		$code = str_replace(
			array( '<script>', '</script>' ),
			array( '', '' ),
		self::$beacon_embed );
		return $code;
	}

	public static function add_beacon() {
		if ( ! self::$beacon_embed ) {
			return;
		}
		if ( is_user_logged_in() ) {
			$user_data = get_userdata( get_current_user_id() );
			$name = $user_data->user_firstname . ' ' . $user_data->user_lastname;
			$email = $user_data->user_email;
			?>
				<script>
					<?php echo self::embed_code(); ?>
					HS.beacon.ready(function() {
						HS.beacon.identify({
							name: '<?php echo esc_js( $name ); ?>',
							email: '<?php echo esc_js( $email ) ?>',
						});
					});
				</script>
			<?php
		} else {
			?>
				<script>
					<?php echo self::embed_code(); ?>
					HS.beacon.ready();
				</script>
			<?php
		}
	}

	//////////////
	// Settings //
	//////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {

		// Settings
		$settings = array(
			'hsd_beacon_options' => array(
				'weight' => 30,
				'settings' => array(
					self::BEACON_OPTION => array(
						'label' => __( 'Beacon', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Copy and paste the beacon embed code. For more information about this please read the <a href="%s">Help Scout documentation</a>.', 'help-scout-desk' ), 'http://developer.helpscout.net/beacons/' ),
							'type' => 'textarea',
							'default' => get_option( self::BEACON_OPTION, '' ),
						),
					),
				),
			),
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}
}
