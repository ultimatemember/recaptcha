<?php

	namespace um_ext\um_recaptcha\core;


	class reCAPTCHA_Enqueue
	{

		function __construct()
		{

			add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), 0 );

		}

		/***
		 ***    @enqueue recaptcha
		 ***/
		function wp_enqueue_scripts()
		{

			wp_register_style( 'um_recaptcha', um_recaptcha_url . 'assets/css/um-recaptcha.css' );
			wp_enqueue_style( 'um_recaptcha' );


			$language_code = um_get_option( 'g_recaptcha_language_code' );
			wp_enqueue_script( 'google-recapthca-api', "//www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code" );
		}

		public function add_defer_js_async( $tag )
		{
			$language_code = um_get_option( 'g_recaptcha_language_code' );
			$defer_js_async = array( "//www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code" );
			foreach ($defer_js_async as $script) {
				if (TRUE == strpos( $tag, $script ))
					return str_replace( ' src', ' async="async" defer="defer" src', $tag );
			}

			return $tag;
		}
	}
