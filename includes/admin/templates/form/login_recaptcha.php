<div class="um-admin-metabox">

	<?php $status = um_get_option('g_recaptcha_status'); ?>
	<?php if ( $status ) { ?>
	
	<p><?php _e('Google reCAPTCHA seems to be <strong style="color:#7ACF58">enabled</strong> by default.','um-recaptcha'); ?></p>
	
	<?php } else { ?>
	
	<p><?php _e('Google reCAPTCHA seems to be <strong style="color:#C74A4A">disabled</strong> by default.','um-recaptcha'); ?></p>
	
	<?php } ?>

	<?php
		UM()->admin_forms( array(
			'class'     => 'um-role-g_recaptcha um-top-label',
			'prefix_id' => 'form',
			'fields'    => array(
				array(
					'id'    => '_um_login_g_recaptcha_status',
					'type'  => 'checkbox',
					'label' => __('reCAPTCHA status on this form','um-recaptcha' ),
					'value' => !empty( $role['_um_login_g_recaptcha_status'] ) ? $role['_um_login_g_recaptcha_status'] : $status,
					'default' => $status,
				),
			)
		) )->render_form();

	?>

</div>