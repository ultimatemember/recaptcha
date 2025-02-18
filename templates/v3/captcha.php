<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 * @version 2.3.9
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/v3/captcha.php
 * @var int|string $form_id
 * @var string     $type
 * @var string     $size
 * @var string     $theme
 * @var string     $sitekey
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.
?>

<div class="um-field">
	<div class="g-recaptcha" id="um-<?php echo esc_attr( $form_id ); ?>" data-type="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $type ); ?>" data-size="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $size ); ?>" data-theme="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $theme ); ?>" data-sitekey="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $sitekey ); ?>"></div>
	<?php if ( UM()->form()->has_error( 'recaptcha' ) ) { ?>
		<p class="um-field-hint um-field-error"><?php echo wp_kses( UM()->form()->errors['recaptcha'], UM()->get_allowed_html( 'templates' ) ); ?></p>
	<?php } ?>
</div>

<script type="text/javascript">
	<?php if ( 'invisible' === $size ) { ?>

		var onSubmit = function( token ) {
			var me = jQuery('.um-<?php echo esc_js( $form_id ); ?> form');
			me.attr('disabled', 'disabled');
			me.submit();
		};

		var onloadCallback = function() {
			grecaptcha.render('um-submit-btn', {
				'sitekey': '<?php echo esc_js( $sitekey ); ?>',
				'callback': onSubmit
			});
		};

		function um_recaptcha_refresh() {
			grecaptcha.reset();
			onloadCallback();
		}

		jQuery(document).ready( function() {
			jQuery('.um-<?php echo esc_js( $form_id ); ?> #um-submit-btn').addClass('um-has-recaptcha');
		});

	<?php } else { ?>

		var onloadCallback = function () {
			jQuery('.g-recaptcha').each( function (i) {
				grecaptcha.render( jQuery(this).attr('id'), {
					'sitekey': jQuery(this).attr('data-sitekey'),
					'theme': jQuery(this).attr('data-theme')
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
