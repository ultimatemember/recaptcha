<?php
namespace um_ext\um_recaptcha\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package um_ext\um_recaptcha\frontend
 */
class Init {

	/**
	 * Create classes' instances where __construct isn't empty for hooks init
	 */
	public function includes() {
		$this->enqueue();
	}

	/**
	 * @return Enqueue
	 */
	public function enqueue() {
		if ( empty( UM()->classes['um_ext\um_recaptcha\common\enqueue'] ) ) {
			UM()->classes['um_ext\um_recaptcha\common\enqueue'] = new Enqueue();
		}
		return UM()->classes['um_ext\um_recaptcha\common\enqueue'];
	}
}
