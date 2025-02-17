<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package um_ext\um_recaptcha\common
 */
class Init {

	/**
	 * Create classes' instances where __construct isn't empty for hooks init
	 */
	public function includes() {
		$this->forms();
		$this->directory();
		$this->capthca();
	}

	/**
	 * @return Forms
	 */
	public function forms() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\forms'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\forms'] = new Forms();
		}
		return UM()->classes['um_ext\um_recaptcha\common\forms'];
	}

	/**
	 * @return Directory
	 */
	public function directory() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\directory'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\directory'] = new Directory();
		}
		return UM()->classes['um_ext\um_recaptcha\common\directory'];
	}

	/**
	 * @return Capthca
	 */
	public function capthca() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\capthca'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\capthca'] = new Capthca();
		}
		return UM()->classes['um_ext\um_recaptcha\common\capthca'];
	}
}
