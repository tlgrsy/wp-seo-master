<?php
/**
 * Plugin Name: WP SEO Master
 * Plugin URI: https://example.com/wp-seo-master
 * Description: Yoast / All in One SEO'ya benzer, Schema.org dahil TÜM özellikleri ücretsiz sunan kapsamlı SEO eklentisi.
 * Version: 1.0.0
 * Author: WP SEO Master
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-seo-master
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WPSM
 */

if (! defined( 'ABSPATH' ) ) {
	exit;
}

// Sabitler.
define( 'WPSM_VERSION', '1.0.0' );
define( 'WPSM_FILE', __FILE__ );
define( 'WPSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSM_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSM_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Varsayılan ayarları döndürür.
 *
 * @return array
 */
function wpsm_get_default_settings() {
	return array(
		'title_separator' => '|',
		'enable_sitemap' => true,
		'enable_schema' => true,
		'enable_opengraph' => true,
		'enable_twitter' => true,
		'enable_breadcrumbs' => true,
		'enable_analyzer' => true,
		'default_schema_type' => 'Article',
		'twitter_site' => '',
		'facebook_app_id' => '',
		'default_og_image' => '',
		'gsc_verification' => '',
		'bing_verification' => '',
		'robots_txt_custom' => '',
		'sitemap_cache_hours' => 12,
		'db_version' => '1.0.0',
	);
}

// Autoloader yükle.
require_once WPSM_PATH. 'includes/class-autoloader.php';
\WPSM\Class_Autoloader::register();

/**
 * Eklentiyi başlatır.
 *
 * @return void
 */
function wpsm_init_plugin() {
	if ( class_exists( '\WPSM\Class_Plugin' ) ) {
		\WPSM\Class_Plugin::get_instance();
	}
}
add_action( 'plugins_loaded', 'wpsm_init_plugin' );

// Aktivasyon / Deaktivasyon.
register_activation_hook( __FILE__, array( '\WPSM\Class_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\WPSM\Class_Installer', 'deactivate' ) );
