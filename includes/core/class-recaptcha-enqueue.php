<?php
namespace um_ext\um_recaptcha\core;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class reCAPTCHA_Enqueue
 * @package um_ext\um_recaptcha\core
 */
class reCAPTCHA_Enqueue {


	/**
	 * reCAPTCHA_Enqueue constructor.
	 */
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), 0 );
	}


	/**
	 * reCAPTCHA scripts/styles enqueue
	 */
	function wp_enqueue_scripts() {
		wp_register_style( 'um_recaptcha', um_recaptcha_url . 'assets/css/um-recaptcha.css' );
		wp_enqueue_style( 'um_recaptcha' );

		$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
		wp_enqueue_script(
			'google-recapthca-api',
			"https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code"
		);
	}

}