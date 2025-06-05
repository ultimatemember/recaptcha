<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Lost_Password
 *
 * @package um_ext\um_recaptcha\common
 */
class UM_Reset_Password extends Captcha {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'um_password_reset_form_primary_btn_classes', array( $this, 'extends_classes' ) );
		add_action( 'um_pre_password_shortcode', array( $this, 'enqueue_scripts' ) );

		add_action( 'um_after_password_reset_fields', array( $this, 'add_captcha' ), 500 );
		add_action( 'um_reset_password_errors_hook', array( $this, 'validate_recaptcha' ), 20 );
	}

	public static function is_allowed( $args = null ) {
		$enable = false;

		if ( self::are_keys_valid() && UM()->options()->get( 'g_recaptcha_password_reset' ) ) {
			$enable = true;
		}

		return $enable;
	}

	public function extends_classes( $classes ) {
		if ( ! self::is_allowed() ) {
			return $classes;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( 'v3' === $version ) {
			return $classes;
		}

		$type = UM()->options()->get( 'g_recaptcha_size' );
		if ( 'invisible' === $type ) {
			$classes[] = 'um-has-recaptcha';
		}

		return $classes;
	}

	/**
	 * reCAPTCHA scripts/styles enqueue in the page with a form
	 *
	 */
	public function enqueue_scripts() {
		if ( ! self::is_allowed() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification -- just getting value for condition logic
		if ( ! empty( $_GET['updated'] ) ) {
			return;
		}

		$this->enqueue_um_recaptcha_scripts();
	}

	/**
	 * Add password reset form recaptcha
	 *
	 */
	public function add_captcha() {
		if ( ! self::is_allowed() ) {
			return;
		}

		if ( UM()->is_new_ui() ) {
			echo '<div class="um-form-row um-recaptcha-row">';
			echo '<div class="um-form-cols um-form-cols-1">';
			echo '<div class="um-form-col um-form-col-1">';
		}
		echo wp_kses(
			self::render_um_captcha(
				array(
					'form_id' => 'lost-password',
					'mode'    => 'lost_password',
				)
			),
			UM()->get_allowed_html( 'templates' )
		);
		if ( UM()->is_new_ui() ) {
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Reset Password form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $args
	 */
	public function validate_recaptcha( $args ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
		if ( ! self::is_allowed() ) {
			return;
		}

		$your_secret = self::get_key( 'secret' );

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			UM()->form()->add_error( 'recaptcha', __( 'Please confirm you are not a robot', 'um-recaptcha' ) );
			return;
		}

		$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

		$result = $this->remote_request( $your_secret, $client_captcha_response, 'um_reset_password_shortcode', compact( 'args' ) );

		if ( ! empty( $result ) ) {
			$validate_score = $this->get_v3_score( $args );

			if ( isset( $result->score ) && $result->score < $validate_score ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: it is very likely a bot.', 'um-recaptcha' ) );
			} elseif ( isset( $result->action ) && 'lost_password' !== $result->action ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: Invalid response the `action` parameter.', 'um-recaptcha' ) );
			} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
				$error_codes = $this->error_codes_list();

				foreach ( $result->{'error-codes'} as $key => $error_code ) {
					// translators: %s: Google reCAPTCHA error code
					$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'um-recaptcha' ), $error_code );
					UM()->form()->add_error( 'recaptcha', $error );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
	}
}
