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
class Content_Importer {
	/*
	 * Import Remote XML file.
	 */
	public function import_remote_xml( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed to import content.' );
		}

		$params           = $request->get_json_params();
		$body             = $params['data'];
		$content_file_url = $body['contentFile'];

		if ( empty( $content_file_url ) ) {
			wp_send_json_error( 'No content to import.' );
		}
		$this->load_importer();
		set_time_limit( 10000 );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		$logger            = new Importer_Logger();
		$content_file      = file_get_contents( $content_file_url );
		$content_file_path = $this->save_xhr_return_path( $content_file );

		if ( ! file_exists( $content_file_path ) || ! is_readable( $content_file_path ) ) {
			wp_send_json_error( 'Export not readable.' );
		}
		$importer = new WXR_Importer();
		$importer->set_logger( $logger );
		$result = $importer->import( $content_file_path );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Could not import content.' );
		}
		unlink( $content_file_path );
		print_r( 'Content imported.' . "\n" );
		$this->maybe_bust_elementor_cache();

		//Set front page.
		if ( isset( $body['frontPage'] ) && ! empty( $body['frontPage'] ) ) {
			$this->setup_front_page( $body['frontPage'] );
		}

		die();
	}

	/**
	 * @param $content
	 *
	 * @return string
	 * @access
	 */
	private function save_xhr_return_path( $content ) {
		$wp_upload_dir = wp_upload_dir( null, false );
		$file_path     = $wp_upload_dir['basedir'] . '/themeisle-demo-import.xml';
		ob_start();
		echo $content;
		$result = ob_get_clean();
		file_put_contents( $file_path, $result );

		return $file_path;
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

		print_r( 'Front page set up.' . "\n" );
	}

	/**
	 * Maybe bust cache for elementor plugin.
	 */
	private function maybe_bust_elementor_cache() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			wp_remote_post(
				admin_url( 'admin-ajax.php' ),
				array(
					'body' => array(
						'action' => 'elementor_clear_cache'
					)
				)
			);
			print_r( 'Busted Elementor Cache.' . "\n" );
		}
	}

	/**
	 * Load the importer.
	 */
	private function load_importer() {
		if ( ! class_exists( 'WP_Importer' ) ) {
			defined( 'WP_LOAD_IMPORTERS' ) || define( 'WP_LOAD_IMPORTERS', true );
			require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}
		require dirname( __FILE__ ) . '/helpers/wxr_importer/class-logger.php';
		require dirname( __FILE__ ) . '/helpers/wxr_importer/class-logger-serversentevents.php';
		require dirname( __FILE__ ) . '/helpers/wxr_importer/class-wxr-importer.php';
		require dirname( __FILE__ ) . '/helpers/wxr_importer/class-wxr-import-info.php';
	}
}