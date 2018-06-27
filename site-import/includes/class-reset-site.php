<?php
/**
 * Author:  Andrei Baicus <andrei@themeisle.com>
 * On:      26/06/2018
 */

namespace ThemeIsle;

/**
 * Class Reset_Site
 *
 * @package ThemeIsle
 */
class Reset_Site {

	/**
	 * Posts Array.
	 *
	 * @var array
	 */
	private $posts = array();

	/**
	 * Pages Array.
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * Reset_Site constructor.
	 */
	public function __construct() {
		$this->set_items( 'posts' );
		$this->set_items( 'pages' );
	}

	/**
	 * Import theme mods.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function reset_site( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$data   = $params['data'];
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed to reset the website.' );
		}
		if ( empty( $this->posts ) && empty( $this->pages ) ) {
			wp_send_json_success( 'Nothing to delete.' );
		}
		$this->delete_items();
		wp_send_json_success( 'Deleted posts and pages.' );
	}

	private function set_items( $type ) {
		$accepted = array( 'posts', 'pages' );
		if ( ! in_array( $type, $accepted ) ) {
			return;
		}
		$items = call_user_func( 'get_' . $type );
		if ( ! is_array( $items ) || empty( $items ) ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( ! isset( $item->ID ) ) {
				continue;
			}
			array_push( $this->$type, $item->ID );
		}
	}

	private function delete_items() {
		$items = array_merge( $this->pages, $this->posts );
		foreach ( $items as $item ) {
			wp_delete_post( $item, true );
		}
	}
}