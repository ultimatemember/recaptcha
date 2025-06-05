<?php
namespace um_ext\um_recaptcha\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fields
 *
 * @package um_ext\um_recaptcha\common
 */
class Fields {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		add_filter( 'um_predefined_fields_hook', array( $this, 'add_field' ) );
	}

	public function add_field( $fields ) {
		$fields['um_recaptcha'] = array(
			'content'     => '',
			'type'        => 'block',
			'private_use' => true,
		);
		return $fields;
	}
}
