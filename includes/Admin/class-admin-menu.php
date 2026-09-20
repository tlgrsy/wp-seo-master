<?php
/**
 * Admin Menu Sınıfı
 * 
 * Admin panelinde SEO ayarları menüsünü oluşturur.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM\Admin;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu Sınıfı
 * 
 * Admin panelinde SEO ayarları menüsünü oluşturur.
 *
 * @since 1.0.0
 */
class Admin_Menu {

	/**
	 * Sınıfı başlatır
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Menü sayfalarını ekler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_menu_pages() {
		// Ana menü
		add_menu_page(
			__( 'WP SEO Master', 'wp-seo-master' ),
			__( 'SEO Master', 'wp-seo-master' ),
			'manage_options',
			'wpsm-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-chart-line',
			80
		);

		// Alt menüler
		add_submenu_page(
			'wpsm-dashboard',
			__( 'Genel Ayarlar', 'wp-seo-master' ),
			__( 'Genel Ayarlar', 'wp-seo-master' ),
			'manage_options',
			'wpsm-general',
			array( $this, 'render_general_settings' )
		);

		add_submenu_page(
			'wpsm-dashboard',
			__( 'Şema Ayarları', 'wp-seo-master' ),
			__( 'Şema', 'wp-seo-master' ),
			'manage_options',
			'wpsm-schema',
			array( $this, 'render_schema_settings' )
		);

		add_submenu_page(
			'wpsm-dashboard',
			__( 'Sosyal Medya Ayarları', 'wp-seo-master' ),
			__( 'Sosyal Medya', 'wp-seo-master' ),
			'manage_options',
			'wpsm-social',
			array( $this, 'render_social_settings' )
		);

		add_submenu_page(
			'wpsm-dashboard',
			__( 'Sitemap Ayarları', 'wp-seo-master' ),
			__( 'Sitemap', 'wp-seo-master' ),
			'manage_options',
			'wpsm-sitemap',
			array( $this, 'render_sitemap_settings' )
		);
	}

	/**
	 * Dashboard sayfasını render eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_dashboard() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'WP SEO Master Dashboard', 'wp-seo-master' ) . '</h1>';
		echo '<p>' . esc_html__( 'WordPress sitenizin SEO performansını artırmak için tüm ayarları bu panel üzerinden yönetebilirsiniz.', 'wp-seo-master' ) . '</p>';
		echo '<div class="wpsm-dashboard-widgets">';
		// Burada SEO durumu, sitemap durumu, analiz sonuçları gibi widget'lar olabilir
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Genel ayarlar sayfasını render eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_general_settings() {
		include WPSM_PATH . 'includes/Admin/views/settings-general.php';
	}

	/**
	 * Şema ayarları sayfasını render eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_schema_settings() {
		include WPSM_PATH . 'includes/Admin/views/settings-schema.php';
	}

	/**
	 * Sosyal medya ayarları sayfasını render eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_social_settings() {
		include WPSM_PATH . 'includes/Admin/views/settings-social.php';
	}

	/**
	 * Sitemap ayarları sayfasını render eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_sitemap_settings() {
		include WPSM_PATH . 'includes/Admin/views/settings-sitemap.php';
	}

	/**
	 * Admin panel assets'lerini yükle
	 *
	 * @since 1.0.0
	 * @param string $hook Curren admin sayfası
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wpsm-' ) !== false ) {
			wp_enqueue_media();
			
			wp_enqueue_style(
				'wpsm-admin-css',
				WPSM_URL . 'assets/css/admin.css',
				array(),
				WPSM_VERSION
			);

			wp_enqueue_script(
				'wpsm-admin-js',
				WPSM_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				WPSM_VERSION,
				true
			);
		}
	}
}
