<?php
namespace um_ext\um_recaptcha\common;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Lost_Password
 *
 * @package um_ext\um_recaptcha\common
 */
class WP_Lost_Password extends Captcha {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		// lost password forms
		add_filter( 'login_body_class', array( $this, 'extends_login_body_class' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'lostpassword_form', array( $this, 'add_recaptcha' ) );
		add_filter( 'lostpassword_errors', array( $this, 'validate_recaptcha' ) );
	}

	public static function is_allowed( $args = null ) {
		$enable = false;

		if ( self::are_keys_valid() && UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) {
			$enable = true;
		}

		return $enable;
	}

	/**
	 * Add classes on wp-login.php page
	 *
	 * @param array  $classes
	 * @param string $action
	 *
	 * @return array
	 */
	public function extends_login_body_class( $classes, $action ) {
		if ( ! ( 'lostpassword' === $action || 'retrievepassword' === $action ) ) {
			return $classes;
		}

		if ( ! self::is_allowed() ) {
			return $classes;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( 'v3' === $version ) {
			return $classes;
		}

		$type = UM()->options()->get( 'g_recaptcha_size' );
		if ( 'invisible' === $type ) {
			return $classes;
		}

		$classes[] = ( 'normal' === $type ) ? 'has-normal-um-recaptcha' : 'has-compact-um-recaptcha';

		return $classes;
	}

	/**
	 * Enqueue assets on wp-login.php page
	 */
	public function enqueue_scripts() {
		global $action;

		if ( ! ( 'lostpassword' === $action || 'retrievepassword' === $action ) ) {
			return;
		}

		if ( ! self::is_allowed() ) {
			return;
		}

		$this->enqueue_wp_recaptcha_scripts();
	}

	/**
	 * Add reCAPTCHA block to the wp-login.php page Lost Password mode
	 */
	public function add_recaptcha() {
		if ( ! self::is_allowed() ) {
			return;
		}

		echo wp_kses( self::render_wp_captcha( array( 'mode' => 'lost_password' ) ), UM()->get_allowed_html( 'templates' ) );
	}

	/**
	 * @param WP_Error $errors
	 *
	 * @return mixed
	 */
	public function validate_recaptcha( $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php
		if ( self::um_is_api_request() ) {
			return $errors;
		}

		if ( is_admin() ) {
			return $errors;
		}

		if ( ! self::is_allowed() ) {
			return $errors;
		}

		$version     = UM()->options()->get( 'g_recaptcha_version' );
		$your_secret = self::get_key( 'secret' );

		switch ( $version ) {
			case 'v3':
				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}
				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_lostpassword_form' );
				if ( ! empty( $result ) ) {
					$validate_score = $this->get_v3_score();

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						$errors->add( 'um-recaptcha-score', __( '<strong>Error</strong>: It is very likely a bot.', 'um-recaptcha' ) );

						return $errors;
					}

					if ( isset( $result->action ) && 'lost_password' !== $result->action ) {
						$errors->add( 'um-recaptcha-invalid-action', __( '<strong>Error</strong>: Invalid response the `action` parameter.', 'um-recaptcha' ) );
						return $errors;
					}

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = $this->error_codes_list();

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

							return $errors;
						}
					}
				}
				break;

			case 'v2':
			default:
				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_lostpassword_form' );
				if ( ! empty( $result ) && isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = $this->error_codes_list();

					foreach ( $result->{'error-codes'} as $key => $error_code ) {
						$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
						$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

						return $errors;
					}
				}
				break;

		}

		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php
	}
}
