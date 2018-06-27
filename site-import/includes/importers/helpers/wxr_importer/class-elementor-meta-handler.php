<?php

namespace ThemeIsle;

/**
 * Class Elementor_Meta_Handler
 *
 * @package ThemeIsle
 */
class Elementor_Meta_Handler {
	private $meta_key = '_elementor_data';

	private $value = null;

	/**
	 * Elementor_Meta_Handler constructor.
	 *
	 * @param $unfiltered_value
	 */
	public function __construct( $unfiltered_value ) {
		$this->value   = $unfiltered_value;
	}

	/**
	 * Filter the meta to allow escaped JSON values.
	 */
	public function filter_meta() {
		add_filter( 'sanitize_post_meta_' . $this->meta_key, array( $this, 'allow_escaped_json_meta' ), 10, 3 );
	}

	/**
	 * Allow JSON escaping.
	 *
	 * @param $val
	 * @param $key
	 * @param $type
	 *
	 * @return array|string
	 */
	public function allow_escaped_json_meta( $val, $key, $type ) {
		if ( empty( $this->value ) ) {
			return $val;
		}

		return $this->value;
	}
}
