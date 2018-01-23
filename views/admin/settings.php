<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
	$page = ( ! isset( $_GET['tab'] ) ) ? $page : self::TEXT_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo esc_attr( $page ) ?>" class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'sprout_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>

	<?php if ( HSD_FREE ) :  ?>
		<?php printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>%s</strong> %s</p></div>', __( 'Looking for more?', 'help-scout-desk' ), sprintf( __( 'Checkout <a href="%s" target="_blank">Help Scout Desk</a> from Sprout Apps.', 'help-scout-desk' ), 'https://sproutapps.co/help-scout-desk-wordpress-plugin/?utm_medium=settings&utm_campaign=hsfree&utm_source=wordpress.org' ) ); ?>
	<?php endif ?>

	<span id="ajax_saving" style="display:none" data-message="<?php _e( 'Saving...', 'help-scout-desk' ) ?>"></span>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="sprout_settings_form <?php echo $page;
	if ( $ajax ) { echo ' ajax_save';
	} if ( $ajax_full_page ) { echo ' full_page_ajax'; }  ?>">
		<?php settings_fields( $page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $page ); ?>
		<?php submit_button(); ?>
		<?php if ( $reset ) : ?>
			<?php submit_button( hsd__( 'Reset Defaults' ), 'secondary', $page.'-reset', false ); ?>
		<?php endif ?>
	</form>

	<?php do_action( 'sprout_settings_page', $page ) ?>
	<?php do_action( 'sprout_settings_page_'.$page, $page ) ?>
</div>
