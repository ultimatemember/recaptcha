<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UM_ReCAPTCHA
 */
class UM_ReCAPTCHA {

	/**
	 * For backward compatibility with 1.3.x and PHP8.2 compatibility.
	 *
	 * @var bool
	 */
	public $plugin_inactive = false;

	/**
	 * @var
	 */
	private static $instance;

	/**
	 * @return UM_ReCAPTCHA
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->um_recaptcha_construct();
		}

		return self::$instance;
	}

	/**
	 * UM_ReCAPTCHA constructor.
	 */
	public function um_recaptcha_construct() {
		// Global for backwards compatibility.
		$GLOBALS['um_recaptcha'] = $this;
		$this->common()->includes();
		if ( UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		}
	}

	/**
	 * @return um_ext\um_recaptcha\common\Setup()
	 */
	public function setup() {
		if ( empty( UM()->classes['um_recaptcha_setup'] ) ) {
			UM()->classes['um_recaptcha_setup'] = new um_ext\um_recaptcha\common\Setup();
		}
		return UM()->classes['um_recaptcha_setup'];
	}

	/**
	 * @return um_ext\um_recaptcha\admin\Init
	 */
	public function admin() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\admin\init'] ) ) {
			UM()->classes['um_ext\um_recaptcha\admin\init'] = new um_ext\um_recaptcha\admin\Init();
		}
		return UM()->classes['um_ext\um_recaptcha\admin\init'];
	}

	/**
	 * @return um_ext\um_recaptcha\common\Init
	 */
	public function common() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\init'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\init'] = new um_ext\um_recaptcha\common\Init();
		}
		return UM()->classes['um_ext\um_recaptcha\common\init'];
	}
}
