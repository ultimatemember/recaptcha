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
	 * Create classes' instances where __construct isn't empty for hooks init
	 */
	public function includes() {
		$this->settings();
		$this->metabox();
	}

	/**
	 * @return Settings
	 */
	public function settings() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\admin\settings'] ) ) {
			UM()->classes['um_ext\um_recaptcha\admin\settings'] = new Settings();
		}
		return UM()->classes['um_ext\um_recaptcha\admin\settings'];
	}

	/**
	 * @return Metabox
	 */
	public function metabox() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\admin\metabox'] ) ) {
			UM()->classes['um_ext\um_recaptcha\admin\metabox'] = new Metabox();
		}
		return UM()->classes['um_ext\um_recaptcha\admin\metabox'];
	}
}
