<?php
namespace um_ext\um_recaptcha\common;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Captcha
 *
 * @package um_ext\um_recaptcha\common
 */
class Captcha {

	/**
	 * @return bool
	 */
	protected static function um_is_api_request() {
		$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
		return apply_filters( 'um_is_api_request', $is_api_request );
	}

	public static function get_key( $type = 'site' ) {
		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( ! in_array( $type, array( 'site', 'secret' ), true ) ) {
			return false;
		}

		if ( 'secret' === $type ) {
			$option_key = 'v3' === $version ? 'g_reCAPTCHA_secret_key' : 'g_recaptcha_secretkey';
		} else {
			$option_key = 'v3' === $version ? 'g_reCAPTCHA_site_key' : 'g_recaptcha_sitekey';
		}

		return trim( UM()->options()->get( $option_key ) );
	}

	public static function are_keys_valid() {
		static $is_valid = null;

		if ( ! is_null( $is_valid ) ) {
			return $is_valid;
		}

		$site_key   = self::get_key();
		$secret_key = self::get_key( 'secret' );

		$is_valid = ( $site_key && $secret_key );

		return $is_valid;
	}

	public static function render_wp_captcha( $args = array() ) {
		$t_args = wp_parse_args(
			$args,
			array(
				'mode'    => 'login',
				'sitekey' => self::get_key(),
				'size'    => '',
				'theme'   => '',
			)
		);

		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( 'v3' !== $version ) {
			$t_args['size'] = UM()->options()->get( 'g_recaptcha_size' );
			if ( 'invisible' !== $t_args['size'] ) {
				$t_args['theme'] = UM()->options()->get( 'g_recaptcha_theme' );
			}
		}

		return UM()->get_template( 'wp-captcha.php', UM_RECAPTCHA_PLUGIN, $t_args );
	}

	public static function render_um_captcha( $args = array() ) {
		$t_args = wp_parse_args(
			$args,
			array(
				'form_id' => '',
				'mode'    => 'login',
				'sitekey' => self::get_key(),
				'size'    => '',
				'theme'   => '',
			)
		);

		$version = UM()->options()->get( 'g_recaptcha_version' );

		$t_args['version'] = $version;
		if ( 'v3' !== $version ) {
			$t_args['size'] = UM()->options()->get( 'g_recaptcha_size' );
			if ( 'invisible' !== $t_args['size'] ) {
				$t_args['theme'] = UM()->options()->get( 'g_recaptcha_theme' );
			}
		}

		$file = 'captcha.php';
		if ( UM()->is_new_ui() ) {
			$file = 'v3/' . $file;
		}
		return UM()->get_template( $file, UM_RECAPTCHA_PLUGIN, $t_args );
	}

	/**
	 * Captcha allowed
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public static function is_allowed( $args = null ) {
		return false;
	}

	/**
	 * @return array
	 */
	public function error_codes_list() {
		$error_codes = array(
			'missing-input-secret'   => __( '<strong>Error</strong>: The secret parameter is missing.', 'um-recaptcha' ),
			'invalid-input-secret'   => __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'um-recaptcha' ),
			'missing-input-response' => __( '<strong>Error</strong>: The response parameter is missing.', 'um-recaptcha' ),
			'invalid-input-response' => __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'um-recaptcha' ),
			'bad-request'            => __( '<strong>Error</strong>: The request is invalid or malformed.', 'um-recaptcha' ),
			'timeout-or-duplicate'   => __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'um-recaptcha' ),
			'undefined'              => __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'um-recaptcha' ),
		);

		return $error_codes;
	}

	public function get_v3_score( $args = array(), $form_data = array() ) {
		$score = UM()->options()->get( 'g_reCAPTCHA_score' );

		if ( ! empty( $form_data['g_recaptcha_score'] ) ) {
			// use form setting for score
			$score = $form_data['g_recaptcha_score'];
		}

		if ( empty( $score ) ) {
			// It's a fallback. Set default 0.6 because Google recommend by default set 0.5 score
			// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
			$score = 0.6;
		}

		/**
		 * Filters the Google reCAPTCHA score.
		 *
		 * @param {float} $score     Google reCAPTCHA score. Number from 0 to 1.
		 * @param {array} $args      Submission arguments.
		 * @param {array} $form_data UM Form data.
		 *
		 * @return {float} Google reCAPTCHA score.
		 *
		 * @since 2.3.8
		 * @hook um_recaptcha_score_validation
		 *
		 * @example <caption>Change the Google reCAPTCHA score.</caption>
		 * function my_um_recaptcha_score_validation( $score, $args, $form_data ) {
		 *     $score = 0.8;
		 *     return $score;
		 * }
		 * add_filter( 'um_recaptcha_score_validation', 'my_um_recaptcha_score_validation', 10, 3 );
		 */
		return apply_filters( 'um_recaptcha_score_validation', $score, $args, $form_data );
	}

	/**
	 * @param string $secret
	 * @param string $client_response
	 * @param string $context
	 * @param array  $args
	 *
	 * @return mixed|string
	 */
	public function remote_request( $secret, $client_response, $context = '', $args = array() ) {
		$user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$client_response&remoteip=$user_ip" );

		$hook_args = compact( 'response', 'context', 'args', 'client_response' );
		/**
		 * Fires just after remote request to Google reCAPTCHA API.
		 *
		 * @param {array|WP_Error} $response        The response or WP_Error on failure.
		 *                                          See WP_Http::request() for information on return value.
		 * @param {string}         $context         Request context. Can be equals `wp_register_form`, `wp_lostpassword_form`, `wp_login_form`, `um_form_shortcode`, `um_reset_password_shortcode`.
		 * @param {array}          $args            Additional arguments passed to `remote_request()` function depends on `$context`.
		 * @param {string}         $client_response reCAPTCHA client side response.
		 *
		 * @since 2.3.8
		 * @hook um_recaptcha_api_response
		 *
		 * @example <caption>Make something custom after API response.</caption>
		 * function my_custom_recaptcha_api_response( $response, $context, $args, $client_response ) {
		 *     // do something custom
		 * }
		 * add_action( 'um_recaptcha_api_response', 'my_custom_recaptcha_api_response', 10, 2 );
		 */
		do_action_ref_array( 'um_recaptcha_api_response', $hook_args );

		$result = wp_remote_retrieve_body( $response );
		if ( empty( $result ) ) {
			return '';
		}

		return json_decode( $result );
	}

	protected function init_authenticate_validation() {
		static $inited = false;

		if ( false === $inited ) {
			add_action( 'wp_authenticate', array( $this, 'wp_authenticate_recaptcha_validation' ), 2, 2 );
			add_action( 'um_before_signon_after_account_changes', array( $this, 'remove_wp_authenticate_recaptcha_validation' ) );
			add_filter( 'wp_login_errors', array( $this, 'um_authenticate_recaptcha_errors' ) );
		}

		$inited = true;
	}

	/**
	 * Run before the authenticate process of the user via wp_login_form() function login widget
	 *
	 * @param $username
	 * @param $password
	 */
	public function wp_authenticate_recaptcha_validation( $username, $password ) {
		if ( empty( $username ) || empty( $password ) ) {
			return;
		}

		if ( self::um_is_api_request() ) {
			return;
		}

		$is_allowed = apply_filters( 'um_recaptcha_wp_authenticate_is_allowed', false );
		if ( ! $is_allowed ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
		$redirect     = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$force_reauth = isset( $_GET['reauth'] ) ? (bool) $_GET['reauth'] : false;

		// for the wp_login_form() function login widget
		// $redirect URL in this case will use the widget current URL from where was request to wp-login.php
		if ( ! empty( $_REQUEST['um_login_form'] ) && ! empty( $redirect ) ) {
			$query = wp_parse_url( $redirect, PHP_URL_QUERY );
			parse_str( $query, $query_args );

			if ( array_key_exists( 'redirect_to', $query_args ) ) {
				$redirect = $query_args['redirect_to'];
			}

			if ( array_key_exists( 'reauth', $query_args ) ) {
				$force_reauth = $query_args['reauth'];
			}
		}

		$version     = UM()->options()->get( 'g_recaptcha_version' );
		$your_secret = self::get_key( 'secret' );

		switch ( $version ) {
			case 'v3':
				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'empty' ), wp_login_url( $redirect, $force_reauth ) ) );
					exit;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_login_form' );

				if ( ! empty( $result ) ) {
					$validate_score = $this->get_v3_score();

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'score' ), wp_login_url( $redirect, $force_reauth ) ) );
						exit;
					}

					if ( isset( $result->action ) && 'login' !== $result->action ) {
						wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'action' ), wp_login_url( $redirect, $force_reauth ) ) );
						exit;
					}

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = $this->error_codes_list();

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

							wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => $code ), wp_login_url( $redirect, $force_reauth ) ) );
							exit;
						}
					}
				}
				break;

			case 'v2':
			default:
				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'empty' ), wp_login_url( $redirect, $force_reauth ) ) );
					exit;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_login_form' );
				if ( ! empty( $result ) && isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = $this->error_codes_list();

					foreach ( $result->{'error-codes'} as $key => $error_code ) {
						$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

						wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => $code ), wp_login_url( $redirect, $force_reauth ) ) );
						exit;
					}
				}
				break;

		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
	}

	public function remove_wp_authenticate_recaptcha_validation() {
		remove_action( 'wp_authenticate', array( &$this, 'wp_authenticate_recaptcha_validation' ), 2 );
	}

	/**
	 * Add wp-login.php form errors from $_GET attribute.
	 *
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function um_authenticate_recaptcha_errors( $errors ) {
		if ( ! ( UM()->options()->get( 'g_recaptcha_wp_login_form' ) || UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ) ) {
			return $errors;
		}

		if ( self::um_is_api_request() ) {
			return $errors;
		}

		// phpcs:disable WordPress.Security.NonceVerification -- getting value from GET line
		if ( isset( $_GET['um-recaptcha-error'] ) ) {
			$code = ! empty( $_GET['um-recaptcha-error'] ) ? sanitize_key( $_GET['um-recaptcha-error'] ) : 'undefined';

			switch ( $code ) {
				case 'empty':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );
					break;
				case 'score':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: It is very likely a bot.', 'um-recaptcha' ) );
					break;
				case 'action':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: Invalid response the `action` parameter.', 'um-recaptcha' ) );
					break;
				case 'missing-input-secret':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The secret parameter is missing.', 'um-recaptcha' ) );
					break;
				case 'invalid-input-secret':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'um-recaptcha' ) );
					break;
				case 'missing-input-response':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response parameter is missing.', 'um-recaptcha' ) );
					break;
				case 'invalid-input-response':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'um-recaptcha' ) );
					break;
				case 'bad-request':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The request is invalid or malformed.', 'um-recaptcha' ) );
					break;
				case 'timeout-or-duplicate':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'um-recaptcha' ) );
					break;
				case 'undefined':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'um-recaptcha' ) );
					break;
				default:
					// translators: %s: Google reCAPTCHA error code
					$errors->add( 'recaptcha_' . $code, sprintf( __( '<strong>Error</strong>: reCAPTCHA Code: %s', 'um-recaptcha' ), $code ) );
					break;
			}
		}
		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- getting value from GET line
	}

	/**
	 * Enqueue assets on wp-login.php page
	 */
	public function enqueue_wp_recaptcha_scripts() {
		$suffix = UM()->frontend()->enqueue()::get_suffix();

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$site_key = self::get_key();

				wp_register_script(
					'google-recaptcha-api-v3',
					"https://www.google.com/recaptcha/api.js?render=$site_key",
					array(),
					'3.0',
					array(
						'in_footer' => true,
						'strategy'  => 'async',
					)
				);
				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/recaptcha.v3' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v3', 'wp-hooks' ), UM_RECAPTCHA_VERSION, true );
				wp_enqueue_script( 'um-recaptcha' );
				break;

			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script(
					'google-recaptcha-api-v2',
					"https://www.google.com/recaptcha/api.js?onload=UMreCAPTCHAonLoad&render=explicit&hl=$language_code",
					array(),
					'2.0',
					array(
						'in_footer' => true,
						'strategy'  => 'async',
					)
				);

				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/recaptcha.v2' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v2', 'wp-hooks' ), UM_RECAPTCHA_VERSION, true );
				wp_enqueue_script( 'um-recaptcha' );

				wp_register_style( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/css/wp-recaptcha' . $suffix . '.css', array(), UM_RECAPTCHA_VERSION );
				wp_enqueue_style( 'um-recaptcha' );
				break;

		}
	}

	public function enqueue_um_recaptcha_scripts() {
		$suffix = UM()->frontend()->enqueue()::get_suffix();

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$site_key = self::get_key();

				wp_register_script(
					'google-recaptcha-api-v3',
					"https://www.google.com/recaptcha/api.js?render=$site_key",
					array(),
					'3.0',
					array(
						'in_footer' => true,
						'strategy'  => 'async',
					)
				);
				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/recaptcha.v3' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v3', 'wp-hooks' ), UM_RECAPTCHA_VERSION, true );
				wp_enqueue_script( 'um-recaptcha' );
				break;

			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script(
					'google-recaptcha-api-v2',
					"https://www.google.com/recaptcha/api.js?onload=UMreCAPTCHAonLoad&render=explicit&hl=$language_code",
					array(),
					'2.0',
					array(
						'in_footer' => true,
						'strategy'  => 'async',
					)
				);

				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/recaptcha.v2' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v2', 'wp-hooks' ), UM_RECAPTCHA_VERSION, true );
				wp_enqueue_script( 'um-recaptcha' );

				wp_register_style( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/css/um-recaptcha' . $suffix . '.css', array(), UM_RECAPTCHA_VERSION );
				wp_enqueue_style( 'um-recaptcha' );
				break;

		}
	}
}
