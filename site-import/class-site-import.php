<?php
/**
 * Template Directory.
 *
 * @package OBFX
 */

/**
 * Class Site_Import
 *
 * @package ThemeIsle
 */
class ThemeIsle_Site_Import {
	/**
	 * Instance of Site_Import
	 *
	 * @var Site_Import
	 */
	protected static $instance = null;
	
	/**
	 * The version of this library
	 *
	 * @var string
	 */
	public $version = '1.0.0';
	
	/**
	 * Sites Library API URL.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var null | string
	 */
	private $api_root = null;
	
	/**
	 * Holds the sites data.
	 *
	 * @var null
	 */
	private $storage_transient = null;
	
	/**
	 * Initialize the Site_Import.
	 */
	public function init() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$theme_support = get_theme_support( 'themeisle-demo-import' );
		
		if ( empty( $theme_support ) ) {
			return;
		}
		
		$this->load_importer();
		$this->storage_transient = 'themeisle_sites_library_data';
		$this->api_root          = 'ti-sites-lib/v1';
		
		// Initialize Endpoints.
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		// Add tab and content on about page.
		add_action( 'ti_about_page_after_tabs', array( $this, 'add_demo_import_tab' ) );
		add_action( 'ti_about_page_after_tabs_content', array( $this, 'add_demo_import_tab_content' ) );
		// Add shortcode to display site library.
		add_shortcode( 'themeisle_site_library', array( $this, 'render_site_library' ) );
	}
	
	/**
	 * Add about page tab list item.
	 */
	public function add_demo_import_tab() { ?>
		<li style="margin-bottom: 0;" data-tab-id="<?php echo esc_attr( 'demo-import' ); ?>"><a class="nav-tab"
		                                                                                        href="#<?php echo esc_attr( 'demo-import' ); ?>"><?php echo wp_kses_post( esc_html__( 'Demo Import', 'textdomain' ) ); ?></a>
		</li>
		<?php
	}
	
	/**
	 * Add about page tab content.
	 */
	public function add_demo_import_tab_content() { ?>
		<div id="<?php echo esc_attr( 'demo-import' ); ?>">
			<?php do_shortcode( '[themeisle_site_library]' ); ?>
		</div>
		<?php
	}
	
	
	/**
	 * Register endpoints.
	 */
	public function register_endpoints() {
		register_rest_route( $this->api_root, '/save_fetched',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'save_fetched_listing_handler' ),
			)
		);
		register_rest_route( $this->api_root, '/install_plugins',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'install_plugins' ),
			)
		);
		register_rest_route( $this->api_root, '/import_content',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'import_remote_xml' ),
			)
		);
		register_rest_route( $this->api_root, '/import_theme_mods',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'import_theme_mods' ),
			)
		);
	}
	
	/**
	 * Render the sites library.
	 */
	public function render_site_library() {
		$this->enqueue(); ?>
		<div class="ti-sites-lib__wrap">
			<h3 class="wp-heading-inline"><?php echo __( 'Sites Library', 'textdomain' ); ?></h3>
			<hr class="wp-header-end">
			<div id="ti-sites-library">
				<app></app>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Enqueue Scripts.
	 */
	public function enqueue() {
		wp_register_script( 'themeisle-site-lib', plugin_dir_url( $this->get_dir() ) . '/assets/js/bundle.js', array(), $this->version, true );
		
		wp_localize_script( 'themeisle-site-lib', 'themeisleSitesLibApi', $this->localize_sites_library() );
		
		wp_enqueue_script( 'themeisle-site-lib' );
		
		wp_enqueue_style( 'themeisle-site-lib', plugin_dir_url( $this->get_dir() ) . '/assets/css/style.css', array(), $this->version );
	}
	
	/**
	 * Localize the sites library.
	 *
	 * @return array
	 */
	private function localize_sites_library() {
		$api = array(
			'root' => esc_url_raw( rest_url( $this->api_root ) ),
		);
		if ( current_user_can( 'manage_options' ) ) {
			$api = array(
				'root'  => esc_url_raw( rest_url( $this->api_root ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			);
		}
		$api['cachedSitesJSON'] = get_transient( $this->storage_transient );
		$api['sitesJSON']       = plugin_dir_url( $this->get_dir() ) . 'assets/vue/models/data.json';
		$api['i18ln']           = $this->get_strings();
		
		return $api;
	}
	
	/**
	 * Get module strings.
	 *
	 * @return array
	 */
	private function get_strings() {
		return array(
			'preview_btn' => __( 'Preview', 'textdomain' ),
			'import_btn'  => __( 'Import', 'textdomain' ),
			'cancel_btn'  => __( 'Cancel', 'textdomain' ),
		);
	}
	
	public function install_plugins( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( 'Sorry, you are not allowed to install plugins on this site.' );
		}
		
		$params  = $request->get_json_params();
		$plugins = $params['data'];
		
		if ( empty( $plugins ) || ! is_array( $plugins ) ) {
			return rest_ensure_response( 'No plugins to install.' );
		}
		$active_plugins = get_option( 'active_plugins' );
		
		foreach ( $plugins as $plugin_nicename => $plugin_slug ) {
			if ( in_array( $plugin_slug, $active_plugins ) ) {
				print_r( 'Plugin is already active' );
				continue;
			}
			$this->install_single_plugin( $plugin_slug );
			$this->activate_single_plugin( $plugin_slug );
		}
		die();
	}
	
	/**
	 * Install a single plugin
	 *
	 * @param string $plugin_slug plugin slug.
	 */
	private function install_single_plugin( $plugin_slug ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
		
		if ( is_dir( $plugin_dir ) ) {
			return;
		}
		
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/misc.php' );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'compatibility'     => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			)
		);
		
		$skin     = new \Plugin_Installer_Skin( array( 'api' => $api ) );
		$upgrader = new \Plugin_Upgrader( $skin );
		$install  = $upgrader->install( $api->download_link );
		if ( $install !== true ) {
			print_r( 'Error: Install process failed (' . $plugin_slug . '). var_dump of result follows.<br>' . '\n' );
			var_dump( $install );
		}
		print_r( 'Installed ' . $plugin_slug . '<br>' );
	}
	
	/**
	 * Activate a single plugin
	 *
	 * @param string $plugin_slug plugin slug.
	 */
	private function activate_single_plugin( $plugin_slug ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
		
		$plugin_path  = $plugin_dir . '/' . $plugin_slug . '.php';
		$plugin_entry = $plugin_slug . '/' . $plugin_slug . '.php';
		if ( ! file_exists( $plugin_path ) ) {
			$plugin_path  = $plugin_dir . '/' . 'index.php';
			$plugin_entry = $plugin_slug . '/' . 'index.php';
		}
		if ( ! file_exists( $plugin_path ) ) {
			print_r( 'No plugin under that directory.' );
			
			return;
		}
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		if ( is_plugin_active( $plugin_entry ) ) {
			print_r( $plugin_slug . ' already active.' );
			
			return;
		}
		$this->maybe_provide_activation_help( $plugin_slug, $plugin_dir );
		
		activate_plugin( $plugin_path );
		print_r( 'Activated ' . $plugin_slug . '.' );
		
		return;
	}
	
	/**
	 * Take care of plugins that are "speshul".
	 *
	 * @param $slug
	 * @param $path
	 */
	private function maybe_provide_activation_help( $slug, $path ) {
		if ( $slug === 'woocommerce' ) {
			require_once( $path . '/includes/admin/wc-admin-functions.php' );
		}
	}
	
	/**
	 * Save sites listing that was fetched.
	 *
	 * @param \WP_REST_Request $request retrieve fetchable data.
	 *
	 * @return string.
	 */
	public function save_fetched_listing_handler( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed.' );
		}
		
		$params = $request->get_params();
		
		if ( empty( $params['data'] ) ) {
			wp_send_json_error( 'No data sent.' );
		}
		
		set_transient( $this->storage_transient, $params['data'], 0.1 * MINUTE_IN_SECONDS );
		
		print_r( 'Saved JSON data.' );
		
		return $params['data'];
	}
	
	/*
	 * Import Remote XML file.
	 */
	public function import_remote_xml( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Not allowed to import content.' );
		}

		$params           = $request->get_json_params();
		$content_file_url = $params['data'];

		if( empty( $content_file_url ) ) {
			wp_send_json_error( 'No content to import.' );
		}

		set_time_limit( 10000 );

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$logger       = new \ThemeIsle_Importer_Logger();
		$content_file = \download_url( esc_url( $content_file_url ) );
		$importer     = new \ThemeIsle_WXR_Importer();
		$importer->set_logger( $logger );
		$result = $importer->import( $content_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Could not import content.' );
		}
		unlink( $content_file );
		print_r( 'Content imported.' );

		$this->maybe_bust_elementor_cache();
		die();
	}
	
	private function maybe_bust_elementor_cache() {
		if( class_exists( 'Elementor' ) ) {
			Elementor\Plugin::$instance->posts_css_manager->clear_cache();
		}
	}
	
	public function import_theme_mods( \WP_REST_Request $request ) {
		$params           = $request->get_json_params();
		$theme_mods_url = $params['data'];
		$theme_mods = wp_remote_get( $theme_mods_url );
		if( empty ( $theme_mods['body'] ) ) {
			wp_send_json_error( 'No theme mods to import.' );
		}
		print_r( $theme_mods['body'] );
		die();
	}
	
	
	/**
	 * Load the importer.
	 */
	private function load_importer() {
		if ( ! class_exists( 'WP_Importer' ) ) {
			defined( 'WP_LOAD_IMPORTERS' ) || define( 'WP_LOAD_IMPORTERS', true );
			require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}
		require dirname( __FILE__ ) . '/importer/class-logger.php';
		require dirname( __FILE__ ) . '/importer/class-logger-serversentevents.php';
		require dirname( __FILE__ ) . '/importer/class-wxr-importer.php';
		require dirname( __FILE__ ) . '/importer/class-wxr-import-info.php';
		require dirname( __FILE__ ) . '/importer/class-wxr-import-ui.php';
	}
	
	/**
	 * Method to return path to child class in a Reflective Way.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return string
	 */
	private function get_dir() {
		return dirname( __FILE__ ) . '/site-import';
	}
	
	/**
	 * Instantiate the class.
	 *
	 * @static
	 * @since  1.0.0
	 * @access public
	 * @return Site_Import
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}
		
		return self::$instance;
	}
	
	/**
	 * Disallow object clone
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0.0' );
	}
	
	/**
	 * Disable un-serializing
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0.0' );
	}
}
