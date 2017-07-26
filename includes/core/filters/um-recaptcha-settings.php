<?php

	/***
	***	@extend settings
	***/
add_filter( 'um_settings_structure', 'um_recaptcha_settings', 10, 1 );

function um_recaptcha_settings( $settings ) {
    $key = ! empty( $settings['extensions']['sections'] ) ? 'recaptcha' : '';
    $settings['extensions']['sections'][$key] = array(
        'title'     => __( 'Google reCAPTCHA','um-recaptcha'),
        'fields'    => array(
            array(
                'id'       		=> 'g_recaptcha_status',
                'type'     		=> 'checkbox',
                'label'   		=> __( 'Enable Google reCAPTCHA','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_status' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_status' ),
                'description' 	   		=> __('Turn on or off your Google reCAPTCHA on your site registration and login forms by default.','um-recaptcha'),
            ),

            array(
                'id'       		=> 'g_recaptcha_sitekey',
                'type'     		=> 'text',
                'label'   		=> __( 'Site Key','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_sitekey' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_sitekey' ),
                'description' 	   		=> __('You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>','um-recaptcha'),
            ),

            array(
                'id'       		=> 'g_recaptcha_secretkey',
                'type'     		=> 'text',
                'value' 		=> UM()->um_get_option( 'g_recaptcha_secretkey' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_secretkey' ),
                'label'   		=> __( 'Secret Key','um-recaptcha' ),
                'description' 	   		=> __('Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>','um-recaptcha'),
            ),
            array(
                'id'       		=> 'g_recaptcha_type',
                'type'     		=> 'select',
                'label'   		=> __( 'Type','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_type' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_type' ),
                'description' 	   		=> __('The type of reCAPTCHA to serve.','um-recaptcha'),
                'options' 		=> array(
                    'audio'    		 => 'Audio',
                    'image'			 => 'Image'
                )
            ),
            array(
                'id'       		=> 'g_recaptcha_language_code',
                'type'     		=> 'select',
                'label'   		=> __( 'Language','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_language_code' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_language_code' ),
                'description' 	   		=> __('Select the language to be used in your reCAPTCHA.','um-recaptcha'),
                'options' 		=> array(
                    'ar'     => 'Arabic',
                    'af'     => 'Afrikaans',
                    'am'     => 'Amharic',
                    'hy'     => 'Armenian',
                    'az'     => 'Azerbaijani',
                    'eu'     => 'Basque',
                    'bn'     => 'Bengali',
                    'bg'     => 'Bulgarian',
                    'ca'     => 'Catalan',
                    'zh-HK'  => 'Chinese (Hong Kong)',
                    'zh-CN'  => 'Chinese (Simplified)',
                    'zh-TW'  => 'Chinese (Traditional)',
                    'hr'     => 'Croatian',
                    'cs'     => 'Czech',
                    'da'     => 'Danish',
                    'nl'     => 'Dutch',
                    'en-GB'  => 'English (UK)',
                    'en'     => 'English (US)',
                    'et'     => 'Estonian',
                    'fil'    => 'Filipino',
                    'fi'     => 'Finnish',
                    'fr'     => 'French',
                    'fr-CA'  => 'French (Canadian)',
                    'gl'     => 'Galician',
                    'ka'     => 'Georgian',
                    'de'     => 'German',
                    'de-AT'  => 'German (Austria)',
                    'de-CH'  => 'German (Switzerland)',
                    'el'     => 'Greek',
                    'gu'     => 'Gujarati',
                    'iw'     => 'Hebrew',
                    'hi'     => 'Hindi',
                    'hu'     => 'Hungarain',
                    'is'     => 'Icelandic',
                    'id'     => 'Indonesian',
                    'it'     => 'Italian',
                    'ja'     => 'Japanese',
                    'kn'     => 'Kannada',
                    'ko'     => 'Korean',
                    'lo'     => 'Laothian',
                    'lv'     => 'Latvian',
                    'lt'     => 'Lithuanian',
                    'ms'     => 'Malay',
                    'ml'     => 'Malayalam',
                    'mr'     => 'Marathi',
                    'mn'     => 'Mongolian',
                    'no'     => 'Norwegian',
                    'fa'     => 'Persian',
                    'pl'     => 'Polish',
                    'pt'     => 'Portuguese',
                    'pt-BR'  => 'Portuguese (Brazil)',
                    'pt-PT'  => 'Portuguese (Portugal)',
                    'ro'     => 'Romanian',
                    'ru'     => 'Russian',
                    'sr'     => 'Serbian',
                    'si'     => 'Sinhalese',
                    'sk'     => 'Slovak',
                    'sl'     => 'Slovenian',
                    'es'     => 'Spanish',
                    'es-419' => 'Spanish (Latin America)',
                    'sw'     => 'Swahili',
                    'sv'     => 'Swedish',
                    'ta'     => 'Tamil',
                    'te'     => 'Telugu',
                    'th'     => 'Thai',
                    'tr'     => 'Turkish',
                    'uk'     => 'Ukrainian',
                    'ur'     => 'Urdu',
                    'vi'     => 'Vietnamese',
                    'zu'     => 'Zulu'
                )
            ),
            array(
                'id'       		=> 'g_recaptcha_theme',
                'type'     		=> 'select',
                'label'   		=> __( 'Theme','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_theme' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_theme' ),
                'description' 	   		=> __('Select a color theme of the widget.','um-recaptcha'),
                'options' 		=> array(
                    'dark'     => 'Dark',
                    'light'			 => 'Light'
                )
            ),
            array(
                'id'       		=> 'g_recaptcha_size',
                'type'     		=> 'select',
                'label'   		=> __( 'Size','um-recaptcha' ),
                'value' 		=> UM()->um_get_option( 'g_recaptcha_size' ),
                'default' 		=> UM()->um_get_default( 'g_recaptcha_size' ),
                'description' 	   		=> __('The type of reCAPTCHA to serve.','um-recaptcha'),
                'options' 		=> array(
                    'compact'     		 => 'Compact',
                    'normal'			 => 'Normal',
                    'invisible'		 	=> 'Invisible'
                )
            )
        )
    );

    return $settings;
}
