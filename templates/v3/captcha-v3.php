<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 * @version 2.3.9
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/v3/captcha-v3.php
 * @var int|string $form_id
 * @var string     $mode
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recaptcha_mode = empty( $mode ) ? 'homepage' : $mode;
?>
<div class="um-field">
	<div class="g-recaptcha" id="um-<?php echo esc_attr( $form_id ); ?>" data-mode="<?php echo esc_attr( $recaptcha_mode ); ?>"></div>
	<?php
	if ( UM()->form()->has_error( 'recaptcha' ) ) {
		?>
		<p class="um-field-hint um-field-error"><?php echo wp_kses( UM()->form()->errors['recaptcha'], UM()->get_allowed_html( 'templates' ) ); ?></p>
		<?php
	}
	?>
</div>
