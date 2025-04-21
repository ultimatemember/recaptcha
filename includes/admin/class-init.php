<?php
namespace um_ext\um_recaptcha\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package um_ext\um_recaptcha\admin
 */
class Init {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_action( 'um_admin_create_notices', array( &$this, 'add_admin_notice' ) );
		add_action( 'um_admin_custom_register_metaboxes', array( &$this, 'add_metabox_register' ), 10 );
		add_action( 'um_admin_custom_login_metaboxes', array( &$this, 'add_metabox_login' ), 10 );
		add_filter( 'um_settings_structure', array( &$this, 'add_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'settings_map' ), 10, 1 );

		add_filter( 'um_override_templates_get_template_path__um-recaptcha', array( &$this, 'um_recaptcha_get_path_template' ), 10, 2 );
		add_filter( 'um_override_templates_scan_files', array( &$this, 'um_recaptcha_extend_scan_files' ), 10, 1 );

		add_filter( 'debug_information', array( $this, 'debug_information' ), 20 );
		add_filter( 'um_debug_information_register_form', array( $this, 'debug_information_register_form' ), 20, 2 );
		add_filter( 'um_debug_information_login_form', array( $this, 'debug_information_login_form' ), 20, 2 );
	}

	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function settings_map( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'g_recaptcha_status'               => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_version'              => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_site_key'             => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_secret_key'           => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_score'                => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_sitekey'              => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_secretkey'            => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_type'                 => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_language_code'        => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_size'                 => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_theme'                => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_password_reset'       => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_lostpasswordform'  => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_login_form'        => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_login_form_widget' => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_register_form'     => array(
					'sanitize' => 'bool',
				),
			)
		);
		return $settings_map;
	}

	/**
	 * Adding admin notices about inactive reCAPTCHA when keys are empty
	 */
	public function add_admin_notice() {
		$status    = UM()->options()->get( 'g_recaptcha_status' );
		$sitekey   = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$secretkey = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $status || ( $sitekey && $secretkey ) ) {
			return;
		}

		$allowed_html = array(
			'strong' => array(),
		);

		ob_start(); ?>

		<p><?php echo wp_kses( __( 'Google reCAPTCHA is active on your site. However you need to fill in both your <strong>site key and secret key</strong> to start protecting your site against spam.', 'um-recaptcha' ), $allowed_html ); ?></p>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=um_options&tab=extensions&section=recaptcha' ) ); ?>" class="button button-primary"><?php esc_html_e( 'I already have the keys', 'um-recaptcha' ); ?></a>&nbsp;
			<a href="http://google.com/recaptcha" class="button-secondary" target="_blank"><?php esc_html_e( 'Generate your site and secret key', 'um-recaptcha' ); ?></a>
		</p>

		<?php
		$message = ob_get_clean();

		UM()->admin()->notices()->add_notice(
			'um_recaptcha_notice',
			array(
				'class'       => 'updated',
				'message'     => $message,
				'dismissible' => true,
			),
			10
		);
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
	 * Extend settings
	 *
	 * @param $settings
	 * @return mixed
	 */
	public function add_settings( $settings ) {
		$settings['extensions']['sections']['recaptcha'] = array(
			'title'  => __( 'Google reCAPTCHA', 'um-recaptcha' ),
			'fields' => array(
				array(
					'id'      => 'g_recaptcha_status',
					'type'    => 'checkbox',
					'label'   => __( 'Enable Google reCAPTCHA', 'um-recaptcha' ),
					'tooltip' => __( 'Turn on or off your Google reCAPTCHA on your site registration and login forms by default.', 'um-recaptcha' ),
				),
				array(
					'id'          => 'g_recaptcha_version',
					'type'        => 'select',
					'label'       => __( 'reCAPTCHA type', 'um-recaptcha' ),
					'tooltip'     => __( 'Choose the type of reCAPTCHA for this site key. A site key only works with a single reCAPTCHA site type.', 'um-recaptcha' ),
					'options'     => array(
						'v2' => __( 'reCAPTCHA v2', 'um-recaptcha' ),
						'v3' => __( 'reCAPTCHA v3', 'um-recaptcha' ),
					),
					'size'        => 'medium',
					'description' => __( 'See <a href="https://developers.google.com/recaptcha/docs/versions" target="_blank">Site Types</a> for more details.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				/* reCAPTCHA v3 */
				array(
					'id'          => 'g_reCAPTCHA_site_key',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'um-recaptcha' ),
					'tooltip'     => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_secret_key',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'um-recaptcha' ),
					'tooltip'     => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_score',
					'type'        => 'text',
					'label'       => __( 'reCAPTCHA Score', 'um-recaptcha' ),
					'tooltip'     => __( 'Consider answers with a score >= to the specified as safe. Set the score in the 0 to 1 range. E.g. 0.5', 'um-recaptcha' ),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				/* reCAPTCHA v2 */
				array(
					'id'          => 'g_recaptcha_sitekey',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'um-recaptcha' ),
					'tooltip'     => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_secretkey',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'um-recaptcha' ),
					'tooltip'     => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_type',
					'type'        => 'select',
					'label'       => __( 'Type', 'um-recaptcha' ),
					'tooltip'     => __( 'The type of reCAPTCHA to serve.', 'um-recaptcha' ),
					'options'     => array(
						'audio' => __( 'Audio', 'um-recaptcha' ),
						'image' => __( 'Image', 'um-recaptcha' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_language_code',
					'type'        => 'select',
					'label'       => __( 'Language', 'um-recaptcha' ),
					'tooltip'     => __( 'Select the language to be used in your reCAPTCHA.', 'um-recaptcha' ),
					'options'     => array(
						'ar'     => __( 'Arabic', 'um-recaptcha' ),
						'af'     => __( 'Afrikaans', 'um-recaptcha' ),
						'am'     => __( 'Amharic', 'um-recaptcha' ),
						'hy'     => __( 'Armenian', 'um-recaptcha' ),
						'az'     => __( 'Azerbaijani', 'um-recaptcha' ),
						'eu'     => __( 'Basque', 'um-recaptcha' ),
						'bn'     => __( 'Bengali', 'um-recaptcha' ),
						'bg'     => __( 'Bulgarian', 'um-recaptcha' ),
						'ca'     => __( 'Catalan', 'um-recaptcha' ),
						'zh-HK'  => __( 'Chinese (Hong Kong)', 'um-recaptcha' ),
						'zh-CN'  => __( 'Chinese (Simplified)', 'um-recaptcha' ),
						'zh-TW'  => __( 'Chinese (Traditional)', 'um-recaptcha' ),
						'hr'     => __( 'Croatian', 'um-recaptcha' ),
						'cs'     => __( 'Czech', 'um-recaptcha' ),
						'da'     => __( 'Danish', 'um-recaptcha' ),
						'nl'     => __( 'Dutch', 'um-recaptcha' ),
						'en-GB'  => __( 'English (UK)', 'um-recaptcha' ),
						'en'     => __( 'English (US)', 'um-recaptcha' ),
						'et'     => __( 'Estonian', 'um-recaptcha' ),
						'fil'    => __( 'Filipino', 'um-recaptcha' ),
						'fi'     => __( 'Finnish', 'um-recaptcha' ),
						'fr'     => __( 'French', 'um-recaptcha' ),
						'fr-CA'  => __( 'French (Canadian)', 'um-recaptcha' ),
						'gl'     => __( 'Galician', 'um-recaptcha' ),
						'ka'     => __( 'Kartuli', 'um-recaptcha' ),
						'de'     => __( 'German', 'um-recaptcha' ),
						'de-AT'  => __( 'German (Austria)', 'um-recaptcha' ),
						'de-CH'  => __( 'German (Switzerland)', 'um-recaptcha' ),
						'el'     => __( 'Greek', 'um-recaptcha' ),
						'gu'     => __( 'Gujarati', 'um-recaptcha' ),
						'iw'     => __( 'Hebrew', 'um-recaptcha' ),
						'hi'     => __( 'Hindi', 'um-recaptcha' ),
						'hu'     => __( 'Hungarain', 'um-recaptcha' ),
						'is'     => __( 'Icelandic', 'um-recaptcha' ),
						'id'     => __( 'Indonesian', 'um-recaptcha' ),
						'it'     => __( 'Italian', 'um-recaptcha' ),
						'ja'     => __( 'Japanese', 'um-recaptcha' ),
						'kn'     => __( 'Kannada', 'um-recaptcha' ),
						'ko'     => __( 'Korean', 'um-recaptcha' ),
						'lo'     => __( 'Laothian', 'um-recaptcha' ),
						'lv'     => __( 'Latvian', 'um-recaptcha' ),
						'lt'     => __( 'Lithuanian', 'um-recaptcha' ),
						'ms'     => __( 'Malay', 'um-recaptcha' ),
						'ml'     => __( 'Malayalam', 'um-recaptcha' ),
						'mr'     => __( 'Marathi', 'um-recaptcha' ),
						'mn'     => __( 'Mongolian', 'um-recaptcha' ),
						'no'     => __( 'Norwegian', 'um-recaptcha' ),
						'fa'     => __( 'Persian', 'um-recaptcha' ),
						'pl'     => __( 'Polish', 'um-recaptcha' ),
						'pt'     => __( 'Portuguese', 'um-recaptcha' ),
						'pt-BR'  => __( 'Portuguese (Brazil)', 'um-recaptcha' ),
						'pt-PT'  => __( 'Portuguese (Portugal)', 'um-recaptcha' ),
						'ro'     => __( 'Romanian', 'um-recaptcha' ),
						'ru'     => __( 'Russian', 'um-recaptcha' ),
						'sr'     => __( 'Serbian', 'um-recaptcha' ),
						'si'     => __( 'Sinhalese', 'um-recaptcha' ),
						'sk'     => __( 'Slovak', 'um-recaptcha' ),
						'sl'     => __( 'Slovenian', 'um-recaptcha' ),
						'es'     => __( 'Spanish', 'um-recaptcha' ),
						'es-419' => __( 'Spanish (Latin America)', 'um-recaptcha' ),
						'sw'     => __( 'Swahili', 'um-recaptcha' ),
						'sv'     => __( 'Swedish', 'um-recaptcha' ),
						'ta'     => __( 'Tamil', 'um-recaptcha' ),
						'te'     => __( 'Telugu', 'um-recaptcha' ),
						'th'     => __( 'Thai', 'um-recaptcha' ),
						'tr'     => __( 'Turkish', 'um-recaptcha' ),
						'uk'     => __( 'Ukrainian', 'um-recaptcha' ),
						'ur'     => __( 'Urdu', 'um-recaptcha' ),
						'vi'     => __( 'Vietnamese', 'um-recaptcha' ),
						'zu'     => __( 'Zulu', 'um-recaptcha' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_size',
					'type'        => 'select',
					'label'       => __( 'Size', 'um-recaptcha' ),
					'tooltip'     => __( 'The type of reCAPTCHA to serve.', 'um-recaptcha' ),
					'options'     => array(
						'compact'   => __( 'Compact', 'um-recaptcha' ),
						'normal'    => __( 'Normal', 'um-recaptcha' ),
						'invisible' => __( 'Invisible', 'um-recaptcha' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_theme',
					'type'        => 'select',
					'label'       => __( 'Theme', 'um-recaptcha' ),
					'tooltip'     => __( 'Select a color theme of the widget.', 'um-recaptcha' ),
					'options'     => array(
						'dark'  => __( 'Dark', 'um-recaptcha' ),
						'light' => __( 'Light', 'um-recaptcha' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_size', '!=', 'invisible' ),
				),
				/* Forms */
				array(
					'id'          => 'g_recaptcha_password_reset',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on the UM password reset form', 'um-recaptcha' ),
					'tooltip'     => __( 'Display the google Google reCAPTCHA on the Ultimate Member password reset form.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_lostpasswordform',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php lost password form', 'um-recaptcha' ),
					'tooltip'     => __( 'Display the google Google reCAPTCHA on wp-login.php lost password form.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_login_form',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php form', 'um-recaptcha' ),
					'tooltip'     => __( 'Display the google Google reCAPTCHA on wp-login.php form.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_login_form_widget',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on login form through `wp_login_form()`', 'um-recaptcha' ),
					'tooltip'     => __( 'Display the google Google reCAPTCHA on login form through `wp_login_form()`.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_register_form',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php registration form', 'um-recaptcha' ),
					'tooltip'     => __( 'Display the google Google reCAPTCHA on wp-login.php registration form`.', 'um-recaptcha' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Scan templates from extension
	 *
	 * @param $scan_files
	 *
	 * @return array
	 */
	public function um_recaptcha_extend_scan_files( $scan_files ) {
		$extension_files['um-recaptcha'] = UM()->admin_settings()->scan_template_files( UM_RECAPTCHA_PATH . '/templates/' );
		$scan_files                      = array_merge( $scan_files, $extension_files );

		return $scan_files;
	}

	/**
	 * Get template paths
	 *
	 * @param $located
	 * @param $file
	 *
	 * @return array
	 */
	public function um_recaptcha_get_path_template( $located, $file ) {
		if ( file_exists( get_stylesheet_directory() . '/ultimate-member/um-recaptcha/' . $file ) ) {
			$located = array(
				'theme' => get_stylesheet_directory() . '/ultimate-member/um-recaptcha/' . $file,
				'core'  => UM_RECAPTCHA_PATH . 'templates/' . $file,
			);
		}

		return $located;
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
