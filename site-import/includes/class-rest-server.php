<?php
/**
 * Author:  Andrei Baicus <andrei@themeisle.com>
 * On:      21/06/2018
 */

namespace ThemeIsle;

use ThemeIsle\Site_Import as Plugin;

/**
 * Class Rest_Server
 *
 * @package ThemeIsle
 */
class Rest_Server {
	/**
	 * Initialize the rest functionality.
	 */
	public function init() {
		delete_transient( Plugin::STORAGE_TRANSIENT );
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}
	
	/**
	 * Register endpoints.
	 */
	public function register_endpoints() {
		register_rest_route( Plugin::API_ROOT, '/initialize_sites_library',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'init_library' ),
			)
		);
		register_rest_route( Plugin::API_ROOT, '/install_plugins',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'run_plugin_importer' ),
			)
		);
		register_rest_route( Plugin::API_ROOT, '/import_content',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'run_xml_importer' ),
			)
		);
		register_rest_route( Plugin::API_ROOT, '/import_theme_mods',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'run_theme_mods_importer' ),
			)
		);
		register_rest_route( Plugin::API_ROOT, '/import_widgets',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'run_widgets_importer' ),
			)
		);
	}
	
	/**
	 * Initialize Library
	 *
	 * @return array
	 */
	public function init_library() {
		$cached = get_transient( Plugin::STORAGE_TRANSIENT );
		
		if ( ! empty( $cached ) ) {
			print_r( 'Loading sites from cache...' . "\n" );
			return $cached;
		}
		$theme_support = get_theme_support( 'themeisle-demo-import' );

		if ( empty( $theme_support[0] ) || ! is_array( $theme_support[0] ) ) {
			return array();
		}
		
		$data = array();
		
		foreach ( $theme_support[0] as $slug => $args ) {
			$request = wp_remote_get( $args['url'] . '/wp-json/ti-demo-data/data' );
			
			if ( empty( $request['body'] ) || ! isset( $request['body'] ) ) {
				continue;
			}
			
			$data[ $slug ]               = json_decode( $request['body'], true );
			$data[ $slug ]['screenshot'] = $args['screenshot'];
			$data[ $slug ]['demo_url']   = $args['url'];
			$data[ $slug ]['title']      = $args['title'];
		}
		
		set_transient( Plugin::STORAGE_TRANSIENT, $data, 0.1 * MINUTE_IN_SECONDS );
		
		return $data;
	}
	
	/**
	 * Run the plugin importer.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function run_plugin_importer( \WP_REST_Request $request ) {
//		wp_send_json_error( 'kill it.' );
		require_once 'importers/class-plugin-importer.php';
		if ( ! class_exists( '\ThemeIsle\Plugin_Importer' ) ) {
			wp_send_json_error( 'Issue with plugin importer' );
		}
		$plugin_importer = new Plugin_Importer();
		$plugin_importer->install_plugins( $request );
	}
	
	/**
	 * Run the XML importer.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function run_xml_importer( \WP_REST_Request $request ) {
//		wp_send_json_error( 'kill it.' );
		require_once 'importers/class-content-importer.php';
		if ( ! class_exists( '\ThemeIsle\Content_Importer' ) ) {
			wp_send_json_error( 'Issue with content importer' );
		}
		$content_importer = new Content_Importer();
		$content_importer->import_remote_xml( $request );
	}
	
	/**
	 * Run the theme mods importer.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function run_theme_mods_importer( \WP_REST_Request $request ) {
//		wp_send_json_error( 'kill it.' );
		require_once 'importers/class-theme-mods-importer.php';
		if ( ! class_exists( '\ThemeIsle\Theme_Mods_Importer' ) ) {
			wp_send_json_error( 'Issue with theme mods importer' );
		}
		$theme_mods_importer = new Theme_Mods_Importer();
		$theme_mods_importer->import_theme_mods( $request );
	}
	
	/**
	 * Run the widgets importer.
	 *
	 * @param \WP_REST_Request $request
	 */
	public function run_widgets_importer( \WP_REST_Request $request ) {
//		wp_send_json_error( 'kill it.' );
		require_once 'importers/class-widgets-importer.php';
		if ( ! class_exists( '\ThemeIsle\Widgets_Importer' ) ) {
			wp_send_json_error( 'Issue with theme mods importer' );
		}
		$theme_mods_importer = new Widgets_Importer();
		$theme_mods_importer->import_widgets( $request );
	}
}