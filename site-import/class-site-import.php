<?php
/**
 * Template Directory.
 *
 * @package OBFX
 */

namespace ThemeIsle;

if ( ! class_exists( '\ThemeIsle\Site_Import' ) ) {
	/**
	 * Class Site_Import
	 *
	 * @package ThemeIsle
	 */
	class Site_Import {
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
			$this->load_importer();
			$this->storage_transient = 'themeisle_sites_library_data';
			$this->api_root          = 'ti-sites-lib/v1';
			add_action( 'rest_api_init', array( $this, 'register_endpoint' ) );
			add_shortcode( 'themeisle_site_library', array( $this, 'render_site_library' ) );
			add_action( 'wp_ajax_saveTiSitesListing', array( $this, 'save_fetched_listing_handler' ) );
		}
		
		public function register_endpoint() {
			
			register_rest_route( $this->api_root, '/save_fetched',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'save_fetched_listing_handler' ),
				)
			);
		}
		
		/**
		 * Render the sites library.
		 */
		public function render_site_library() {
			$this->enqueue(); ?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php echo __( 'Sites Library', 'textdomain' ); ?></h1>
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
			$api['i18ln'] = $this->get_strings();
			return $api;
		}
		
		/**
		 *
		 */
		private function get_strings() {
			return array(
				'preview_btn' => __( 'Preview', 'textdomain' ),
			);
		}
		
		/**
		 * Save sites listing that was fetched.
		 */
		public function save_fetched_listing_handler( \WP_REST_Request $request ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return 'Not allowed.';
			}
			if ( empty( $_GET['data'] ) ) {
				return 'No data sent.';
			}
			
			set_transient( $this->storage_transient, $_GET['data'], 0.1 * MINUTE_IN_SECONDS );
			
			print_r( 'Saved JSON data.' );
			return $_GET['data'];
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
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @access public
		 * @since  1.0.0
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
		 * @since  1.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'textdomain' ), '1.0.0' );
		}
	}
}
