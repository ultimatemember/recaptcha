<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_add_recaptcha_wp_lostpassword_form(), um_add_recaptcha_login_form() functions
 * @version 2.4.0
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/captcha.php
 * @var string $mode
 * @var string $size
 * @var string $theme
 * @var string $sitekey
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um-recaptcha" id="um-wp-login-recaptcha" data-mode="<?php echo esc_attr( $mode ); ?>" data-size="<?php echo esc_attr( $size ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" data-sitekey="<?php echo esc_attr( $sitekey ); ?>"></div>
