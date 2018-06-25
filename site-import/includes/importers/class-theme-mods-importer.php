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
			if ( $mod === '__ti_import_menus_location' ) {
				continue;
			}
			set_theme_mod( $mod, $value );
		}

		//Set nav menu locations.
		if ( isset( $this->theme_mods['__ti_import_menus_location'] ) ) {
			$menus = $this->theme_mods['__ti_import_menus_location'];
			$this->setup_nav_menus( $menus );
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
	 * Set up the `nav_menu_locations` theme mod.
	 *
	 * @param array $menus represents the menu data as as [location => slug] retrieved from the API.
	 */
	private function setup_nav_menus( $menus ) {
		if ( empty( $menus ) || ! is_array( $menus ) ) {
			return;
		}
		$setup_menus = array();
		foreach ( $menus as $location => $menu_slug ) {
			$menu_object = wp_get_nav_menu_object( $menu_slug );
			$term_id = $menu_object->term_id;
			$setup_menus[$location] = $term_id;
		}
		if( empty( $setup_menus ) ) {
			print_r( 'No menus to set up locations for.' . "\n" );
			return;
		}
		set_theme_mod( 'nav_menu_locations', $setup_menus );
		print_r( 'Menus are set up.' . "\n" );
	}
	
}