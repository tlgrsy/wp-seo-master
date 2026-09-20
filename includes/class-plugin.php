<?php
/**
 * Plugin Sınıfı
 * 
 * Ana plugin singleton sınıfı, tüm bağımlılıkları yükler.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Sınıfı
 * 
 * Singleton pattern ile tek instance sağlar ve tüm modülleri başlatır.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Singleton instance
	 *
	 * @since 1.0.0
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Instance alır
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor (private - singleton)
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Gerekli sınıfları yükler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies() {
		// Core sınıflar zaten autoloader tarafından yüklenecek
		// Burada sadece manuel require gereken durumlar için yer tutucu
	}

	/**
	 * Hook'ları başlatır
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Options sınıfını başlat
		$options = Options::get_instance();
		
		// i18n zaten plugins_loaded'da başlatıldı
		
		// Schema Manager (her zaman aktif)
		if ( class_exists( 'WPSM\Schema\Schema_Manager' ) ) {
			$schema_manager = new \WPSM\Schema\Schema_Manager();
			$schema_manager->init();
		}

		// Frontend Meta Tags (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\Meta_Tags' ) ) {
			$meta_tags = new \WPSM\Frontend\Meta_Tags();
			$meta_tags->init();
		}

		// OpenGraph (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\OpenGraph' ) ) {
			$opengraph = new \WPSM\Frontend\OpenGraph();
			$opengraph->init();
		}

		// Twitter Cards (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\Twitter_Cards' ) ) {
			$twitter_cards = new \WPSM\Frontend\Twitter_Cards();
			$twitter_cards->init();
		}

		// Canonical (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\Canonical' ) ) {
			$canonical = new \WPSM\Frontend\Canonical();
			$canonical->init();
		}

		// Robots (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\Robots' ) ) {
			$robots = new \WPSM\Frontend\Robots();
			$robots->init();
		}

		// Breadcrumbs (her zaman aktif)
		if ( class_exists( 'WPSM\Frontend\Breadcrumbs' ) ) {
			$breadcrumbs = new \WPSM\Frontend\Breadcrumbs();
			$breadcrumbs->init();
		}

		// Sitemap (her zaman aktif)
		if ( class_exists( 'WPSM\Sitemap\Sitemap_Generator' ) ) {
			$sitemap = new \WPSM\Sitemap\Sitemap_Generator();
			$sitemap->init();
		}

		// Admin sınıfları (sadece admin panelinde)
		if ( is_admin() ) {
			if ( class_exists( 'WPSM\Admin\Admin_Menu' ) ) {
				$admin_menu = new \WPSM\Admin\Admin_Menu();
				$admin_menu->init();
			}

			if ( class_exists( 'WPSM\Admin\Settings' ) ) {
				$settings = new \WPSM\Admin\Settings();
				$settings->init();
			}

			if ( class_exists( 'WPSM\Admin\Metabox' ) ) {
				$metabox = new \WPSM\Admin\Metabox();
				$metabox->init();
			}
		}

		// Analyzer (admin + ajax)
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			if ( class_exists( 'WPSM\Analyzer\Content_Analyzer' ) ) {
				$analyzer = new \WPSM\Analyzer\Content_Analyzer();
				$analyzer->init();
			}
		}
	}

	/**
	 * Plugin'i başlatır (public method)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Zaten constructor'da başlatıldı
		do_action( 'wpsm_loaded' );
	}
}
