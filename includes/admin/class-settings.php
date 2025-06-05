<?php
namespace um_ext\um_recaptcha\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 * @package um_ext\um_recaptcha\admin
 */
class Settings {

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		add_filter( 'um_settings_structure', array( &$this, 'add_settings' ) );
		add_filter( 'um_settings_map', array( &$this, 'settings_map' ) );
		add_action( 'um_admin_create_notices', array( &$this, 'add_admin_notice' ) );
		add_filter( 'um_override_templates_get_template_path__um-recaptcha', array( &$this, 'um_recaptcha_get_path_template' ), 10, 2 );
		add_filter( 'um_override_templates_scan_files', array( &$this, 'um_recaptcha_extend_scan_files' ) );
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
					'id'          => 'g_recaptcha_version',
					'type'        => 'select',
					'label'       => __( 'reCAPTCHA type', 'um-recaptcha' ),
					'description' => __( 'Choose the type of reCAPTCHA for this site key. A site key only works with a single reCAPTCHA site type. See <a href="https://developers.google.com/recaptcha/docs/versions" target="_blank">Site Types</a> for more details.', 'um-recaptcha' ),
					'options'     => array(
						'v2' => __( 'reCAPTCHA v2', 'um-recaptcha' ),
						'v3' => __( 'reCAPTCHA v3', 'um-recaptcha' ),
					),
					'size'        => 'medium',
				),
				/* reCAPTCHA v3 */
				array(
					'id'          => 'g_reCAPTCHA_site_key',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'um-recaptcha' ),
					'description' => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_secret_key',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'um-recaptcha' ),
					'description' => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_score',
					'type'        => 'text',
					'label'       => __( 'reCAPTCHA Score', 'um-recaptcha' ),
					'description' => __( 'Consider answers with a score >= to the specified as safe. Set the score in the 0 to 1 range. E.g. 0.5', 'um-recaptcha' ),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				/* reCAPTCHA v2 */
				array(
					'id'          => 'g_recaptcha_sitekey',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'um-recaptcha' ),
					'description' => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_secretkey',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'um-recaptcha' ),
					'description' => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'um-recaptcha' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_language_code',
					'type'        => 'select',
					'label'       => __( 'Language', 'um-recaptcha' ),
					'description' => __( 'Select the language to be used in your reCAPTCHA.', 'um-recaptcha' ),
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
					'description' => __( 'The type of reCAPTCHA to serve.', 'um-recaptcha' ),
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
					'description' => __( 'Select a color theme of the widget.', 'um-recaptcha' ),
					'options'     => array(
						'dark'  => __( 'Dark', 'um-recaptcha' ),
						'light' => __( 'Light', 'um-recaptcha' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_size', '!=', 'invisible' ),
				),
				/* Forms */
				array(
					'id'             => 'g_recaptcha_status',
					'type'           => 'checkbox',
					'label'          => __( 'UM Forms Google reCAPTCHA', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on the UM Forms by default', 'um-recaptcha' ),
					'description'    => __( 'Turn on or off your Google reCAPTCHA on your site registration and login forms by default.', 'um-recaptcha' ),
				),
				array(
					'id'             => 'g_recaptcha_password_reset',
					'type'           => 'checkbox',
					'label'          => __( 'UM Password Reset form Google reCAPTCHA', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on the UM password reset form', 'um-recaptcha' ),
					'description'    => __( 'Display the google Google reCAPTCHA on the Ultimate Member password reset form.', 'um-recaptcha' ),
				),
				array(
					'id'             => 'g_recaptcha_wp_lostpasswordform',
					'type'           => 'checkbox',
					'label'          => __( 'wp-login.php lost password form Google reCAPTCHA', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on wp-login.php lost password form', 'um-recaptcha' ),
					'description'    => __( 'Display the google Google reCAPTCHA on wp-login.php lost password form.', 'um-recaptcha' ),
				),
				array(
					'id'             => 'g_recaptcha_wp_login_form',
					'type'           => 'checkbox',
					'label'          => __( 'wp-login.php form Google reCAPTCHA', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on wp-login.php form', 'um-recaptcha' ),
					'description'    => __( 'Display the google Google reCAPTCHA on wp-login.php form.', 'um-recaptcha' ),
				),
				array(
					'id'             => 'g_recaptcha_wp_login_form_widget',
					'type'           => 'checkbox',
					'label'          => __( '`wp_login_form()` widget Google reCAPTCHA ', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on login form through `wp_login_form()`', 'um-recaptcha' ),
					'description'    => __( 'Display the google Google reCAPTCHA on login form through `wp_login_form()`.', 'um-recaptcha' ),
				),
				array(
					'id'             => 'g_recaptcha_wp_register_form',
					'type'           => 'checkbox',
					'label'          => __( 'wp-login.php registration form Google reCAPTCHA', 'um-recaptcha' ),
					'checkbox_label' => __( 'Enable Google reCAPTCHA on wp-login.php registration form', 'um-recaptcha' ),
					'description'    => __( 'Display the google Google reCAPTCHA on wp-login.php registration form.', 'um-recaptcha' ),
				),
			),
		);

		return $settings;
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
				'g_recaptcha_language_code'        => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_size'                 => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_theme'                => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_status'               => array(
					'sanitize' => 'bool',
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
		$version = UM()->options()->get( 'g_recaptcha_version' );
		if ( 'v3' === $version ) {
			$sitekey   = UM()->options()->get( 'g_reCAPTCHA_site_key' );
			$secretkey = UM()->options()->get( 'g_reCAPTCHA_secret_key' );
		} else {
			$sitekey   = UM()->options()->get( 'g_recaptcha_sitekey' );
			$secretkey = UM()->options()->get( 'g_recaptcha_secretkey' );
		}

		if ( $sitekey && $secretkey ) {
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
	 * Scan templates from extension
	 *
	 * @param $scan_files
	 *
	 * @return array
	 */
	public function um_recaptcha_extend_scan_files( $scan_files ) {
		$extension_files['um-recaptcha'] = UM()->admin_settings()->scan_template_files( UM_RECAPTCHA_PATH . '/templates/' );
		return array_merge( $scan_files, $extension_files );
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
}
