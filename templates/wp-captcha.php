<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="g-recaptcha" id="um-login-recaptcha" <?php echo $attrs; ?>></div>

<script type="text/javascript">
	<?php if ( 'invisible' === $options['data-size'] ) { ?>

		var onSubmit = function( token ) {
			var me = jQuery('#loginform');
			me.attr('disabled', 'disabled');
			me.submit();
		};

		var onloadCallback = function() {
			grecaptcha.render( 'wp-submit', {
				'sitekey': '<?php echo esc_js( $options['data-sitekey'] ); ?>',
				'callback': onSubmit
			});
		};

		function um_recaptcha_refresh() {
			grecaptcha.reset();
			onloadCallback();
		}

	<?php } else { ?>

		var onloadCallback = function() {
			jQuery('.g-recaptcha').each( function (i) {
				grecaptcha.render( jQuery(this).attr('id'), {
					'sitekey': jQuery(this).data('sitekey'),
					'theme': jQuery(this).data('theme')
				});
			});
		};

		function um_recaptcha_refresh() {
			jQuery('.g-recaptcha').html('');
			grecaptcha.reset();
			onloadCallback();
		}

	<?php } ?>
</script>