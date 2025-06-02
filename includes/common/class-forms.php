<?php namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Forms
 *
 * @package um_ext\um_recaptcha\common
 */
class Forms {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		// login forms
		add_filter( 'login_body_class', array( $this, 'um_add_recaptcha_login_form_classes' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'um_login_form_scripts' ) );
		add_action( 'login_form', array( $this, 'um_add_recaptcha_wp_login_form' ) );
		add_filter( 'wp_login_errors', array( $this, 'um_authenticate_recaptcha_errors' ) );
		add_filter( 'login_form_middle', array( $this, 'um_add_recaptcha_login_form' ), 10, 2 );
		add_filter( 'login_errors', array( $this, 'um_recaptcha_hide_errors' ), 10, 2 );

		// register forms
		add_action( 'register_form', array( $this, 'um_add_recaptcha_wp_register_form' ) );
		add_filter( 'registration_errors', array( $this, 'um_recaptcha_validate_register_form' ) );

		// lost password forms
		add_action( 'lostpassword_form', array( $this, 'um_add_recaptcha_wp_lostpassword_form' ) );
		add_filter( 'lostpassword_errors', array( $this, 'um_recaptcha_validate_lostpassword_form' ) );

		// reset password forms
		add_action( 'um_reset_password_errors_hook', array( $this, 'um_recaptcha_validate_rp' ), 20 );

		/**
		 * fields
		 * @todo deprecate since old UI is deprecated
		**/
		add_action( 'um_after_register_fields', array( $this, 'um_recaptcha_add_captcha' ), 500 );
		add_action( 'um_after_login_fields', array( $this, 'um_recaptcha_add_captcha' ), 500 );
		add_action( 'um_after_password_reset_fields', array( $this, 'um_recaptcha_add_captcha' ), 500 );

		// enqueue script
		add_action( 'um_pre_register_shortcode', array( $this, 'um_recaptcha_enqueue_scripts' ) );
		add_action( 'um_pre_login_shortcode', array( $this, 'um_recaptcha_enqueue_scripts' ) );
		add_action( 'um_pre_password_shortcode', array( $this, 'um_recaptcha_enqueue_scripts' ) );

		add_action( 'wp_authenticate', array( $this, 'um_authenticate_recaptcha_action' ), 2, 2 );
		add_action( 'um_before_signon_after_account_changes', array( $this, 'um_remove_authenticate_recaptcha_action' ) );
		add_action( 'um_submit_form_errors_hook', array( $this, 'um_recaptcha_validate' ), 20, 2 );

		add_filter( 'um_predefined_fields_hook', array( $this, 'add_field' ) );
		add_filter( 'um_get_form_fields', array( &$this, 'extends_fields' ), 100, 2 );
		add_action( 'um_after_password_reset_fields', array( $this, 'password_reset_add_captcha' ), 500 );
	}

	/**
	 * @return bool
	 */
	public function um_is_api_request() {
		$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
		$is_api_request = apply_filters( 'um_is_api_request', $is_api_request );

		return $is_api_request;
	}

	/**
	 * Add classes on wp-login.php page
	 *
	 * @param $classes
	 * @param $action
	 *
	 * @return array
	 */
	public function um_add_recaptcha_login_form_classes( $classes, $action ) {
		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $classes;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $classes;
		}

		if ( ( 'login' === $action && UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) || ( ( 'lostpassword' === $action || 'retrievepassword' === $action ) && UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) || ( 'register' === $action && UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) ) {
			$version = UM()->options()->get( 'g_recaptcha_version' );
			if ( 'v3' === $version ) {
				return $classes;
			}

			$type = UM()->options()->get( 'g_recaptcha_size' );
			if ( 'invisible' === $type ) {
				return $classes;
			}

			$classes[] = ( 'normal' === $type ) ? 'has-normal-um-recaptcha' : 'has-compact-um-recaptcha';
		}

		return $classes;
	}

	/**
	 * Enqueue assets on wp-login.php page
	 */
	public function um_login_form_scripts() {
		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		if ( ! ( UM()->options()->get( 'g_recaptcha_wp_login_form' ) || UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) || UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) ) {
			return;
		}

		$suffix = UM()->frontend()->enqueue()::get_suffix();

		wp_register_style( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/css/wp-recaptcha' . $suffix . '.css', array(), UM_RECAPTCHA_VERSION );
		wp_enqueue_style( 'um-recaptcha' );

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

				wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key", array(), '3.0', false );
				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/wp-recaptcha' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v3' ), UM_RECAPTCHA_VERSION, true );

				wp_localize_script(
					'um-recaptcha',
					'umRecaptchaData',
					array(
						'site_key' => $site_key,
					)
				);

				wp_enqueue_script( 'um-recaptcha' );
				break;
			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code", array( 'jquery' ), '2.0', false );
				wp_enqueue_script( 'google-recaptcha-api-v2' );
				break;
		}
	}

	/* Handle reCAPTCHA via `wp_login_form()` */
	/**
	 * Add reCAPTCHA block to the wp-login.php page
	 */
	public function um_add_recaptcha_wp_login_form() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				UM()->get_template( 'wp-captcha-v3.php', UM_RECAPTCHA_PLUGIN, array(), true );
				break;
			case 'v2':
			default:
				UM()->get_template(
					'wp-captcha.php',
					UM_RECAPTCHA_PLUGIN,
					array(
						'mode'    => 'login',
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					true
				);
				break;
		}
	}

	/**
	 * @param $content
	 * @param $args
	 *
	 * @return string
	 */
	public function um_add_recaptcha_login_form( $content, $args ) {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ) {
			return $content;
		}

		if ( ! ( array_key_exists( 'um_login_form', $args ) && true === $args['um_login_form'] ) ) {
			return $content;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $content;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $content;
		}

		$suffix = UM()->frontend()->enqueue()::get_suffix();

		wp_register_style( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/css/wp-recaptcha' . $suffix . '.css', array(), UM_RECAPTCHA_VERSION );
		wp_enqueue_style( 'um-recaptcha' );

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

				wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key", array(), '3.0', false );
				wp_register_script( 'um-recaptcha', UM_RECAPTCHA_URL . 'assets/js/wp-recaptcha' . $suffix . '.js', array( 'jquery', 'google-recaptcha-api-v3' ), UM_RECAPTCHA_VERSION, true );

				wp_localize_script(
					'um-recaptcha',
					'umRecaptchaData',
					array(
						'site_key' => $site_key,
					)
				);

				wp_enqueue_script( 'um-recaptcha' );

				$content .= UM()->get_template(
					'wp-captcha-v3.php',
					UM_RECAPTCHA_PLUGIN,
					array(),
					false
				);
				break;
			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code", array( 'jquery' ), '2.0', false );
				wp_enqueue_script( 'google-recaptcha-api-v2' );

				$content .= UM()->get_template(
					'wp-captcha.php',
					UM_RECAPTCHA_PLUGIN,
					array(
						'mode'    => 'login',
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					false
				);
				break;
		}

		return $content;
	}

	/**
	 * @param WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function um_authenticate_recaptcha_errors( $errors ) {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return $errors;
		}

		if ( $this->um_is_api_request() ) {
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
	 * Don't display reCAPTCHA error message twice on login
	 *
	 * @since 2.2.1
	 *
	 * @param string $error_message  Error message
	 * @param string $error_key      A key of the error
	 *
	 * @return string Filtered error message
	 */
	public function um_recaptcha_hide_errors( $error_message, $error_key = null ) {
		if ( 'recaptcha' === $error_key ) {
			$error_message = '';
		}
		return $error_message;
	}

	/**
	 * Add reCAPTCHA block to the wp-login.php page Register mode
	 */
	public function um_add_recaptcha_wp_register_form() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				UM()->get_template( 'wp-captcha-v3.php', UM_RECAPTCHA_PLUGIN, array(), true );
				break;
			case 'v2':
			default:
				UM()->get_template(
					'wp-captcha.php',
					UM_RECAPTCHA_PLUGIN,
					array(
						'mode'    => 'register',
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					true
				);
				break;
		}
	}

	/**
	 * @param WP_Error $errors
	 *
	 * @return mixed
	 */
	public function um_recaptcha_validate_register_form( $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php
		if ( $this->um_is_api_request() ) {
			return $errors;
		}

		if ( ! UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) {
			return $errors;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $errors;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $errors;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_register_form' );
				if ( ! empty( $result ) ) {
					$validate_score = $this->get_v3_score();

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						$errors->add( 'um-recaptcha-score', __( '<strong>Error</strong>: It is very likely a bot.', 'um-recaptcha' ) );

						return $errors;
					}

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = $this->error_codes_list();

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

							return $errors;
						}
					}
				}
				break;

			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_register_form' );
				if ( ! empty( $result ) && isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = $this->error_codes_list();

					foreach ( $result->{'error-codes'} as $key => $error_code ) {
						$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
						$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

						return $errors;
					}
				}
				break;

		}

		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php
	}

	/**
	 * Add reCAPTCHA block to the wp-login.php page Lost Password mode
	 */
	public function um_add_recaptcha_wp_lostpassword_form() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				UM()->get_template( 'wp-captcha-v3.php', UM_RECAPTCHA_PLUGIN, array(), true );
				break;
			case 'v2':
			default:
				UM()->get_template(
					'wp-captcha.php',
					UM_RECAPTCHA_PLUGIN,
					array(
						'mode'    => 'login',
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					true
				);
				break;
		}
	}

	/**
	 * @param WP_Error $errors
	 *
	 * @return mixed
	 */
	public function um_recaptcha_validate_lostpassword_form( $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php
		if ( $this->um_is_api_request()() ) {
			return $errors;
		}
		if ( is_admin() ) {
			return $errors;
		}
		if ( ! UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) {
			return $errors;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $errors;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $errors;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}
				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_lostpassword_form' );
				if ( ! empty( $result ) ) {
					$validate_score = $this->get_v3_score();

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						$errors->add( 'um-recaptcha-score', __( '<strong>Error</strong>: It is very likely a bot.', 'um-recaptcha' ) );

						return $errors;
					}

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = $this->error_codes_list();

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

							return $errors;
						}
					}
				}
				break;

			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'um-recaptcha' ) );

					return $errors;
				}

				$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

				$result = $this->remote_request( $your_secret, $client_captcha_response, 'wp_lostpassword_form' );
				if ( ! empty( $result ) && isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = $this->error_codes_list();

					foreach ( $result->{'error-codes'} as $key => $error_code ) {
						$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
						$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );

						return $errors;
					}
				}
				break;

		}

		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php
	}

	/**
	 * Reset Password form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $args
	 */
	public function um_recaptcha_validate_rp( $args ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
		if ( ! isset( $args['mode'] ) || 'password' !== $args['mode'] ) {
			return;
		}

		$allowed_args = array(
			'mode' => $args['mode'],
		);
		if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );
				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
				break;
		}

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			UM()->form()->add_error( 'recaptcha', __( 'Please confirm you are not a robot', 'um-recaptcha' ) );
			return;
		}

		$client_captcha_response = sanitize_textarea_field( wp_unslash( $_POST['g-recaptcha-response'] ) );

		$result = $this->remote_request( $your_secret, $client_captcha_response, 'um_reset_password_shortcode', compact( 'args' ) );
		if ( ! empty( $result ) ) {
			$validate_score = $this->get_v3_score( $args );

			if ( isset( $result->score ) && $result->score < $validate_score ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: it is very likely a bot.', 'um-recaptcha' ) );
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
	 * add recaptcha
	 * @param $args
	 * @todo deprecate since old UI is deprecated
	 */
	public function um_recaptcha_add_captcha( $args ) {
		if ( ! UM()->is_new_ui() ) {
			$allowed_args = array(
				'mode' => $args['mode'],
			);
			if ( isset( $args['g_recaptcha_status'] ) ) {
				$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
			}
			if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
				return;
			}

			$version = UM()->options()->get( 'g_recaptcha_version' );
			switch ( $version ) {
				case 'v3':
					UM()->get_template( 'captcha-v3.php', UM_RECAPTCHA_PLUGIN, array( 'args' => $args ), true );
					break;
				case 'v2':
				default:
					UM()->get_template(
						'captcha.php',
						UM_RECAPTCHA_PLUGIN,
						array(
							'args'    => $args,
							'type'    => UM()->options()->get( 'g_recaptcha_type' ),
							'size'    => UM()->options()->get( 'g_recaptcha_size' ),
							'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
							'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
						),
						true
					);
					break;
			}
			wp_enqueue_script( 'um-recaptcha' );
		}
	}

	/**
	 * reCAPTCHA scripts/styles enqueue in the page with a form
	 *
	 * @param array $args
	 */
	public function um_recaptcha_enqueue_scripts( $args ) {
		$allowed_args = array(
			'mode' => $args['mode'],
		);
		if ( isset( $args['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
		}
		if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
			return;
		}

		wp_enqueue_style( 'um-recaptcha' );
		wp_enqueue_script( 'um-recaptcha' );
	}

	/**
	 * Run before the authenticate process of the user via wp-login.php form
	 *
	 * @param $username
	 * @param $password
	 */
	public function um_authenticate_recaptcha_action( $username, $password ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return;
		}

		if ( $this->um_is_api_request() ) {
			return;
		}

		if ( empty( $username ) || empty( $password ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

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

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

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
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

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

	public function um_remove_authenticate_recaptcha_action() {
		remove_action( 'wp_authenticate', 'um_authenticate_recaptcha_action', 2 );
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
	public function um_recaptcha_validate( $args, $form_data = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
		if ( ! isset( $args['_social_login_form'] ) && ( ! isset( $form_data['mode'] ) || ! in_array( $form_data['mode'], array( 'login', 'register' ), true ) ) ) {
			return;
		}

		$allowed_args = array(
			'mode' => $form_data['mode'],
		);
		if ( isset( $form_data['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $form_data['g_recaptcha_status'];
		}
		if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );
				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
				break;
		}

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

	public function add_field( $fields ) {
		if ( UM()->is_new_ui() ) {
			$fields['um_recaptcha'] = array(
				'content'     => '',
				'type'        => 'block',
				'private_use' => true,
			);
		}
		return $fields;
	}

	/**
	 * Extends fields on the registration form
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

		$args = UM()->query()->post_data( $form_id );
		if ( ! empty( $args['g_recaptcha_status'] ) ) {
			$recaptcha_row_key = 'um-recaptcha-row';

			$fields[ $recaptcha_row_key ] = array(
				'type'     => 'row',
				'id'       => $recaptcha_row_key,
				'sub_rows' => 1,
				'cols'     => 1,
				'origin'   => $recaptcha_row_key,
			);

			$allowed_args = array(
				'mode' => $args['mode'],
			);
			if ( isset( $args['g_recaptcha_status'] ) ) {
				$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
			}
			if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
				return $fields;
			}

			$recaptcha_field = UM()->builtin()->get_specific_fields( 'um_recaptcha' );
			foreach ( $recaptcha_field as $key => $data ) {
				if ( 'um_recaptcha' === $key ) {
					if ( ! empty( $args['g_recaptcha_status'] ) ) {
						$version = UM()->options()->get( 'g_recaptcha_version' );
						if ( 'v3' === $version ) {
							$t_args = array(
								'form_id' => $form_id,
								'mode'    => $args['mode'],
							);

							$data['content'] = UM()->get_template( 'v3/captcha-v3.php', UM_RECAPTCHA_PLUGIN, $t_args, false );
						} else {
							$t_args = array(
								'form_id' => $form_id,
								'type'    => UM()->options()->get( 'g_recaptcha_type' ),
								'size'    => UM()->options()->get( 'g_recaptcha_size' ),
								'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
								'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
							);

							$data['content'] = UM()->get_template( 'v3/captcha.php', UM_RECAPTCHA_PLUGIN, $t_args, false );
						}
						$data['in_row']     = $recaptcha_row_key;
						$data['in_sub_row'] = '0';
						$data['in_column']  = '1';
						$data['in_group']   = '';
						$data['position']   = 1;

						$fields[ $key ] = $data;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * add password reset form recaptcha
	 * @param $args
	 */
	public function password_reset_add_captcha( $args ) {
		if ( ! UM()->is_new_ui() ) {
			return;
		}
		$allowed_args = array(
			'mode' => $args['mode'],
		);
		if ( isset( $args['g_recaptcha_status'] ) ) {
			$allowed_args['g_recaptcha_status'] = $args['g_recaptcha_status'];
		}
		if ( ! UM()->ReCAPTCHA()->common()->captcha()->captcha_allowed( $allowed_args ) ) {
			return;
		}

		if ( 'password' !== $args['mode'] ) {
			return;
		}

		echo '<div class="um-form-row">';
		echo '<div class="um-form-cols um-form-cols-1">';
		echo '<div class="um-form-col um-form-col-1">';
		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$t_args = array(
					'form_id' => $args['form_id'],
					'mode'    => $args['mode'],
				);
				UM()->get_template( 'v3/captcha-v3.php', UM_RECAPTCHA_PLUGIN, $t_args, true );
				break;
			case 'v2':
			default:
				UM()->get_template(
					'v3/captcha.php',
					UM_RECAPTCHA_PLUGIN,
					array(
						'form_id' => $args['form_id'],
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					true
				);
				break;
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
		wp_enqueue_script( 'um-recaptcha' );
	}
}
