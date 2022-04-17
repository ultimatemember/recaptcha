<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* wp-login.php */


/**
 * Add classes on wp-login.php page
 *
 * @param $classes
 * @param $action
 *
 * @return array
 */
function um_add_recaptcha_login_form_classes( $classes, $action ) {
	if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
		return $classes;
	}

	if ( 'login' !== $action ) {
		return $classes;
	}

	$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
	if ( ! $recaptcha ) {
		return $classes;
	}

	$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
	$your_secret = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

	if ( ! $your_sitekey || ! $your_secret ) {
		return $classes;
	}

	$version = UM()->options()->get( 'g_recaptcha_version' );
	if ( 'v3' === $version ) {
		return $classes;
	}

	$type = UM()->options()->get( 'g_recaptcha_size' );
	if ( 'invisible' === $type ) {
		return $classes;
	}

	$classes[] = ( 'normal' === $type ) ? 'has-normal-um-recaptcha' : 'has-compact-um-recaptcha';
	return $classes;
}
add_filter( 'login_body_class', 'um_add_recaptcha_login_form_classes', 10, 2 );


/**
 * Enqueue assets on wp-login.php page
 */
function um_login_form_scripts() {
	if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
		return;
	}

	$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
	if ( ! $recaptcha ) {
		return;
	}

	$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
	$your_secret = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

	if ( ! $your_sitekey || ! $your_secret ) {
		return;
	}

	wp_register_style( 'um_recaptcha', um_recaptcha_url . 'assets/css/wp-recaptcha.css' );
	wp_enqueue_style( 'um_recaptcha' );

	$version = UM()->options()->get( 'g_recaptcha_version' );
	switch( $version ) {
		case 'v3':
			$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

			wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key" );
			wp_register_script( 'um-recaptcha', um_recaptcha_url . 'assets/js/wp-recaptcha.js', array( 'jquery', 'google-recaptcha-api-v3' ), um_recaptcha_version, true );

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

			wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code" );
			wp_enqueue_script( 'google-recaptcha-api-v2' );
			break;
	}
}
add_action( 'login_enqueue_scripts', 'um_login_form_scripts' );


/**
 * Add reCAPTCHA block to the wp-login.php page
 */
function um_add_recaptcha_wp_login_form() {
	if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
		return;
	}

	$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
	if ( ! $recaptcha ) {
		return;
	}

	$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
	$your_secret = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

	if ( ! $your_sitekey || ! $your_secret ) {
		return;
	}

	$version = UM()->options()->get( 'g_recaptcha_version' );

	switch( $version ) {
		case 'v3':

			UM()->get_template( 'wp-captcha_v3.php', um_recaptcha_plugin, array(), true );
			break;

		case 'v2':
		default:

			$options = array(
				'data-type'    => UM()->options()->get( 'g_recaptcha_type' ),
				'data-size'    => UM()->options()->get( 'g_recaptcha_size' ),
				'data-theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
				'data-sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
			);

			$attrs = array();
			foreach ( $options as $att => $value ) {
				if ( $value ) {
					$att = esc_html( $att );
					$value = esc_attr( $value );
					$attrs[] = "{$att}=\"{$value}\"";
				}
			}

			if ( ! empty( $attrs ) ) {
				$attrs = implode( ' ', $attrs );
			} else {
				$attrs = '';
			}

			UM()->get_template( 'wp-captcha.php', um_recaptcha_plugin, array( 'attrs' => $attrs, 'options' => $options ), true );
			break;
	}
}
add_action( 'login_form', 'um_add_recaptcha_wp_login_form' );


/**
 * @return bool
 */
function um_is_api_request() {
	$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
	$is_api_request = apply_filters( 'um_is_api_request', $is_api_request );

	return $is_api_request;
}


/**
 * @param \WP_Error $errors
 *
 * @return \WP_Error
 */
function um_authenticate_recaptcha_errors( $errors ) {
	if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
		return $errors;
	}

	if ( um_is_api_request() ) {
		return $errors;
	}

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
				$errors->add( 'recaptcha_' . $code, sprintf( __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'um-recaptcha' ), $code ) );
				break;
			default:
				$errors->add( 'recaptcha_' . $code, sprintf( __( '<strong>Error</strong>: reCAPTCHA Code: %s', 'um-recaptcha' ), $code ) );
				break;
		}
	}
	return $errors;
}
add_filter( 'wp_login_errors', 'um_authenticate_recaptcha_errors', 10, 1 );


/**
 * Run before the authenticate process of the user via wp-login.php form
 *
 * @param $username
 * @param $password
 */
function um_authenticate_recaptcha_action( $username, $password ) {
	if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
		return;
	}

	if ( um_is_api_request() ) {
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
	$your_secret = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

	if ( ! $your_sitekey || ! $your_secret ) {
		return;
	}

	$version = UM()->options()->get( 'g_recaptcha_version' );

	$redirect     = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : '';
	$force_reauth = isset( $_GET['reauth'] ) ? ( bool ) $_GET['reauth'] : false;

	// for the wp_login_form() function login widget
	// $redirect URL in this case will use the widget current URL from where was request to wp-login.php
	if ( ! empty( $_REQUEST['um_login_form'] ) && ! empty( $redirect ) ) {
		$query = parse_url( $redirect, PHP_URL_QUERY );
		parse_str( $query, $query_args );

		if ( array_key_exists( 'redirect_to', $query_args ) ) {
			$redirect = $query_args['redirect_to'];
		}

		if ( array_key_exists( 'reauth', $query_args ) ) {
			$force_reauth = $query_args['reauth'];
		}
	}

	switch( $version ) {
		case 'v3':
			$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

			if ( empty( $_POST['g-recaptcha-response'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'empty' ), wp_login_url( $redirect, $force_reauth ) ) );
				exit;
			} else {
				$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
			}

			$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
			$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

			if ( is_array( $response ) ) {
				$result = json_decode( $response['body'] );

				$score = UM()->options()->get( 'g_reCAPTCHA_score' );
				if ( empty( $score ) ) {
					// set default 0.6 because Google recommend by default set 0.5 score
					// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
					$score = 0.6;
				}
				// available to change score based on form $args
				$validate_score = apply_filters( 'um_recaptcha_score_validation', $score );

				if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
					wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'score' ), wp_login_url( $redirect, $force_reauth ) ) );
					exit;
				} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = array(
						'missing-input-secret'   => __( '<strong>Error</strong>: The secret parameter is missing.', 'um-recaptcha' ),
						'invalid-input-secret'   => __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'um-recaptcha' ),
						'missing-input-response' => __( '<strong>Error</strong>: The response parameter is missing.', 'um-recaptcha' ),
						'invalid-input-response' => __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'um-recaptcha' ),
						'bad-request'            => __( '<strong>Error</strong>: The request is invalid or malformed.', 'um-recaptcha' ),
						'timeout-or-duplicate'   => __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'um-recaptcha' ),
					);

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
			} else {
				$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
			}

			$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
			$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

			if ( is_array( $response ) ) {
				$result = json_decode( $response['body'] );

				if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
					$error_codes = array(
						'missing-input-secret'   => __( '<strong>Error</strong>: The secret parameter is missing.', 'um-recaptcha' ),
						'invalid-input-secret'   => __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'um-recaptcha' ),
						'missing-input-response' => __( '<strong>Error</strong>: The response parameter is missing.', 'um-recaptcha' ),
						'invalid-input-response' => __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'um-recaptcha' ),
						'bad-request'            => __( '<strong>Error</strong>: The request is invalid or malformed.', 'um-recaptcha' ),
						'timeout-or-duplicate'   => __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'um-recaptcha' ),
					);

					foreach ( $result->{'error-codes'} as $key => $error_code ) {
						$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

						wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => $code ), wp_login_url( $redirect, $force_reauth ) ) );
						exit;
					}
				}
			}
			break;
	}
}
add_action( 'wp_authenticate', 'um_authenticate_recaptcha_action', 2, 2 );


function um_add_recaptcha_login_form( $content, $args ) {
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
	$your_secret = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

	if ( ! $your_sitekey || ! $your_secret ) {
		return $content;
	}

	wp_register_style( 'um_recaptcha', um_recaptcha_url . 'assets/css/wp-recaptcha.css' );
	wp_enqueue_style( 'um_recaptcha' );

	$version = UM()->options()->get( 'g_recaptcha_version' );

	switch( $version ) {
		case 'v3':

			$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

			wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key" );
			wp_register_script( 'um-recaptcha', um_recaptcha_url . 'assets/js/wp-recaptcha.js', array( 'jquery', 'google-recaptcha-api-v3' ), um_recaptcha_version, true );

			wp_localize_script(
				'um-recaptcha',
				'umRecaptchaData',
				array(
					'site_key' => $site_key,
				)
			);

			wp_enqueue_script( 'um-recaptcha' );

			$content .= UM()->get_template( 'wp-captcha_v3.php', um_recaptcha_plugin, array(), false );
			break;
		case 'v2':
		default:

			$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
			$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

			wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code" );
			wp_enqueue_script( 'google-recaptcha-api-v2' );

			$options = array(
				'data-type'    => UM()->options()->get( 'g_recaptcha_type' ),
				'data-size'    => UM()->options()->get( 'g_recaptcha_size' ),
				'data-theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
				'data-sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
			);

			$attrs = array();
			foreach ( $options as $att => $value ) {
				if ( $value ) {
					$att = esc_html( $att );
					$value = esc_attr( $value );
					$attrs[] = "{$att}=\"{$value}\"";
				}
			}

			if ( ! empty( $attrs ) ) {
				$attrs = implode( ' ', $attrs );
			} else {
				$attrs = '';
			}

			$content .= UM()->get_template( 'wp-captcha.php', um_recaptcha_plugin, array( 'attrs' => $attrs, 'options' => $options ), false );
			break;
	}

	return $content;
}
add_filter( 'login_form_middle', 'um_add_recaptcha_login_form', 10, 2 );




/**
 * add recaptcha
 *
 * @param $args
 */
function um_recaptcha_add_captcha( $args ) {
	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	$version = UM()->options()->get( 'g_recaptcha_version' );
	switch( $version ) {
		case 'v3':

			$t_args = compact( 'args' );
			UM()->get_template( 'captcha_v3.php', um_recaptcha_plugin, $t_args, true );

			break;

		case 'v2':
		default:

			$options = array(
				'data-type'    => UM()->options()->get( 'g_recaptcha_type' ),
				'data-size'    => UM()->options()->get( 'g_recaptcha_size' ),
				'data-theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
				'data-sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
			);

			$attrs = array();
			foreach ( $options as $att => $value ) {
				if ( $value ) {
					$att = esc_html( $att );
					$value = esc_attr( $value );
					$attrs[] = "{$att}=\"{$value}\"";
				}
			}

			if ( ! empty( $attrs ) ) {
				$attrs = implode( ' ', $attrs );
			} else {
				$attrs = '';
			}

			$t_args = compact( 'args', 'attrs', 'options' );
			UM()->get_template( 'captcha.php', um_recaptcha_plugin, $t_args, true );

			break;
	}
	wp_enqueue_script( 'um-recaptcha' );
}
add_action( 'um_after_register_fields', 'um_recaptcha_add_captcha', 500 );
add_action( 'um_after_login_fields', 'um_recaptcha_add_captcha', 500 );
add_action( 'um_after_password_reset_fields', 'um_recaptcha_add_captcha', 500 );


/**
 * form error handling
 *
 * @link https://developers.google.com/recaptcha/docs/verify#api_request
 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
 *
 * @param $args
 */
function um_recaptcha_validate( $args ) {
	if ( isset( $args['mode'] ) && ! in_array( $args['mode'], array( 'login', 'register', 'password' ), true ) && ! isset( $args['_social_login_form'] ) ) {
		return;
	}

	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	$version = UM()->options()->get( 'g_recaptcha_version' );
	switch( $version ) {
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
	} else {
		$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
	}

	$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
	$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

	if ( is_array( $response ) ) {

		$result = json_decode( $response['body'] );

		$score = UM()->options()->get( 'g_reCAPTCHA_score' );
		if ( ! empty( $args['g_recaptcha_score'] ) ) {
			// use form setting for score
			$score = $args['g_recaptcha_score'];
		}

		if ( empty( $score ) ) {
			// set default 0.6 because Google recommend by default set 0.5 score
			// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
			$score = 0.6;
		}
		// available to change score based on form $args
		$validate_score = apply_filters( 'um_recaptcha_score_validation', $score, $args );

		if ( isset( $result->score ) && $result->score < $validate_score ) {
			UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: it is very likely a bot.', 'um-recaptcha' ) );
		} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
			$error_codes = array(
				'missing-input-secret'   => __( 'The secret parameter is missing.', 'um-recaptcha' ),
				'invalid-input-secret'   => __( 'The secret parameter is invalid or malformed.', 'um-recaptcha' ),
				'missing-input-response' => __( 'The response parameter is missing.', 'um-recaptcha' ),
				'invalid-input-response' => __( 'The response parameter is invalid or malformed.', 'um-recaptcha' ),
				'bad-request'            => __( 'The request is invalid or malformed.', 'um-recaptcha' ),
				'timeout-or-duplicate'   => __( 'The response is no longer valid: either is too old or has been used previously.', 'um-recaptcha' ),
			);

			foreach ( $result->{'error-codes'} as $key => $error_code ) {
				$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'um-recaptcha' ), $error_code );
				UM()->form()->add_error( 'recaptcha', $error );
			}
		}

	}
}
add_action( 'um_submit_form_errors_hook', 'um_recaptcha_validate', 20 );
add_action( 'um_reset_password_errors_hook', 'um_recaptcha_validate', 20 );


/**
 * reCAPTCHA scripts/styles enqueue in the page with a form
 */
function um_recaptcha_enqueue_scripts( $args ) {
	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	UM()->reCAPTCHA()->enqueue()->wp_enqueue_scripts();
}
add_action( 'um_pre_register_shortcode', 'um_recaptcha_enqueue_scripts' );
add_action( 'um_pre_login_shortcode', 'um_recaptcha_enqueue_scripts' );
add_action( 'um_pre_password_shortcode', 'um_recaptcha_enqueue_scripts' );


/**
 * reCAPTCHA scripts/styles enqueue in member directory
 *
 * @param array $args
 */
function um_recaptcha_directory_enqueue_scripts( $args ) {
	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	if ( is_user_logged_in() || empty( $args['show_pm_button'] ) ) {
		return;
	}

	UM()->reCAPTCHA()->enqueue()->wp_enqueue_scripts();
}
add_action( 'um_pre_directory_shortcode', 'um_recaptcha_directory_enqueue_scripts', 10, 1 );


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
function um_recaptcha_hide_errors( $error_message, $error_key = null ) {
	if ( 'recaptcha' === $error_key ) {
		$error_message = '';
	}
	return $error_message;
}
add_filter( 'login_errors', 'um_recaptcha_hide_errors', 10, 2 );
