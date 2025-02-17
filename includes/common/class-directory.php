<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um_ext\um_recaptcha\common
 */
class Directory {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_action( 'um_pre_directory_shortcode', 'um_recaptcha_directory_enqueue_scripts', 10, 1 );
	}

	/**
	 * reCAPTCHA scripts/styles enqueue in member directory
	 *
	 * @param array $args
	 */
	public function um_recaptcha_directory_enqueue_scripts( $args ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$global_hide_pm_button = ! empty( $args['hide_pm_button'] ) ? $args['hide_pm_button'] : ! UM()->options()->get( 'show_pm_button' );
		if ( $global_hide_pm_button ) {
			return;
		}

		$form_data = UM()->query()->post_data( UM()->shortcodes()->core_login_form() );
		if ( empty( $form_data ) || ! is_array( $form_data ) || ! array_key_exists( 'mode', $form_data ) ) {
			return;
		}

		$allowed_args = array(
			'mode' => $form_data['mode'],
		);
		if ( isset( $form_data['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $form_data['g_recaptcha_status'];
		}

		if ( ! UM()->ReCAPTCHA()->common()->capthca()->captcha_allowed( $allowed_args ) ) {
			return;
		}

		wp_enqueue_style( 'um-recaptcha' );
		wp_enqueue_script( 'um-recaptcha' );
	}
}
