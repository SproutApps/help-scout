<?php


/**
 * Help Scout API Controller
 *
 * @package Help_Scout_Desk
 * @subpackage Help
 */
class HSD_Beacon extends HSD_Controller {
	const BEACON_OPTION_ONE = 'help_scout_beacon';
	const BEACON_SEC_OPTION_ONE = 'help_scout_beacon_sec_key';
	const BEACON_PAGE_OPTION_ONE = 'help_scout_beacon_page_key';
	
	const BEACON_OPTION_TWO = 'help_scout_beacon_two';
	const BEACON_SEC_OPTION_TWO = 'help_scout_beacon_two_sec_key';
	const BEACON_PAGE_OPTION_TWO = 'help_scout_beacon_two_page_key';

	protected static $beacon_embed;
	protected static $beacon_embed_two;

	protected static $beacon_key;
	protected static $beacon_key_two;


	public static function init() {
		self::$beacon_embed = get_option( self::BEACON_OPTION_ONE, false );
		self::$beacon_key = get_option( self::BEACON_SEC_OPTION_ONE, false );

		self::$beacon_embed_two = get_option( self::BEACON_OPTION_TWO, false );
		self::$beacon_key_two = get_option( self::BEACON_SEC_OPTION_TWO, false );

		// Register Settings
		self::register_settings();

		// front-end view
			add_action( 'wp_after_admin_bar_render', array( __CLASS__, 'add_beacon' ) );
	
	}

	private static function embed_code($beacon_number = 0) {
		if($beacon_number == 1){
			$code = str_replace(
				array( '<script>', '</script>', '<script type="text/javascript">' ),
				array( '', '', '' ),
			self::$beacon_embed );
		}
		else if($beacon_number == 2){
			$code = str_replace(
				array( '<script>', '</script>', '<script type="text/javascript">' ),
				array( '', '', '' ),
			self::$beacon_embed_two );
		}
		return $code;
	}

	private static function is_beacon_2($beacon_number = 0) {
		if($beacon_number == 1){
			$bool = false;
			if ( strpos( self::$beacon_embed, 'beacon-v2' ) !== false ) {
				$bool = true;
			}
		}
		else if($beacon_number == 2){
			$bool = false;
			if ( strpos( self::$beacon_embed_two, 'beacon-v2' ) !== false ) {
				$bool = true;
			}
		}
		return $bool;
	}

	public static function add_beacon() {
		if ( ! self::$beacon_embed ) {
			return;
		}
		
		
		$page_option_one;
		$Page_Display_ID = array();
		$Page_Two_Display_ID = array();

		$page_option_one = get_option( self::BEACON_PAGE_OPTION_ONE, '' );
		$page_option_two = get_option( self::BEACON_PAGE_OPTION_TWO, '' );

		if($page_option_one != ''){
			$Page_Display_ID = $page_option_one;
			$Page_Display_ID = array_values(array_filter(explode(',', $page_option_one)));

			do_action( 'si_error','Elana Page Option One', $Page_Display_ID);
			
		}
		if($page_option_two != ''){
			$Page_Two_Display_ID = $page_option_two;
			$Page_Two_Display_ID = array_values(array_filter(explode(',', $page_option_two)));

			do_action( 'si_error','Elana Page Option TWO:', $Page_Two_Display_ID);
			
		}

		$post = get_the_ID();

		foreach($Page_Display_ID as $page_id){
			if($post == $page_id)
			{
				if ( is_user_logged_in() ) {

					$user_data = get_userdata( get_current_user_id() );
					$uname = $user_data->user_firstname . ' ' . $user_data->user_lastname;
					$name = ( strlen( $uname ) > 1 ) ? $uname : '' ;
					$email = $user_data->user_email;

					$signature = hash_hmac(
						'sha256',
						$email,
						self::$beacon_key
					);
					?>
						
						<script type="text/javascript">
							<?php echo self::embed_code(1); ?>

							<?php if ( self::is_beacon_2(1) ) : ?>

								<?php if ( self::$beacon_key ) : ?>
									window.Beacon("identify", {
										name: "<?php echo esc_js( $name ); ?>",
										email: "<?php echo esc_js( $email ) ?>",
										signature: "<?php echo esc_js( $signature ) ?>"
									});					
								<?php else : ?>
									window.Beacon("identify", {
										name: "<?php echo esc_js( $name ); ?>",
										email: "<?php echo esc_js( $email ) ?>"
									});

								<?php endif ?>
							<?php else : ?>
								HS.beacon.ready(function() {
									HS.beacon.identify({
										name: '<?php echo esc_js( $name ); ?>',
										email: '<?php echo esc_js( $email ) ?>',
									});
								});
							<?php endif ?>
						</script>
					<?php
				
				} else {
					?>
						<script type="text/javascript">
							<?php echo self::embed_code(1); ?>

							<?php if ( self::is_beacon_2(1) ) : ?>
								// nothing yet
							<?php else : ?>
								HS.beacon.ready();
							<?php endif ?>
						</script>
					<?php
				} 
			}
		}





		foreach($Page_Two_Display_ID as $page_id){
			if($post == $page_id)
			{
				if ( is_user_logged_in() ) {

					$user_data = get_userdata( get_current_user_id() );
					$uname = $user_data->user_firstname . ' ' . $user_data->user_lastname;
					$name = ( strlen( $uname ) > 1 ) ? $uname : '' ;
					$email = $user_data->user_email;

					$signature = hash_hmac(
						'sha256',
						$email,
						self::$beacon_key_two
					);
					?>
						
						<script type="text/javascript">
							<?php echo self::embed_code(2); ?>

							<?php if ( self::is_beacon_2(2) ) : ?>

								<?php if ( self::$beacon_key_two ) : ?>
									window.Beacon("identify", {
										name: "<?php echo esc_js( $name ); ?>",
										email: "<?php echo esc_js( $email ) ?>",
										signature: "<?php echo esc_js( $signature ) ?>"
									});					
								<?php else : ?>
									window.Beacon("identify", {
										name: "<?php echo esc_js( $name ); ?>",
										email: "<?php echo esc_js( $email ) ?>"
									});

								<?php endif ?>
							<?php else : ?>
								HS.beacon.ready(function() {
									HS.beacon.identify({
										name: '<?php echo esc_js( $name ); ?>',
										email: '<?php echo esc_js( $email ) ?>',
									});
								});
							<?php endif ?>
						</script>
					<?php
				
				} else {
					?>
						<script type="text/javascript">
							<?php echo self::embed_code(2); ?>

							<?php if ( self::is_beacon_2(2) ) : ?>
								// nothing yet
							<?php else : ?>
								HS.beacon.ready();
							<?php endif ?>
						</script>
					<?php
				} 
			}
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
				'title' => 'Beacon One',
				'weight' => 30,
				'settings' => array(
					self::BEACON_OPTION_ONE => array(
						'label' => __( 'Beacon Embed Code', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Copy and paste the beacon embed code. For more information about this please read the <a href="%s">Help Scout documentation</a>.', 'help-scout-desk' ), 'http://developer.helpscout.net/beacons/' ),
							'type' => 'textarea',
							'default' => get_option( self::BEACON_OPTION_ONE, '' ),
						),
					),
					self::BEACON_SEC_OPTION_ONE => array(
						'label' => __( 'Support history security key', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Click "advanced" for the "Support history security" option when setting up your Beacon.', 'help-scout-desk' ) ),
							'type' => 'input',
							'default' => get_option( self::BEACON_SEC_OPTION_ONE, '' ),
						),
					),
					self::BEACON_PAGE_OPTION_ONE => array(
						'label' => __( 'Page Numbers to Display for Beacon One', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Select pages numbers you would like to display. Leave blank to display for all pages.', 'help-scout-desk' ) ),
							'type' => 'textarea',
							'default' => get_option( self::BEACON_PAGE_OPTION_ONE, '' ),
						),
					),
					
				),
			),

			
			'hsd_second_beacon_options' => array(
				'title' => 'Beacon TWO',
				'weight' => 30,
				'settings' => array(
					self::BEACON_OPTION_TWO => array(
						'label' => __( 'Beacon Embed Code', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Copy and paste the beacon embed code. For more information about this please read the <a href="%s">Help Scout documentation</a>.', 'help-scout-desk' ), 'http://developer.helpscout.net/beacons/' ),
							'type' => 'textarea',
							'default' => get_option( self::BEACON_OPTION_TWO, '' ),
						),
					),
					self::BEACON_SEC_OPTION_TWO => array(
						'label' => __( 'Support history security key', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Click "advanced" for the "Support history security" option when setting up your Beacon.', 'help-scout-desk' ) ),
							'type' => 'input',
							'default' => get_option( self::BEACON_SEC_OPTION_TWO, '' ),
						),
					),
					self::BEACON_PAGE_OPTION_TWO => array(
						'label' => __( 'Page Numbers to Display for the second becon', 'help-scout-desk' ),
						'option' => array(
							'description' => sprintf( __( 'Select pages numbers you would like to display. If blank this will not display.', 'help-scout-desk' ) ),
							'type' => 'textarea',
							'default' => get_option( self::BEACON_PAGE_OPTION_TWO, '' ),
						),
					),
					
				),
			),
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}
}
