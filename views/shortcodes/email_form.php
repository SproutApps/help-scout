<div class="row">
	<div class="col-sm-offset-3 col-md-6">
		<?php if ( $error ) : ?>
			<div class="alert alert-danger" role="alert"><?php esc_attr_e( $error , 'help-scout-desk' ) ?></div>
		<?php endif ?>

		<form id="hsd_email_form" action="" method="post" accept-charset="utf-8" class="form" role="form">
			<div class="form-group">
				<label for="email"><?php _e( 'Your e-mail' , 'help-scout-desk' ) ?></label>
				<input type="email" class="form-control" id="hsd_email" name="email" placeholder="<?php _e( 'you@gmail.com' , 'help-scout-desk' ) ?>" required="required">
			</div>
			<?php do_action( 'hsd_email_form_fields' ) ?>
			<input type="hidden" name="mid" value="<?php echo $mid ?>">
			<input type="hidden" name="hsd_nonce" value="<?php echo $nonce ?>">
			<button type="submit" class="button"><?php _e( 'Submit' , 'help-scout-desk' ) ?></button>
		</form>
	</div><!-- .col-sm-offset-3 col-md-6 -->	
</div><!-- .row -->
