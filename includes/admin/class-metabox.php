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

		add_filter( 'um_form_meta_map', array( &$this, 'form_meta_map' ) );
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

	/**
	 * Merges additional form meta keys and their sanitize methods into the existing form meta map.
	 *
	 * @param array $map The current form meta map.
	 *
	 * @return array The updated form meta map with additional keys and sanitize methods.
	 */
	public function form_meta_map( $map ) {
		$new_map = array(
			'_um_login_g_recaptcha_status'    => array(
				'sanitize' => 'absint',
			),
			'_um_login_g_recaptcha_score'     => array(
				'sanitize' => 'text',
			),
			'_um_register_g_recaptcha_status' => array(
				'sanitize' => 'absint',
			),
			'_um_register_g_recaptcha_score'  => array(
				'sanitize' => 'text',
			),
		);

		return array_merge( $map, $new_map );
	}
}
