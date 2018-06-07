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
		 * Holds the module slug.
		 *
		 * @since   1.0.0
		 * @access  protected
		 * @var     string $slug The module slug.
		 */
		protected $slug = 'site-import';

		/**
		 * Initialize the Site_Import.
		 */
		public function init() {
			$this->load_importer();
			add_shortcode( 'themeisle_site_library', array( $this, 'render_site_library' ) );
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
			wp_localize_script( 'themeisle-site-lib', 'themeisleSitesLibApi', array() );
			wp_enqueue_script( 'themeisle-site-lib' );
			wp_enqueue_style( 'themeisle-site-lib', plugin_dir_url( $this->get_dir() ) . '/assets/css/style.css', array(), $this->version );
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
