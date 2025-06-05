<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Login_Widget
 *
 * @package um_ext\um_recaptcha\common
 */
class WP_Login_Widget extends Captcha {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'login_form_middle', array( $this, 'add_recaptcha' ), 10, 2 );

		add_filter( 'um_recaptcha_wp_authenticate_is_allowed', array( $this, 'wp_authenticate_is_allowed' ) );
		$this->init_authenticate_validation();
	}

	public static function is_allowed( $args = null ) {
		$enable = false;

		if ( self::are_keys_valid() && UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ) {
			$enable = true;
		}

		return $enable;
	}

	public static function wp_authenticate_is_allowed( $is_allowed ) {
		if ( $is_allowed ) {
			return $is_allowed;
		}
		return ! empty( $_REQUEST['um_login_form'] ) && self::is_allowed(); // phpcs:ignore WordPress.Security.NonceVerification -- just getting value for condition logic
	}

	/**
	 * @param $content
	 * @param $args
	 *
	 * @return string
	 */
	public function add_recaptcha( $content, $args ) {
		if ( ! self::is_allowed() ) {
			return $content;
		}

		if ( ! ( array_key_exists( 'um_login_form', $args ) && true === $args['um_login_form'] ) ) {
			return $content;
		}

		$this->enqueue_wp_recaptcha_scripts();

		$content .= self::render_wp_captcha();
		return $content;
	}
}
