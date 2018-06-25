<?php
/**
 * Author:  Andrei Baicus <andrei@themeisle.com>
 * On:      21/06/2018
 */

namespace ThemeIsle;

use ThemeIsle\Site_Import as Plugin;

/**
 * Class Site_Import_Admin
 *
 * @package ThemeIsle
 */
class Site_Import_Admin {
	private $sites = array();

	/**
	 * Initialize the Admin.
	 */
	public function init() {
		$theme_support = get_theme_support( 'themeisle-demo-import' );
		$this->sites   = $theme_support[0];

		// Add tab and content on about page.
		add_action( 'ti_about_page_after_tabs', array( $this, 'add_demo_import_tab' ) );
		add_action( 'ti_about_page_after_tabs_content', array( $this, 'add_demo_import_tab_content' ) );
		// Add short code to display site library.
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
	 * Enqueue script and styles.
	 */
	public function enqueue() {
		wp_register_script( 'themeisle-site-lib', plugin_dir_url( Plugin::get_dir() ) . '/assets/js/bundle.js', array(), Plugin::VERSION, true );

		wp_localize_script( 'themeisle-site-lib', 'themeisleSitesLibApi', $this->localize_sites_library() );

		wp_enqueue_script( 'themeisle-site-lib' );

		wp_enqueue_style( 'themeisle-site-lib', plugin_dir_url( Plugin::get_dir() ) . '/assets/css/style.css', array(), Plugin::VERSION );
	}

	/**
	 * Localize the sites library.
	 *
	 * @return array
	 */
	private function localize_sites_library() {
		$api = array(
			'root' => esc_url_raw( rest_url( Plugin::API_ROOT ) ),
		);
		if ( current_user_can( 'manage_options' ) ) {
			$api = array(
				'root'  => esc_url_raw( rest_url( Plugin::API_ROOT ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			);
		}
		$api['i18ln']       = $this->get_strings();
		$api['cachedSites'] = get_transient( Plugin::STORAGE_TRANSIENT );

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
}