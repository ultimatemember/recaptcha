<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 * @version 2.4.0
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/captcha.php
 * @var string $form_id
 * @var string $mode
 * @var string $size
 * @var string $theme
 * @var string $sitekey
 * @var string $version
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'v2' === $version && 'invisible' !== $size ) {
	?>
	<div class="um-field um-field-recaptcha um-field-type_recaptcha">
	<?php
}
?>
	<div class="um-recaptcha" id="um-<?php echo esc_attr( $form_id ); ?>-recaptcha" data-mode="<?php echo esc_attr( $mode ); ?>" data-size="<?php echo esc_attr( $size ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" data-sitekey="<?php echo esc_attr( $sitekey ); ?>"></div>
<?php
if ( 'v2' === $version && 'invisible' !== $size ) {
	?>
	</div>
	<?php
}
if ( UM()->form()->has_error( 'recaptcha' ) ) {
	?>
	<div class="um-field-error"><?php echo wp_kses( UM()->form()->errors['recaptcha'], UM()->get_allowed_html( 'templates' ) ); ?></div>
	<?php
}
