<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Login
 *
 * @package um_ext\um_recaptcha\common
 */
class WP_Login extends Captcha {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'login_body_class', array( $this, 'extends_login_body_class' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'login_form', array( $this, 'add_recaptcha' ) );

		add_filter( 'um_recaptcha_wp_authenticate_is_allowed', array( $this, 'wp_authenticate_is_allowed' ) );
		$this->init_authenticate_validation();
	}

	public static function is_allowed( $args = null ) {
		$enable = false;

		if ( self::are_keys_valid() && UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			$enable = true;
		}

		return $enable;
	}

	public static function wp_authenticate_is_allowed( $is_allowed ) {
		if ( $is_allowed ) {
			return $is_allowed;
		}

		return empty( $_REQUEST['um_login_form'] ) && self::is_allowed(); // phpcs:ignore WordPress.Security.NonceVerification -- just getting value for condition logic
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
		if ( 'login' !== $action ) {
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

		if ( 'login' !== $action ) {
			return;
		}

		if ( ! self::is_allowed() ) {
			return;
		}

		$this->enqueue_wp_recaptcha_scripts();
	}

	/* Handle reCAPTCHA via `wp_login_form()` */
	/**
	 * Add reCAPTCHA block to the wp-login.php page
	 */
	public function add_recaptcha() {
		if ( ! self::is_allowed() ) {
			return;
		}

		echo wp_kses( self::render_wp_captcha(), UM()->get_allowed_html( 'templates' ) );
	}
}
