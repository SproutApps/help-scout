<div id="hsd_support_conversation" data-item-status="<?php esc_attr_e( $item['status'] , 'help-scout-desk' ) ?>">
	<header id="conversation_header" class="entry-header clearfix">
		<h1 class="entry-title title"><?php esc_attr_e( $item['subject'] , 'help-scout-desk' ) ?></h1>
		<div class="author">
			<span class="posted-on">
				<span class="label label-<?php hsd_status_class( $item['status'] ) ?>"><?php hsd_status_label( $item['status'] , 'help-scout-desk' ) ?></span>
				<?php
					$name = esc_attr__( $item['customer']['firstName'] , 'help-scout-desk' ) . ' ' . esc_attr__( $item['customer']['lastName'] , 'help-scout-desk' );
					$time = '<time datetime="'.esc_attr__( $item['createdAt'] , 'help-scout-desk' ).'">'.date( get_option( 'date_format' ), strtotime( esc_attr__( $item['createdAt'] , 'help-scout-desk' ) ) ).'</time>';

					printf( __( 'By %s on %s', 'help-scout-desk' ), $name, $time ); ?>
			</span>
		</div>
	</header><!-- /conversation_header -->

	<a href="<?php echo get_permalink( $post_id ) ?>" class="button hsd_goback"><?php esc_attr_e( 'Go back' , 'help-scout-desk' ) ?></a>

	<section id="hsd_conversation_thread"  class="clearfix">
		<?php
		$thread = 1;
		foreach ( array_reverse( $threads ) as $key => $data ) : ?>
			<?php if ( $data['type'] != 'lineitem' ) : ?>
				<div class="panel panel-default">
					<div class="panel-heading clearfix">
						<span class="avatar pull-left"><?php echo get_avatar( $data['createdBy']['email'], 36 ) ?></span>
						<h3 class="panel-title pull-right">
							<?php
								$name = esc_attr__( $data['createdBy']['firstName'] , 'help-scout-desk' ) . ' ' . esc_attr__( $data['createdBy']['lastName'] , 'help-scout-desk' );
								$time = '<time datetime="'.esc_attr__( $data['createdAt'] , 'help-scout-desk' ).'">'.date( get_option( 'date_format' ), strtotime( esc_attr__( $data['createdAt'] , 'help-scout-desk' ) ) ).'</time>';

								printf( __( '%s on %s', 'help-scout-desk' ), $name, $time ); ?>
						</h3>
					</div>
					<div class="panel-body">
						<div class="conversation_body clearfix">
							<div class="message">
								<?php echo wpautop( self::linkify( __( $data['body'], 'help-scout-desk' ) ) ); ?>
							</div>
							<!-- Image Attachments will be imgs -->
							<?php if ( isset( $data['attachments'] ) && ! empty( $data['attachments'] ) ) : ?>
								<div class="img_attachments_wrap clearfix">
									<ul class="attachments img_attachments clearfix">
									<?php foreach ( $data['attachments'] as $key => $att_data ) : ?>
										<?php if ( in_array( $att_data['mimeType'], array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif' ) ) ) : ?>
											<li class="image_att">
												<a target="_blank" href="<?php echo esc_url( $att_data['url'] ) ?>" class="file fancyimg" title="View Attachment"><img src="<?php echo esc_url( $att_data['url'] ) ?>" alt="<?php esc_attr_e( $att_data['fileName'] , 'help-scout-desk' ) ?>"></a>
											</li>
										<?php endif ?>
									<?php endforeach ?>
									</ul>
								</div>
								<!-- All Attachments will be linked -->
								<div class="attachments_wrap clearfix">
									<h5><?php _e( 'Attachments', 'help-scout-desk' ) ?></h5>
									<?php if ( isset( $data['attachments'] ) && ! empty( $data['attachments'] ) ) : ?>
										<ul class="attachments file_attachments">
										<?php foreach ( $data['attachments'] as $key => $att_data ) : ?>
											<li class="file_att">
												<a target="_blank" href="<?php echo esc_url( $att_data['url'] ) ?>" class="file fancyimg" title="View Attachment"><?php esc_attr_e( $att_data['fileName'] , 'help-scout-desk' ) ?></a>
											</li>
										<?php endforeach ?>
										</ul>
									<?php endif ?>
								</div>
							<?php endif ?>
						</div>
					</div>
					<div class="panel-footer">
						<?php
							printf( __( '<b>%s</b> of %s', 'help-scout-desk' ), $thread, esc_attr__( $item['threadCount'] , 'help-scout-desk' ) ); ?>
					</div>
				</div>
				<?php $thread++; // update thread count ?>
			<?php else : ?>
				<div class="line_item">
					<?php
						$name = esc_attr__( $data['createdBy']['firstName'] , 'help-scout-desk' ) . ' ' . esc_attr__( $data['createdBy']['lastName'] , 'help-scout-desk' );
						$time = '<time datetime="'.esc_attr__( $data['createdAt'] , 'help-scout-desk' ).'">'.date( get_option( 'date_format' ), strtotime( esc_attr__( $data['createdAt'] , 'help-scout-desk' ) ) ).'</time>';
						$status = sprintf( '<span class="label label-%s">%s</span>', hsd_get_status_class( $data['status'] ), hsd_get_status_label( $data['status'] ) );
						printf( __( '%s by %s on %s', 'help-scout-desk' ), $status, $name, $time ); ?>
				</div>
			<?php endif ?>
		<?php
		endforeach; ?>
	</section><!-- #hsd_conversation_thread -->

</div><!-- #hsd_support_conversation -->
