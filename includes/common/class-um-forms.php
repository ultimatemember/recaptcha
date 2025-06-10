<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UM_Forms
 *
 * @package um_ext\um_recaptcha\common
 */
class UM_Forms extends Captcha {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'um_login_form_primary_btn_classes', array( $this, 'extends_classes' ) ); // new/old UI both
		add_filter( 'um_register_form_primary_btn_classes', array( $this, 'extends_classes' ) ); // new/old UI both

		// enqueue script
		add_action( 'um_pre_register_shortcode', array( $this, 'enqueue_scripts' ) );
		add_action( 'um_pre_login_shortcode', array( $this, 'enqueue_scripts' ) );

		add_filter( 'um_get_form_fields', array( &$this, 'extends_fields' ), 999, 2 ); // new UI only with max priority to append at the end of the form.

		add_action( 'um_submit_form_errors_hook', array( $this, 'validate_recaptcha' ), 20, 2 );
		add_filter( 'login_errors', array( $this, 'remove_general_login_error' ), 10, 2 );

		add_action( 'um_after_register_fields', array( $this, 'add_captcha' ), 500 ); // old UI
		add_action( 'um_after_login_fields', array( $this, 'add_captcha' ), 500 ); // old UI
	}

	/**
	 * Captcha allowed
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public static function is_allowed( $args = null ) {
		$enable = false;

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( $recaptcha ) {
			$enable = true;
		}

		if ( isset( $args['g_recaptcha_status'] ) ) {
			$enable = (bool) $args['g_recaptcha_status'];
		}

		if ( $enable && isset( $args['mode'] ) ) {
			$enable = in_array( $args['mode'], array( 'login', 'register' ), true );
		}

		if ( ! self::are_keys_valid() ) {
			$enable = false;
		}

		return false !== $enable;
	}

	public function extends_classes( $classes ) {
		if ( ! self::is_allowed() ) {
			return $classes;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( 'v3' === $version ) {
			return $classes;
		}

		$type = UM()->options()->get( 'g_recaptcha_size' );
		if ( 'invisible' === $type ) {
			$classes[] = 'um-has-recaptcha';
		}

		return $classes;
	}

	/**
	 * reCAPTCHA scripts/styles enqueue in the page with a form
	 *
	 * @param array $args
	 */
	public function enqueue_scripts( $args ) {
		$allowed_args = array();
		if ( isset( $args['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
		}

		if ( ! self::is_allowed( $allowed_args ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification -- just getting value for condition logic
		if ( isset( $_GET['message'] ) && in_array( sanitize_key( wp_unslash( $_GET['message'] ) ), array( 'approved', 'checkmail', 'pending' ), true ) ) {
			return;
		}

		$this->enqueue_um_recaptcha_scripts();
	}

	/**
	 * Extends fields on the login/registration form
	 *
	 * New UI only callback.
	 *
	 * @param array $fields
	 * @param int   $form_id
	 *
	 * @return array
	 */
	public function extends_fields( $fields, $form_id ) {
		if ( ! UM()->is_new_ui() ) {
			return $fields;
		}

		$allowed_args = array();
		$args         = UM()->query()->post_data( $form_id );
		if ( isset( $args['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
		}
		if ( isset( $args['mode'] ) ) {
			$allowed_args['mode'] = $args['mode'];
		}
		if ( ! self::is_allowed( $allowed_args ) ) {
			return $fields;
		}

		$recaptcha_row_key = 'um-recaptcha-row';

		$fields[ $recaptcha_row_key ] = array(
			'type'     => 'row',
			'id'       => $recaptcha_row_key,
			'sub_rows' => 1,
			'cols'     => 1,
			'origin'   => $recaptcha_row_key,
			'margin'   => '0px',
		);

		$recaptcha_field = UM()->builtin()->get_specific_fields( 'um_recaptcha' );
		foreach ( $recaptcha_field as $key => $data ) {
			if ( 'um_recaptcha' !== $key ) {
				continue;
			}

			$data['content'] = self::render_um_captcha(
				array(
					'form_id' => $args['mode'],
					'mode'    => $args['mode'],
				)
			);

			$data['in_row']     = $recaptcha_row_key;
			$data['in_sub_row'] = '0';
			$data['in_column']  = '1';
			$data['in_group']   = '';
			$data['position']   = 1;

			$fields[ $key ] = $data;
		}

		return $fields;
	}

	/**
	 * Login|Register form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param array $args
	 * @param array $form_data
	 */
	public function validate_recaptcha( $args, $form_data = array() ) {
		// TODO Investigate integration with Social Login.
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
		if ( ! isset( $args['_social_login_form'] ) && ( ! isset( $form_data['mode'] ) || ! in_array( $form_data['mode'], array( 'login', 'register' ), true ) ) ) {
			return;
		}

		$allowed_args = array();
		if ( isset( $form_data['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $form_data['g_recaptcha_status'];
		}
		if ( ! self::is_allowed( $allowed_args ) ) {
			return;
		}

		$your_secret = self::get_key( 'secret' );

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			UM()->form()->add_error( 'recaptcha', __( 'Please confirm you are not a robot', 'um-recaptcha' ) );
			return;
		}

		$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

		$result = $this->remote_request( $your_secret, $client_captcha_response, 'um_form_shortcode', compact( 'args', 'form_data' ) );
		if ( ! empty( $result ) ) {
			$validate_score = $this->get_v3_score( $args, $form_data );

			if ( isset( $result->score ) && $result->score < $validate_score ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: it is very likely a bot.', 'um-recaptcha' ) );
			} elseif ( isset( $result->action ) && $form_data['mode'] !== $result->action ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: Invalid response the `action` parameter.', 'um-recaptcha' ) );
			} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
				$error_codes = $this->error_codes_list();

				foreach ( $result->{'error-codes'} as $key => $error_code ) {
					// translators: %s: Google reCAPTCHA error code
					$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'um-recaptcha' ), $error_code );
					UM()->form()->add_error( 'recaptcha', $error );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
	}

	/**
	 * Don't display reCAPTCHA error message twice on login
	 *
	 * @since 2.2.1
	 *
	 * @param string $error_message  Error message
	 * @param string $error_key      A key of the error
	 *
	 * @return string Filtered error message
	 */
	public function remove_general_login_error( $error_message, $error_key = null ) {
		if ( 'recaptcha' === $error_key ) {
			$error_message = '';
		}
		return $error_message;
	}

	/**
	 * Add recaptcha
	 * old UI
	 *
	 * @param array $args
	 * @todo deprecate since old UI is deprecated
	 */
	public function add_captcha( $args ) {
		if ( UM()->is_new_ui() ) {
			return;
		}

		$allowed_args = array();
		if ( isset( $args['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
		}
		if ( ! self::is_allowed( $allowed_args ) ) {
			return;
		}

		echo wp_kses(
			self::render_um_captcha(
				array(
					'form_id' => $args['mode'],
					'mode'    => $args['mode'],
				)
			),
			UM()->get_allowed_html( 'templates' )
		);
	}
}
