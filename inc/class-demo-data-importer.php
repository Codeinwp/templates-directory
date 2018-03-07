<?php

namespace ThemeIsle\PageTemplatesDirectory;

class DemoDataImporter {
	/**
	 * @var DemoDataImporter
	 */
	protected static $instance = null;

	protected $plugin_upgrader = null;

	protected $plugins_dir = null;

	protected $data = null;

	protected $theme_support = null;

	protected function init() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		$this->plugins_dir = WP_PLUGIN_DIR . '/';

		// meta data with images
		add_filter( 'demodata_before_import_post_meta__thumbnail_id', array( $this, 'prepare_image_meta_id' ) );
		add_filter( 'demodata_before_import_post_meta_product_image_gallery', array( $this, 'prepare_image_meta_id' ) );

		// nav menus
		add_filter( 'demodata_import_after_mod_nav_menu_locations', array(
			$this,
			'filter_post_theme_mod_nav_menu_locations'
		) );
		add_filter( 'demodata_after_post_type_import', array( $this, 'remap_nav_menu_items' ) );
	}

	/**
	 * Register Rest endpoint for requests.
	 */
	public function register_endpoints() {
		register_rest_route( 'templates-directory', 'v1/get_demodata', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_demodata' ),
			'permission_callback' => array( $this, 'check_rest_nonce' ),
			'args'                => array(
				'nonce' => array( 'required' => true ),
				'demo'  => array( 'required' => true )
			)
		) );

		register_rest_route( 'templates-directory', 'v1/import_chunk', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_import_chunk' ),
			'permission_callback' => array( $this, 'check_rest_nonce' ),
			'args'                => array(
				'nonce'      => array( 'required' => true ),
				'importType' => array( 'required' => true ),
				'demo'       => array( 'required' => true )
			)
		) );

		register_rest_route( 'templates-directory', 'v1/import_plugin', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_import_plugin' ),
			'permission_callback' => array( $this, 'check_rest_nonce' ),
			'args'                => array(
				'nonce'  => array( 'required' => true ),
				'demo'   => array( 'required' => true ),
				'plugin' => array( 'required' => true )
			)
		) );

		register_rest_route( 'templates-directory', 'v1/activate_plugins', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_activate_plugins' ),
			'permission_callback' => array( $this, 'check_rest_nonce' ),
			'args'                => array(
				'nonce' => array( 'required' => true ),
				'demo'  => array( 'required' => true )
			)
		) );

		register_rest_route( 'templates-directory', 'v1/import_media', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_upload_media' ),
			'permission_callback' => array( $this, 'check_rest_nonce' ),
			'args'                => array(
				'nonce' => array( 'required' => true ),
				'demo'  => array( 'required' => true ),
				'last'  => array( 'required' => false ),
				'image' => array( 'required' => true )
			)
		) );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function rest_get_demodata( \WP_REST_Request $request ) {
		$theme_support = $this->get_theme_support();
		$params        = $request->get_params();

		if ( empty( $params['demo'] ) || empty( $theme_support[ $params['demo'] ] ) ) {
			return rest_ensure_response( 'what?' );
		}

		$demo = $params['demo'];

		$this->set_remote_data( $demo );
		$theme_support = $theme_support[ $demo ];

		$cached = $this->get_remote_data();

		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get( $theme_support['demo_url'] . '/wp-json/demodata/v1/data' );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return rest_ensure_response( array(
				'status' => 'unavailable',
				'data'   => $theme_support['demo_url']
			) );
		}

		$results = wp_remote_retrieve_body( $response );

		$results = json_decode( $results, true );

		if ( empty( $results ) ) {
			return rest_ensure_response( 'empty response' );
		}

		set_transient( 'demodata_for_' . $demo, $results, DAY_IN_SECONDS );

		return rest_ensure_response( $results );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function rest_import_chunk( \WP_REST_Request $request ) {
		$theme_support = $this->get_theme_support();

		$params = $request->get_params();

		if ( empty( $params['demo'] ) || empty( $theme_support[ $params['demo'] ] ) ) {
			return rest_ensure_response( 'what?' );
		}

		$demo       = $params['demo'];
		$importType = $params['importType'];

		$theme_support = $theme_support[ $demo ];

		$response = array(
			'themeSupport' => $theme_support,
			'succces'      => true,
			'msg'          => 'Bad call'
		);

		$this->set_remote_data( $demo );

		switch ( $importType ) {
			case "before_settings":
				$response['msg'] = $this->import_before_settings();
				break;
			case "taxonomies":
				$response['msg'] = $this->import_taxonomies( $demo, $theme_support['demo_url'] );
				break;
			case "post_types":
				$response['msg'] = $this->import_post_types( $demo, $theme_support['demo_url'] );
				break;
			case "widgets":
				$response['msg'] = $this->import_widgets( $demo );
				break;
			case "after_settings":
				$response['msg'] = $this->import_after_settings();
				break;
			default:
				break;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function rest_import_plugin( \WP_REST_Request $request ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to install plugins on this site.' ) );
		}

		$params = $request->get_params();

		$this->set_remote_data( $params['demo'] );

		$pluginSlug = $params['plugin'];

		$demoData = $this->get_remote_data( 'active_plugins' );

		// a server side check to be sure that this plugin is really required by demo
		if ( ! in_array( $pluginSlug, $demoData ) ) {
			return rest_ensure_response( 'Plugin not required' );
		}

		$active_plugins = get_option( 'active_plugins' );

		if ( in_array( $pluginSlug, $active_plugins ) ) {
			return rest_ensure_response( 'Plugin is already active' );
		}

		$this->maybe_init_plugin_upgrader();
		include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..

		// if the plugin is already installed we only need to activate it.
		if ( file_exists( $this->plugins_dir . $pluginSlug ) ) {
			// skip
			return rest_ensure_response( 'Activated only' );
		}

		$install = $this->_install_plugin( $pluginSlug );

		if ( ! is_wp_error( $install ) ) {
			if ( file_exists( $this->plugins_dir . $pluginSlug ) ) {

				return rest_ensure_response( array(
					'success' => true,
				) );

			}

			// weirdly, the file exists
			return rest_ensure_response( $this->plugins_dir . $pluginSlug );
		}

		// this is a wpError, debug it
		return rest_ensure_response( $install );
	}

	/**
	 * @param \WP_REST_Request $request
	 */
	public function rest_activate_plugins( \WP_REST_Request $request ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to install plugins on this site.' ) );
		}

		$params = $request->get_params();

		$this->set_remote_data( $params['demo'] );

		$active_plugins = $this->get_remote_data( 'active_plugins' );

		rest_ensure_response( activate_plugins( $active_plugins, '', false, true ) );
	}

	/**
	 * @param $request
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function rest_upload_media( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json( __( 'Sorry, you are not allowed to upload images on this site.' ) );
		}

		$theme_support = $this->get_theme_support();
		$params        = $request->get_params();

		if ( empty( $params['demo'] ) || empty( $theme_support[ $params['demo'] ] ) ) {
			return rest_ensure_response( 'what?' );
		}

		$importedMedia = $this->get_imported_data( 'media' );

		if ( ! empty( $params['last'] && empty( $importedMedia ) ) ) {
			$this->set_imported_data( 'media', $params['last'] );

			return rest_ensure_response( 'success' );
		}

		$imageID       = $params['image'];
		$demo          = $params['demo'];
		$theme_support = $theme_support[ $demo ];

		$response = wp_remote_get( $theme_support['demo_url'] . '/wp-json/demodata/v1/media?id=' . $imageID );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return rest_ensure_response( 'not 200, meh' );
		}

		$results = wp_remote_retrieve_body( $response );

		$results = json_decode( $results, true );

		$media = $results['data']['media'];

		$attachID = $this->upload_media( $media['url'], $media['parent'] );

		return rest_ensure_response( $attachID );
	}

	protected function import_taxonomies( $demo, $demo_url ) {
		$imported_ids = $current_terms = array();
		$taxonomies   = $this->get_remote_data( 'taxonomies' );

		$current_terms = $this->get_imported_data( 'taxonomies' );

		$request_url = $demo_url . '/wp-json/demodata/v1/terms';

		foreach ( $taxonomies as $index => $args ) {
			$tax          = $args['name'];
			$request_data = array(
				'taxonomy' => $args['name'],
				'ids'      => $args['ids'],
			);

			$request_args = array(
				'method'    => 'GET',
				'timeout'   => 5,
				'blocking'  => true,
				'body'      => $request_data,
				'sslverify' => false,
			);

			$response = wp_remote_request( $request_url, $request_args );

			if ( is_wp_error( $response ) ) {
				return rest_ensure_response( $response );
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

			foreach ( $response_data['data']['terms'] as $i => $term ) {

				if ( ! empty( $current_terms[ $tax ][ $term['term_id'] ] ) ) {
					continue;
				}

				if ( ! isset( $imported_ids[ $tax ] ) ) {
					$imported_ids[ $tax ] = array();
				}

				$term_args = array(
					'description' => $term['description'],
					'slug'        => $term['slug'],
				);

				$new_id = wp_insert_term(
					$term['name'], // the term
					$term['taxonomy'], // the taxonomy
					$term_args
				);

				if ( is_wp_error( $new_id ) ) {
					// If the term exists we will us the existing ID
					if ( ! empty( $new_id->error_data['term_exists'] ) ) {
						$imported_ids[ $tax ][ $term['term_id'] ] = $new_id->error_data['term_exists'];
					}
				} else {
					$imported_ids[ $tax ][ $term['term_id'] ] = $new_id['term_id'];
					// @TODO maybe import meta?
				}

				// Clear the term cache
				if ( ! is_wp_error( $new_id ) && ! empty( $new_id['term_id'] ) ) {
					clean_term_cache( $new_id['term_id'], $args['tax'] );
				}
			}

			foreach ( $response_data['data']['terms'] as $i => $term ) {
				if ( isset( $imported_ids[ $tax ][ $term['parent'] ] ) ) {
					wp_update_term( $imported_ids[ $tax ][ $term['term_id'] ], $args['tax'], array(
						'parent' => $imported_ids[ $tax ][ $term['parent'] ]
					) );
				}
			}
		}

		if ( ! empty( $imported_ids ) ) {
			$this->set_imported_data( 'taxonomies', $imported_ids );
		}

		return $imported_ids;
	}

	protected function import_post_types( $demo, $demo_url ) {
		$imported_ids = array();
		$post_types   = $this->get_remote_data( 'post_types' );

		$imported      = get_option( 'demodata_imported_data' );
		$current_posts = array();

		if ( isset( $imported['post_types'] ) ) {
			$current_posts = $imported['post_types'];
		}

		$request_url = $demo_url . '/wp-json/demodata/v1/posts';

		foreach ( $post_types as $post_type => $args ) {
			$post_type = $args['name'];

			$request_data = array(
				'post_type' => $post_type,
				'ids'       => $args['ids']
			);

			$request_args = array(
				'method'    => 'GET',
				'timeout'   => 5,
				'blocking'  => true,
				'body'      => $request_data,
				'sslverify' => false,
			);

			$response = wp_remote_request( $request_url, $request_args );
			if ( is_wp_error( $response ) ) {
				return rest_ensure_response( $response );
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

			foreach ( $response_data['data']['posts'] as $i => $post ) {
				if ( ! empty( $current_posts[ $post_type ][ $post['ID'] ] ) ) {
					continue;
				}

				$post_args = array(
					'import_id'             => $post['ID'],
					'post_title'            => wp_strip_all_tags( $post['post_title'] ),
					'post_content'          => $post['post_content'],
					'post_content_filtered' => $post['post_content_filtered'],
					'post_excerpt'          => $post['post_excerpt'],
					'post_status'           => $post['post_status'],
					'post_name'             => $post['post_name'],
					'post_type'             => $post['post_type'],
					'post_date'             => $post['post_date'],
					'post_date_gmt'         => $post['post_date_gmt'],
					'post_modified'         => $post['post_modified'],
					'post_modified_gmt'     => $post['post_modified_gmt'],
					'menu_order'            => $post['menu_order'],
					'meta_input'            => array(
						'imported_by_demodata' => true
					)
				);

				if ( ! empty( $post['meta'] ) ) {
					foreach ( $post['meta'] as $key => $meta ) {
						if ( $meta === null || $meta === array( null ) ) {
							continue;
						}

						if ( ! empty( $meta ) ) {
							$meta = maybe_unserialize( $meta );
						}

						if ( ! empty( $meta ) ) {
							$post_args['meta_input'][ $key ] = apply_filters( 'demodata_before_import_post_meta_' . $key, $meta );
						}
					}
				}

				if ( ! empty( $post['taxonomies'] ) ) {
					$post_args['post_category'] = array();
					$post_args['tax_input']     = array();

					foreach ( $post['taxonomies'] as $taxonomy => $terms ) {

						if ( ! taxonomy_exists( $taxonomy ) ) {
							continue;
						}

						$post_args['tax_input'][ $taxonomy ] = array();

						foreach ( $terms as $term ) {
							if ( is_numeric( $term ) && isset( $imported['taxonomies'][ $taxonomy ][ $term ] ) ) {
								$term = $imported['taxonomies'][ $taxonomy ][ $term ];
							}

							$post_args['tax_input'][ $taxonomy ][] = $term;
						}
					}
				}

				$post_id = wp_insert_post( apply_filters( 'demodata_filter_post_type_args', $post_args ) );

				if ( is_wp_error( $post_id ) || empty( $post_id ) ) {
					$imported_ids[ $post_type ][ $post['ID'] ] = $post_id;
				} else {
					$imported_ids[ $post_type ][ $post['ID'] ] = $post_id;
				}
			}
		}

		if ( ! empty( $imported_ids ) ) {

			$imported_ids = apply_filters( 'demodata_after_post_type_import', $imported_ids );

			$this->set_imported_data( 'post_types', $imported_ids );
			$this->remap_images_attachments_parents( $imported_ids );
		}

		return $imported_ids;
	}

	protected function import_widgets( $demo ) {
		$data = $this->get_remote_data( 'widgets' );

		// First let's remove all the widgets in sidebars to avoid a big mess
		$sidebars_widgets = wp_get_sidebars_widgets();

		$backup = get_option( 'demodata_backup_widgets_before_importing_' . $demo );
		if ( empty( $backup ) ) {
			update_option( 'demodata_backup_widgets_before_importing_' . $demo, $sidebars_widgets );
		}

		foreach ( $sidebars_widgets as $sidebarID => $widgets ) {
			if ( $sidebarID != 'wp_inactive_widgets' ) {
				$sidebars_widgets[ $sidebarID ] = array();
			}
		}
		wp_set_sidebars_widgets( $sidebars_widgets );

		// Let's get to work
		$json_data = json_decode( base64_decode( $data ), true );

		$sidebar_data = $json_data[0];
		$widget_data  = $json_data[1];

		foreach ( $sidebar_data as $type => $sidebar ) {
			$count = count( $sidebar );
			for ( $i = 0; $i < $count; $i ++ ) {
				$widget               = array();
				$widget['type']       = trim( substr( $sidebar[ $i ], 0, strrpos( $sidebar[ $i ], '-' ) ) );
				$widget['type-index'] = trim( substr( $sidebar[ $i ], strrpos( $sidebar[ $i ], '-' ) + 1 ) );
				if ( ! isset( $widget_data[ $widget['type'] ][ $widget['type-index'] ] ) ) {
					unset( $sidebar_data[ $type ][ $i ] );
				}
			}
			$sidebar_data[ $type ] = array_values( $sidebar_data[ $type ] );
		}

		$sidebar_data = array( array_filter( $sidebar_data ), $widget_data );

		if ( ! $this->parse_import_data( $sidebar_data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Widgets importer method
	 *
	 * @param $import_array
	 *
	 * @return bool
	 */
	protected function parse_import_data( $import_array ) {
		// Bail if we have no data to work with
		if ( empty( $import_array[0] ) || empty( $import_array[1] ) ) {
			return false;
		}

		$sidebars_data = $import_array[0];
		$widget_data   = $import_array[1];

		$current_sidebars = wp_get_sidebars_widgets();
		$new_widgets      = array();

		foreach ( $sidebars_data as $import_sidebar => $import_widgets ) :
			$current_sidebars[ $import_sidebar ] = array();
			foreach ( $import_widgets as $import_widget ) :

				$import_widget = json_decode( json_encode( $import_widget ), true );

				$type                = trim( substr( $import_widget, 0, strrpos( $import_widget, '-' ) ) );
				$index               = trim( substr( $import_widget, strrpos( $import_widget, '-' ) + 1 ) );
				$current_widget_data = get_option( 'widget_' . $type );
				$new_widget_name     = $this->get_new_widget_name( $type, $index );
				$new_index           = trim( substr( $new_widget_name, strrpos( $new_widget_name, '-' ) + 1 ) );

				if ( ! empty( $new_widgets[ $type ] ) && is_array( $new_widgets[ $type ] ) ) {
					while ( array_key_exists( $new_index, $new_widgets[ $type ] ) ) {
						$new_index ++;
					}
				}
				$current_sidebars[ $import_sidebar ][] = $type . '-' . $new_index;
				if ( array_key_exists( $type, $new_widgets ) ) {
					$new_widgets[ $type ][ $new_index ] = $widget_data[ $type ][ $index ];
				} else {
					$current_widget_data[ $new_index ] = $widget_data[ $type ][ $index ];
					$new_widgets[ $type ]              = $current_widget_data;
				}

				// All widgets should use the new format _multiwidget
				$new_widgets[ $type ]['_multiwidget'] = 1;
			endforeach;
		endforeach;

		if ( ! empty( $new_widgets ) && ! empty( $current_sidebars ) ) {
			foreach ( $new_widgets as $type => $content ) {
				// Save the data for each widget type
				$content = apply_filters( "demodata_import_widget_{$type}", $content, $type );
				update_option( 'widget_' . $type, $content );
			}

			// Save the sidebars data
			wp_set_sidebars_widgets( $current_sidebars );

			return true;
		}

		return false;
	}

	protected function get_new_widget_name( $widget_name, $widget_index ) {
		$current_sidebars = get_option( 'sidebars_widgets' );
		$all_widget_array = array();
		foreach ( $current_sidebars as $sidebar => $widgets ) {
			if ( ! empty( $widgets ) && is_array( $widgets ) && $sidebar != 'wp_inactive_widgets' ) {
				foreach ( $widgets as $widget ) {
					$all_widget_array[] = $widget;
				}
			}
		}
		while ( in_array( $widget_name . '-' . $widget_index, $all_widget_array ) ) {
			$widget_index ++;
		}
		$new_widget_name = $widget_name . '-' . $widget_index;

		return $new_widget_name;
	}

	protected function import_before_settings() {
		$settings = $this->get_remote_data( 'before_settings' );
		$backup   = get_option( 'demodata_backup_before_settings' );

		if ( empty( $backup ) ) {
			$backup = array(
				'options' => array(),
				'mods'    => array()
			);
		}

		if ( ! empty( $settings['options'] ) ) {
			foreach ( $settings['options'] as $option => $value ) {
				$back_val = get_option( $option );
				if ( $back_val ) {
					$backup['options'][ $option ] = $back_val;
				}
				update_option( $option, apply_filters( 'demodata_import_before_option_' . $option, $value ) );
			}
		}

		if ( ! empty( $settings['mods'] ) ) {
			foreach ( $settings['mods'] as $mod => $value ) {
				$back_val = get_theme_mod( $mod );
				if ( $back_val ) {
					$backup['mods'][ $mod ] = $back_val;
				}
				set_theme_mod( $mod, apply_filters( 'demodata_import_before_mod_' . $mod, $value ) );
			}
		}

		return update_option( 'demodata_backup_before_settings', $backup );
	}

	protected function import_after_settings() {
		$settings = $this->get_remote_data( 'after_settings' );
		$backup   = get_option( 'demodata_backup_after_settings' );

		if ( empty( $backup ) ) {
			$backup = array(
				'options' => array(),
				'mods'    => array()
			);
		}

		if ( ! empty( $settings['options'] ) ) {
			foreach ( $settings['options'] as $option => $value ) {
				$back_val = get_option( $option );
				if ( $back_val ) {
					$backup['options'][ $option ] = $back_val;
				}
				update_option( $option, apply_filters( 'demodata_import_after_option_' . $option, $value ) );
			}
		}

		if ( ! empty( $settings['mods'] ) ) {
			foreach ( $settings['mods'] as $mod => $value ) {
				$back_val = get_theme_mod( $mod );
				if ( $back_val ) {
					$backup['mods'][ $mod ] = $back_val;
				}
				set_theme_mod( $mod, apply_filters( 'demodata_import_after_mod_' . $mod, $value ) );
			}
		}

		return update_option( 'demodata_backup_after_settings', $backup );
	}

	/** === FILTERS === **/

	protected function remap_images_attachments_parents( $imported_ids ) {
		$media      = $this->get_imported_data( 'media' );
		$post_types = $this->get_imported_data( 'post_types' );

		if ( ! empty( $media['images'] ) ) {
			foreach ( $media['images'] as $remote => $local ) {
				$attach = get_post( $local );

				$newId = $this->array_search_key( $attach->post_parent, $post_types );

				if ( ! empty( $newId ) ) {
					wp_update_post( array(
						'ID'          => $local,
						'post_parent' => (int) $newId
					) );
				}
			}
		}
	}

	/**
	 * Replace each menu id from `nav_menu_locations` with the new menus ids
	 *
	 * @param $locations
	 *
	 * @return mixed
	 */
	public function filter_post_theme_mod_nav_menu_locations( $locations ) {
		if ( empty( $locations ) ) {
			return $locations;
		}

		$demodata = $this->get_imported_data( 'taxonomies' );

		foreach ( $locations as $location => $menu ) {
			if ( ! empty( $demodata['nav_menu'][ $menu ] ) ) {
				$locations[ $location ] = $demodata['nav_menu'][ $menu ];
			}
		}

		return $locations;
	}

	/**
	 *
	 */
	public function remap_nav_menu_items( $imported_post_types ) {

		if ( empty( $imported_post_types['nav_menu_item'] ) ) {
			return $imported;
		}

		$taxonomies = $this->get_imported_data( 'taxonomies' );

		$nav_menu_ids = $imported_post_types['nav_menu_item'];

		foreach ( $nav_menu_ids as $remoteID => $nav_menu_id ) {
			$parent = get_post_meta( $nav_menu_id, '_menu_item_menu_item_parent', true );
			if ( ! empty( $parent ) && isset( $nav_menu_ids[ $parent ] ) ) {
				update_post_meta( $nav_menu_id, '_menu_item_menu_item_parent', $nav_menu_ids[ $parent ] );
			}

			$menu_item_type      = get_post_meta( $nav_menu_id, '_menu_item_type', true );
			$menu_item_object    = get_post_meta( $nav_menu_id, '_menu_item_object', true );
			$menu_item_object_id = get_post_meta( $nav_menu_id, '_menu_item_object_id', true );

			// Try to remap custom objects in nav items
			switch ( $menu_item_type ) {
				case 'taxonomy':
					if ( isset( $taxonomies[ $menu_item_object ][ $menu_item_object_id ] ) ) {
						$menu_item_object_id = $taxonomies[ $menu_item_object ][ $menu_item_object_id ];
					}
					break;
				case 'post_type':
					if ( isset( $imported_post_types[ $menu_item_object ][ $menu_item_object_id ] ) ) {
						$menu_item_object_id = $imported_post_types[ $menu_item_object ][ $menu_item_object_id ];
					}
					break;
				case 'custom':
					/**
					 * Remap custom links
					 */
					$meta_url = get_post_meta( $nav_menu_id, '_menu_item_url', true );
					if ( isset( $this->theme_support['demo_url'] ) && ! empty( $meta_url ) ) {
						$meta_url = str_replace( $this->theme_support['demo_url'], site_url(), $meta_url );
						update_post_meta( $nav_menu_id, '_menu_item_url', $meta_url );
					}
					break;
				default:
					// no clue
					break;
			}

			update_post_meta( $nav_menu_id, '_menu_item_object_id', $menu_item_object_id );
		}

		return $imported;
	}

	/** === HELPERS === **/

	/**
	 * Save data from transient into a protected property, because we'll use it often
	 *
	 * @param $demo
	 */
	protected function set_remote_data( $demo ) {
		//delete_transient( 'demodata_for_' . $demo );
		$data       = get_transient( 'demodata_for_' . $demo );
		$this->data = $data;
	}

	/**
	 * Upload image as attachment.
	 *
	 * @param string $url Url to download.
	 * @param $parent
	 *
	 * @return int|mixed|object
	 */
	protected function upload_media( $url, $parent ) {
		// Need to require these files
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
			require_once( ABSPATH . "wp-admin" . '/includes/media.php' );
		}

		$tmp_file = download_url( $url );
		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}
		$file = array();

		preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches );
		$file['name']     = basename( $matches[0] );
		$file['tmp_name'] = $tmp_file;
		$image_id         = media_handle_sideload( $file, $parent );
		if ( is_wp_error( $image_id ) ) {
			return $image_id;
		}
		$attach_data = wp_generate_attachment_metadata( $image_id, get_attached_file( $image_id ) );
		if ( is_wp_error( $attach_data ) ) {
			return $attach_data;
		}
		wp_update_attachment_metadata( $image_id, $attach_data );

		return $image_id;
	}

	/**
	 * We should allow svg uploads but only inside our REST route `demodata/v1/upload_media`
	 *
	 * @param $mimes
	 *
	 * @return mixed
	 */
	public function allow_svg_upload( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	public function prepare_image_meta_id( $value ) {
		$media = $this->get_imported_data( 'media' );

		if ( isset( $media['images'][ $value ] ) ) {
			$attached_ids = explode( ',', $value );

			foreach ( $attached_ids as $i => $attach_id ) {
				$attached_ids[ $i ] = $media['images'][ $attach_id ];
			}

			return join( ',', $attached_ids );
		}

		return $value;
	}

	/**
	 * A primitive which installs a plugin via the plugin_api.
	 *
	 * @param $plugin
	 *
	 * @return array|object|\WP_Error
	 */
	protected function _install_plugin( $plugin ) {
		// some plugins may come with the full slug like `plugin/plugin.php` but we only need the dir name
		if ( strpos( $plugin, '/' ) !== false ) {
			$plugin = explode( '/', $plugin, 2 );
			$plugin = $plugin[0];
		}

		$api = plugins_api(
			'plugin_information', array(
				'slug'   => $plugin,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		return $this->plugin_upgrader->install( $api->download_link, array(
			'clear_update_cache' => true
		) );
	}

	/**
	 * Gets a certain entry from data object.
	 *
	 * @param null $key
	 *
	 * @return bool|array
	 */
	protected function get_remote_data( $key = null ) {
		if ( empty( $key ) ) {
			return $this->data;
		} elseif ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return false;
	}

	/**
	 * Save the imported data, by key, for later mapping.
	 *
	 * @param $key
	 * @param $value
	 */
	protected function set_imported_data( $key, $value ) {
		wp_cache_delete( 'demodata_imported_data', 'options' );
		$imported = get_option( 'demodata_imported_data' );

		if ( ! is_array( $imported ) ) {
			$imported = array();
		}

		$imported[ $key ] = $value;

		update_option( 'demodata_imported_data', $imported, true );
	}

	/**
	 * Get already imported data by key
	 *
	 * @param $key
	 *
	 * @return array
	 */
	protected function get_imported_data( $key ) {
		$imported = get_option( 'demodata_imported_data' );

		if ( isset( $imported[ $key ] ) ) {
			return $imported[ $key ];
		}

		return array();
	}

	/**
	 * Returns the theme support for `demo-data` feature or will die in its absence.
	 * @return array
	 */
	protected function get_theme_support() {
		if ( $this->theme_support !== null ) {
			return $this->theme_support;
		}

		$theme_support = get_theme_support( 'demo-data' );

		if ( empty( $theme_support ) || empty( $theme_support[0] ) ) {
			wp_send_json_error( 'Current Theme doesn\'t support demo data' ); // will die here
		}

		$this->theme_support = $theme_support[0];

		return $this->theme_support;
	}

	/**
	 * In order to be able to install plugins we need a plugin upgrader object
	 * So we will require the class and create it once with this method
	 */
	protected function maybe_init_plugin_upgrader() {
		if ( ! empty( $this->plugin_upgrader ) ) {
			return;
		}

		$this->plugin_upgrader = new \Plugin_Upgrader( new Quiet_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
	}

	/**
	 * Deep searching an array by key and return the value.
	 *
	 * @param $needle_key
	 * @param $array
	 *
	 * @return bool
	 */
	protected function array_search_key( $needle_key, $array ) {
		foreach ( $array as $key => $value ) {
			if ( $key === $needle_key ) {
				return $value;
			}

			if ( is_array( $value ) ) {
				if ( ( $result = $this->array_search_key( $needle_key, $value ) ) !== false ) {
					return $result;
				}
			}
		}

		return false;
	}

	/**
	 * Permission check callback for rest routes.
	 * Also checks if the current theme has theme support for demo-data
	 *
	 * @param $request
	 *
	 * @return false|int
	 */
	public function check_rest_nonce( $request ) {
		// Get the nonce we've been given
		$nonce = $request->get_param( 'nonce' );
		if ( ! empty( $nonce ) ) {
			$nonce = wp_unslash( $nonce );
		}

		// check if the current theme supports the `demo-data` feature, if not it will die internally
		$this->get_theme_support();

		return wp_verify_nonce( $nonce, 'demodata_wp_rest' );
	}

	/**
	 * @static
	 * @since 1.0.0
	 * @access public
	 * @return DemoDataImporter
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0.0' );
	}
}

//includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
include_once( ABSPATH . 'wp-admin/includes/file.php' );
include_once( ABSPATH . 'wp-admin/includes/misc.php' );
include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class Quiet_Skin extends \WP_Upgrader_Skin {

	public $done_header = true;

	public $done_footer = true;

	public function feedback( $string ) {
		// just keep it quiet
	}
}