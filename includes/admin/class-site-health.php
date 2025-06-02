<?php
namespace um_ext\um_recaptcha\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class Site_Health
 *
 * @package um_ext\um_recaptcha\admin
 */
class Site_Health {

	/**
	 * Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'debug_information' ), 20 );
		add_filter( 'um_debug_information_register_form', array( $this, 'debug_information_register_form' ), 20, 2 );
		add_filter( 'um_debug_information_login_form', array( $this, 'debug_information_login_form' ), 20, 2 );
	}

	public function debug_information( $info ) {
		$labels = array(
			'yes' => __( 'Yes', 'um-recaptcha' ),
			'no'  => __( 'No', 'um-recaptcha' ),
		);

		$info['um-recaptcha'] = array(
			'label'       => __( 'UM Google reCAPTCHA', 'um-recaptcha' ),
			'description' => __( 'This debug information for your UM Google reCAPTCHA extension installation can assist you in getting support.', 'um-recaptcha' ),
			'fields'      => array(
				'g_recaptcha_status'  => array(
					'label' => __( 'Enable Google reCAPTCHA', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_status' ) ? $labels['yes'] : $labels['no'],
				),
				'g_recaptcha_version' => array(
					'label' => __( 'reCAPTCHA type', 'um-recaptcha' ),
					'value' => 'v2' === UM()->options()->get( 'g_recaptcha_version' ) ? __( 'reCAPTCHA v2', 'um-recaptcha' ) : __( 'reCAPTCHA v3', 'um-recaptcha' ),
				),
			),
		);

		if ( 'v3' === UM()->options()->get( 'g_recaptcha_version' ) ) {
			$info['um-recaptcha']['fields'] = array_merge(
				$info['um-recaptcha']['fields'],
				array(
					'g_reCAPTCHA_site_key'   => array(
						'label' => __( 'Site Key', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_reCAPTCHA_site_key' ) ? $labels['yes'] : $labels['no'],
					),
					'g_reCAPTCHA_secret_key' => array(
						'label' => __( 'Secret Key', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_reCAPTCHA_secret_key' ) ? $labels['yes'] : $labels['no'],
					),
					'g_reCAPTCHA_score'      => array(
						'label' => __( 'reCAPTCHA Score', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_reCAPTCHA_score' ),
					),
				)
			);
		} else {
			$sizes = array(
				'compact'   => __( 'Compact', 'um-recaptcha' ),
				'normal'    => __( 'Normal', 'um-recaptcha' ),
				'invisible' => __( 'Invisible', 'um-recaptcha' ),
			);

			$info['um-recaptcha']['fields'] = array_merge(
				$info['um-recaptcha']['fields'],
				array(
					'g_recaptcha_sitekey'       => array(
						'label' => __( 'Site Key', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_recaptcha_sitekey' ) ? $labels['yes'] : $labels['no'],
					),
					'g_recaptcha_secretkey'     => array(
						'label' => __( 'Secret Key', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_recaptcha_secretkey' ) ? $labels['yes'] : $labels['no'],
					),
					'g_recaptcha_type'          => array(
						'label' => __( 'Type', 'um-recaptcha' ),
						'value' => 'audio' === UM()->options()->get( 'g_recaptcha_type' ) ? __( 'Audio', 'um-recaptcha' ) : __( 'Image', 'um-recaptcha' ),
					),
					'g_recaptcha_language_code' => array(
						'label' => __( 'Language', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_recaptcha_language_code' ) ? UM()->options()->get( 'g_recaptcha_language_code' ) : 'en',
					),
					'g_recaptcha_size'          => array(
						'label' => __( 'Size', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_recaptcha_size' ) ? $sizes[ UM()->options()->get( 'g_recaptcha_size' ) ] : $sizes['normal'],
					),
					'g_recaptcha_theme'         => array(
						'label' => __( 'Theme', 'um-recaptcha' ),
						'value' => UM()->options()->get( 'g_recaptcha_theme' ) ? UM()->options()->get( 'g_recaptcha_theme' ) : __( 'Light', 'um-recaptcha' ),
					),
				)
			);
		}

		$info['um-recaptcha']['fields'] = array_merge(
			$info['um-recaptcha']['fields'],
			array(
				'g_recaptcha_password_reset'       => array(
					'label' => __( 'Enable Google reCAPTCHA on the UM password reset form', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_password_reset' ) ? $labels['yes'] : $labels['no'],
				),
				'g_recaptcha_wp_lostpasswordform'  => array(
					'label' => __( 'Enable Google reCAPTCHA on wp-login.php lost password form', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ? $labels['yes'] : $labels['no'],
				),
				'g_recaptcha_wp_login_form'        => array(
					'label' => __( 'Enable Google reCAPTCHA on wp-login.php form', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_wp_login_form' ) ? $labels['yes'] : $labels['no'],
				),
				'g_recaptcha_wp_login_form_widget' => array(
					'label' => __( 'Enable Google reCAPTCHA on login form through `wp_login_form()`', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ? $labels['yes'] : $labels['no'],
				),
				'g_recaptcha_wp_register_form'     => array(
					'label' => __( 'Enable Google reCAPTCHA on wp-login.php registration form', 'um-recaptcha' ),
					'value' => UM()->options()->get( 'g_recaptcha_wp_register_form' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

		return $info;
	}

	public function debug_information_register_form( $info, $key ) {
		$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
			$info[ 'ultimate-member-' . $key ]['fields'],
			array(
				'um_register_g_recaptcha_status' => array(
					'label' => __( 'reCAPTCHA status on this form', 'um-recaptcha' ),
					'value' => ! empty( get_post_meta( $key, '_um_register_g_recaptcha_status', true ) ) ? __( 'Yes', 'um-recaptcha' ) : __( 'No', 'um-recaptcha' ),
				),
			)
		);

		return $info;
	}

	public function debug_information_login_form( $info, $key ) {
		$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
			$info[ 'ultimate-member-' . $key ]['fields'],
			array(
				'um_login_g_recaptcha_status' => array(
					'label' => __( 'reCAPTCHA status on this form', 'um-recaptcha' ),
					'value' => ! empty( get_post_meta( $key, '_um_login_g_recaptcha_status', true ) ) ? __( 'Yes', 'um-recaptcha' ) : __( 'No', 'um-recaptcha' ),
				),
			)
		);

		return $info;
	}
}
