<?php
/**
 * Plugin Name: WP SEO Master
 * Plugin URI: https://github.com/tlgrsy/wp-seo-master
 * Description: Yoast / All in One SEO'ya benzer, ancak Schema.org dahil TÜM özellikleri ücretsiz olan WordPress SEO eklentisi.
 * Version: 1.0.0
 * Author: WP SEO Master Team
 * Author URI: https://github.com/tlgrsy/wp-seo-master
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-seo-master
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WPSM
 */

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sabitler
define( 'WPSM_VERSION', '1.0.0' );
define( 'WPSM_FILE', __FILE__ );
define( 'WPSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSM_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSM_BASENAME', plugin_basename( __FILE__ ) );

// Çakışma kontrolü
if ( defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
	add_action( 'admin_notices', 'wpsm_conflict_notice' );
	
	/**
	 * Diğer SEO eklentileri ile çakışma uyarısı
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function wpsm_conflict_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'WP SEO Master Çakışma Uyarısı', 'wp-seo-master' ); ?></strong><br>
				<?php esc_html_e( 'Sitenizde başka bir SEO eklentisi (Yoast SEO, All in One SEO, Rank Math veya SEOPress) aktif görünüyor. Lütfen diğer SEO eklentilerini devre dışı bırakın.', 'wp-seo-master' ); ?>
			</p>
		</div>
		<?php
	}
	
	// Frontend output'u devre dışı bırak
	add_filter( 'wpsm_enable_frontend_output', '__return_false' );
	
	return; // Plugin yüklemesini durdur
}

// Autoloader
require_once WPSM_PATH . 'includes/class-autoloader.php';
WPSM\Autoloader::register();

// Plugin aktivasyon/deaktivasyon hook'ları
register_activation_hook( __FILE__, array( 'WPSM\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPSM\Installer', 'deactivate' ) );

// Plugin başlatma
add_action( 'plugins_loaded', function() {
	// i18n yükle
	$i18n = new \WPSM\I18n();
	$i18n->init();
	
	// Ana plugin sınıfını başlat
	$plugin = \WPSM\Plugin::get_instance();
	$plugin->init();
}, 0 ); // Öncelik 0 - diğer eklentilerden önce yüklenmesi için
