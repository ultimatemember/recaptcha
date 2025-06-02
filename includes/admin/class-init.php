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
		$this->metabox();
		$this->settings();
		$this->site_health();
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
	 * @return Site_Health
	 */
	public function site_health() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\admin\site_health'] ) ) {
			UM()->classes['um_ext\um_recaptcha\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['um_ext\um_recaptcha\admin\site_health'];
	}
}
