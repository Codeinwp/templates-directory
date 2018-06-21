<?php
/**
 * Author:  Andrei Baicus <andrei@themeisle.com>
 * On:      21/06/2018
 */

namespace ThemeIsle;

/**
 * Class Content_Importer
 *
 * @package ThemeIsle
 */
class Theme_Mods_Importer {
	/**
	 * Source URL.
	 *
	 * @var string
	 */
	private $source_url = '';

	/**
	 * Theme mods array.
	 *
	 * @var array
	 */
	private $theme_mods = array();

	/**
	 * Import theme mods.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function import_theme_mods( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$data   = $params['data'];

		if ( ! isset( $data['source_url'] ) || empty( $data['source_url'] ) ) {
			wp_send_json_error( 'Incomplete import.' );
		}

		if ( ! isset( $data['theme_mods'] ) || empty( $data['theme_mods'] ) ) {
			wp_send_json_error( 'No theme mods to import.' );
		}
		$this->source_url = $data['source_url'];
		$this->theme_mods = $data['theme_mods'];
		array_walk( $this->theme_mods, array( $this, 'change_theme_mods_root_url' ) );

		foreach ( $this->theme_mods as $mod => $value ) {
			set_theme_mod( $mod, $value );
		}

		if ( isset( $data['front_page'] ) && ! empty( $data['front_page'] ) ) {
			$this->setup_front_page( $data['front_page'] );
		}
		wp_send_json_success( 'Theme mods imported.' );
	}

	/**
	 * @param &item.
	 *
	 * @return void
	 */
	private function change_theme_mods_root_url( &$item ) {
		$current_site        = home_url();
		$source_site         = $this->source_url;
		$item                = str_replace( $source_site, $current_site, $item );
		$escaped_source_url  = str_replace( '/', '\/', $source_site );
		$escaped_current_url = str_replace( '/', '\/', $current_site );
		$item                = str_replace( $escaped_source_url, $escaped_current_url, $item );
	}

	/**
	 * Set up front page options.
	 *
	 * @param $args
	 */
	private function setup_front_page( $args ) {
		if ( ! is_array( $args ) ) {
			return;
		}
		update_option( 'show_on_front', 'page' );

		if ( isset( $args['front_page'] ) ) {
			update_option( 'page_on_front', $args['front_page'] );
		}

		if ( isset( $args['blog_page'] ) ) {
			update_option( 'page_for_posts', $args['blog_page'] );
		}

		print_r( 'Front page set up.' );
	}

}