<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Capthca
 *
 * @package um_ext\um_recaptcha\common
 */
class Capthca {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
	}

	/**
	 * Captcha allowed
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function captcha_allowed( $args ) {
		$enable = false;

		$recaptcha    = UM()->options()->get( 'g_recaptcha_status' );
		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( $recaptcha ) {
			$enable = true;
		}

		if ( isset( $args['g_recaptcha_status'] ) ) {
			$enable = (bool) $args['g_recaptcha_status'];
		}

		if ( ! $your_sitekey || ! $your_secret ) {
			$enable = false;
		}

		if ( isset( $args['mode'] ) && 'password' === $args['mode'] && ! UM()->options()->get( 'g_recaptcha_password_reset' ) ) {
			$enable = false;
		}

		return ( false === $enable ) ? false : true;
	}
}
