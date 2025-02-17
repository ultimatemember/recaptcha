<?php
namespace um_ext\um_recaptcha\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Metabox
 *
 * @package um_ext\um_recaptcha\admin
 */
class Metabox {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_action( 'um_admin_custom_register_metaboxes', array( &$this, 'add_metabox_register' ), 10 );
		add_action( 'um_admin_custom_login_metaboxes', array( &$this, 'add_metabox_login' ), 10 );
	}

	/**
	 * Adding metabox for the UM Form type = register
	 */
	public function add_metabox_register() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_meta_box(
			'um-admin-form-register-recaptcha{' . UM_RECAPTCHA_PATH . '}',
			__( 'Google reCAPTCHA', 'um-recaptcha' ),
			array( UM()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);
	}

	/**
	 * Adding metabox for the UM Form type = login
	 */
	public function add_metabox_login() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_meta_box(
			'um-admin-form-login-recaptcha{' . UM_RECAPTCHA_PATH . '}',
			__( 'Google reCAPTCHA', 'um-recaptcha' ),
			array( UM()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);
	}
}
