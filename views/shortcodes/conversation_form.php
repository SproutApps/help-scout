<?php
if ( ! is_user_logged_in() ) {
	return;
} ?>

<?php if ( $error ) : ?>
	<div class="alert alert-danger" role="alert"><?php echo esc_attr( $error, 'help-scout-desk' ) ?></div>
<?php endif ?>

<form action="" method="post" enctype="multipart/form-data" id="hsd_message_form" class="form" role="form">
	
	<?php do_action( 'hsd_form_start' ) ?>

	<?php if ( ! $conversation_view ) : ?>
		<?php do_action( 'hsd_form_subject' ) ?>
		<div class="form-group">
			<label for="subject"><?php esc_html_e( 'Subject', 'help-scout-desk' ) ?></label>
			<input type="text" class="form-control" id="hsd_subject" name="subject" placeholder="<?php esc_attr_e( 'How can we help?', 'help-scout-desk' ) ?>" required="required">
		</div>
	<?php endif ?>
	
	<?php do_action( 'hsd_form_message' ) ?>

	<div class="form-group">
		<label for="message"><?php esc_html_e( 'Message', 'help-scout-desk' ) ?></label>
		<textarea name="message" class="form-control" id="hsd_message" rows="10" placeholder="<?php esc_attr_e( 'Please include any information that you think will help us generate a speedy response.', 'help-scout-desk' ) ?>" required="required" ></textarea>
		<?php if ( $conversation_view ) : ?>
			<p class="help-block"><?php esc_html_e( 'This will add a message to our current conversation.', 'help-scout-desk' ) ?></p>
		<?php endif ?>
	</div>

	<?php do_action( 'hsd_form_attachments' ) ?>

	<div class="form-group">
		<label for="message_attachment"><?php esc_html_e( 'Add attachments', 'help-scout-desk' ) ?></label>
		<input type="file" id="message_attachment" name="message_attachment[]" multiple>
	</div>

	<?php do_action( 'hsd_form_close_thread' ) ?>

	<?php if ( $conversation_view ) : ?>
		<div id="close_thread_check" class="checkbox">
			<label for="close_thread"><input type="checkbox" name="close_thread" id="close_thread"> <?php esc_html_e( 'Close Support Thread', 'help-scout-desk' ) ?></label>
		</div>
	<?php endif ?>

	<?php do_action( 'hsd_form_hidden_values' ) ?>

	<?php if ( $conversation_view ) : ?>
		<input type="hidden" name="hsd_conversation_id" value="<?php echo esc_attr( $_GET['conversation_id'] ) ?>">
	<?php endif ?>
	<input type="hidden" name="mid" value="<?php echo esc_attr( $mid ) ?>">
	<input type="hidden" name="hsd_nonce" value="<?php echo wp_create_nonce( HSD_Controller::NONCE ) ?>">

	<?php do_action( 'hsd_form_submit' ) ?>

	<button type="submit" id="hsd_submit" class="button"><?php esc_html_e( 'Submit', 'help-scout-desk' ) ?></button>

	<?php do_action( 'hsd_form_end' ) ?>
</form>
