<?php foreach ( $converation_tags as $key => $tag ) : ?>
	<span class="label label-default label-<?php esc_attr_e( $tag ) ?>"><?php echo $tag ?></span> 
<?php endforeach ?>