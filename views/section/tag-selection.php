<div class="form-group">
	<label for="hs_thread_type" class="hidden"><?php _e( 'Select Topic', 'help-scout-desk' ) ?></label>
	<select name="hs_thread_type" class="hsd_select select_input">
		<option><?php _e( 'Select Topic', 'help-scout-desk' ) ?></option>
		<?php foreach ( $tags as $key => $tag ) : ?>
			<option value="<?php esc_attr_e( $tag->id ) ?>"><?php esc_attr_e( $tag->tag ) ?></option>
		<?php endforeach ?>
	</select>
</div>