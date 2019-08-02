<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * add recaptcha
 *
 * @param $args
 */
function um_recaptcha_add_captcha( $args ) {
	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	$options = array(
		'data-type'     => UM()->options()->get( 'g_recaptcha_type' ),
		'data-size'     => UM()->options()->get( 'g_recaptcha_size' ),
		'data-theme'    => UM()->options()->get( 'g_recaptcha_theme' ),
		'data-sitekey'  => UM()->options()->get( 'g_recaptcha_sitekey' )
	);

	$attrs = '';
	foreach ( $options as $att => $value ) {
		if ( $value ) {
			$attrs .= " {$att}=\"{$value}\" ";
		}
	}

	$t_args = compact( 'args', 'attrs', 'options', 'your_sitekey' );
	UM()->get_template( 'captcha.php', um_recaptcha_plugin, $t_args, true );
}
add_action( 'um_after_register_fields', 'um_recaptcha_add_captcha', 500 );
add_action( 'um_after_login_fields', 'um_recaptcha_add_captcha', 500 );
add_action( 'um_after_password_reset_fields', 'um_recaptcha_add_captcha', 500 );


/**
 * form error handling
 *
 * @param $args
 */
function um_recaptcha_validate( $args ) {
	if ( isset( $args['mode'] ) && ! in_array( $args['mode'], array( 'login', 'register', 'password' ) ) && ! isset( $args['_social_login_form'] ) ) {
		return;
	}

	if ( ! UM()->reCAPTCHA()->captcha_allowed( $args ) ) {
		return;
	}

	$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
	$client_captcha_response = $_POST['g-recaptcha-response'];
	$user_ip = $_SERVER['REMOTE_ADDR'];

	$response = wp_remote_get(
	"https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip"
	);

	$error_codes = array(
		'missing-input-secret'   => __( 'The secret parameter is missing.', 'um-recaptcha' ),
		'invalid-input-secret'   => __( 'The secret parameter is invalid or malformed.', 'um-recaptcha' ),
		'missing-input-response' => __( 'The response parameter is missing.', 'um-recaptcha' ),
		'invalid-input-response' => __( 'The response parameter is invalid or malformed.', 'um-recaptcha' ),
	);


	if ( is_array( $response ) ) {

		$result = json_decode( $response['body'] );

		if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
			foreach ( $result->{'error-codes'} as $key => $error_code ) {
				if ( $error_code == 'missing-input-response' ) {
					UM()->form()->add_error( 'recaptcha', __( 'Please confirm you are not a robot', 'um-recaptcha' ) );
				} else {
					UM()->form()->add_error( 'recaptcha', $error_codes[ $error_code ] );
				}
			}
		}

	}
}
add_action( 'um_submit_form_errors_hook', 'um_recaptcha_validate', 20 );
add_action( 'um_reset_password_errors_hook', 'um_recaptcha_validate', 20 );


/**
 * reCAPTCHA scripts/styles enqueue
 *
 * @uses   hook actions: um_pre_register_shortcode
 *                       um_pre_login_shortcode
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
 * reCAPTCHA scripts/styles enqueue
 *
 * @uses   hook actions: um_pre_register_shortcode
 *                       um_pre_login_shortcode
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