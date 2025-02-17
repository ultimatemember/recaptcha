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
		}
		return self::$instance;
	}

	/**
	 * UM_ReCAPTCHA constructor.
	 */
	public function __construct() {
		// Global for backwards compatibility.
		$GLOBALS['um_recaptcha'] = $this;
		add_filter( 'um_call_object_ReCAPTCHA', array( &$this, 'get_this' ) );
		add_filter( 'um_settings_default_values', array( &$this, 'default_settings' ), 10, 1 );
		$this->includes();
	}

	/**
	 * @return $this
	 */
	public function get_this() {
		return $this;
	}

	/**
	 * @param $defaults
	 *
	 * @return array
	 */
	public function default_settings( $defaults ) {
		$defaults = array_merge( $defaults, $this->setup()->settings_defaults );
		return $defaults;
	}

	public function includes() {
		$this->common()->includes();
		if ( UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->frontend()->includes();
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

	/**
	 * @return um_ext\um_recaptcha\frontend\Init
	 */
	public function frontend() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\frontend\init'] ) ) {
			UM()->classes['um_ext\um_recaptcha\frontend\init'] = new um_ext\um_recaptcha\frontend\Init();
		}
		return UM()->classes['um_ext\um_recaptcha\frontend\init'];
	}
}

//create class var
add_action( 'plugins_loaded', 'um_init_recaptcha', -10 );
function um_init_recaptcha() {
	if ( function_exists( 'UM' ) ) {
		UM()->set_class( 'ReCAPTCHA', true );
	}
}
