<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package um_ext\um_recaptcha\common
 */
class Init {

	/**
	 * Create classes' instances where __construct isn't empty for hooks init
	 */
	public function includes() {
		if ( UM()->is_new_ui() ) {
			$this->fields();
		}
		$this->um_forms();
		$this->um_reset_password();
		$this->wp_login();
		$this->wp_login_widget();
		$this->wp_lost_password();
		$this->wp_register();
	}

	/**
	 * @return Captcha
	 */
	public function captcha() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\captcha'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\captcha'] = new Captcha();
		}
		return UM()->classes['um_ext\um_recaptcha\common\captcha'];
	}

	/**
	 * @return Fields
	 */
	public function fields() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\fields'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\fields'] = new Fields();
		}
		return UM()->classes['um_ext\um_recaptcha\common\fields'];
	}

	/**
	 * @return WP_Login
	 */
	public function wp_login() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\wp_login'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\wp_login'] = new WP_Login();
		}
		return UM()->classes['um_ext\um_recaptcha\common\wp_login'];
	}

	/**
	 * @return WP_Login_Widget
	 */
	public function wp_login_widget() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\wp_login_widget'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\wp_login_widget'] = new WP_Login_Widget();
		}
		return UM()->classes['um_ext\um_recaptcha\common\wp_login_widget'];
	}

	/**
	 * @return UM_Forms
	 */
	public function um_forms() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\um_forms'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\um_forms'] = new UM_Forms();
		}
		return UM()->classes['um_ext\um_recaptcha\common\um_forms'];
	}

	/**
	 * @return UM_Reset_Password
	 */
	public function um_reset_password() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\um_reset_password'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\um_reset_password'] = new UM_Reset_Password();
		}
		return UM()->classes['um_ext\um_recaptcha\common\um_reset_password'];
	}

	/**
	 * @return WP_Lost_Password
	 */
	public function wp_lost_password() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\wp_lost_password'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\wp_lost_password'] = new WP_Lost_Password();
		}
		return UM()->classes['um_ext\um_recaptcha\common\wp_lost_password'];
	}

	/**
	 * @return WP_Register
	 */
	public function wp_register() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\wp_register'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\wp_register'] = new WP_Register();
		}
		return UM()->classes['um_ext\um_recaptcha\common\wp_register'];
	}
}
