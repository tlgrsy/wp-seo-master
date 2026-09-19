<?php
/**
 * Plugin Name: WP SEO Master
 * Plugin URI: https://wp-seo-master.example.com
 * Description: All in One SEO benzeri, tamamen ücretsiz, şema destekli WordPress SEO eklentisi.
 * Version: 1.0.0
 * Author: WP SEO Master Team
 * Author URI: https://wp-seo-master.example.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-seo-master
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Eklenti sabitleri
define( 'WPSM_VERSION', '1.0.0' );
define( 'WPSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSM_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSM_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSM_INCLUDES_PATH', WPSM_PATH . 'includes/' );

/**
 * PSR-4 benzeri autoloader
 *
 * Namespace yapısını dosya yoluna çevirir:
 * WPSM\Admin\Class_Admin_Menu → includes/Admin/class-admin-menu.php
 * WPSM\Frontend\Class_Meta_Tags → includes/Frontend/class-meta-tags.php
 * WPSM\Schema\Class_Schema_Manager → includes/Schema/class-schema-manager.php
 *
 * Kurallar:
 * - WPSM\ ön eki kaldırılır
 * - Alt çizgiler tireye çevrilir (Class_Admin_Menu → class-admin-menu)
 * - Tüm harfler küçük harfe çevrilir
 * - Namespace bölümleri klasör yapısını oluşturur
 */
spl_autoload_register( function ( $class_name ) {
    // Sadece WPSM namespace'ini işle
    $prefix = 'WPSM\\';
    $prefix_length = strlen( $prefix );

    if ( strncmp( $prefix, $class_name, $prefix_length ) !== 0 ) {
        return;
    }

    // Namespace'in geri kalanını al (WPSM\ sonrası)
    $relative_class = substr( $class_name, $prefix_length );

    // Namespace ayırıcılarını dizin ayırıcısına çevir
    $parts = explode( '\\', $relative_class );

    // Son eleman sınıf adı, diğerleri dizin yapısı
    $class_file = array_pop( $parts );

    // Sınıf adını dosya adına çevir:
    // 1. Alt çizgileri tireye çevir
    // 2. Küçük harfe çevir
    $class_file = str_replace( '_', '-', strtolower( $class_file ) );

    // Dosya yolunu oluştur
    $file_path = WPSM_INCLUDES_PATH;

    // Dizin parçalarını ekle (Admin, Frontend, Schema vb.)
    if ( ! empty( $parts ) ) {
        $file_path .= implode( '/', $parts ) . '/';
    }

    // Sınıf dosyasını ekle
    $file_path .= 'class-' . $class_file . '.php';

    // Dosya varsa yükle
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
});

/**
 * Eklenti aktivasyon hook'u
 *
 * Eklenti ilk etkinleştirildiğinde çalışır.
 * Veritabanı tablolarını oluşturur ve varsayılan ayarları kaydeder.
 */
function wpsm_activate() {
    require_once WPSM_INCLUDES_PATH . 'class-installer.php';
    $installer = new WPSM\Class_Installer();
    $installer->activate();
}
register_activation_hook( __FILE__, 'wpsm_activate' );

/**
 * Eklenti deaktivasyon hook'u
 *
 * Eklenti devre dışı bırakıldığında çalışır.
 * Geçici verileri temizler, cron job'ları kaldırır.
 */
function wpsm_deactivate() {
    require_once WPSM_INCLUDES_PATH . 'class-installer.php';
    $installer = new WPSM\Class_Installer();
    $installer->deactivate();
}
register_deactivation_hook( __FILE__, 'wpsm_deactivate' );

/**
 * Eklentiyi başlat
 *
 * plugins_loaded hook'unda çalışır.
 * Tüm WordPress çekirdek dosyaları yüklendikten sonra eklenti başlatılır.
 */
function wpsm_init() {
    // Dil dosyalarını yükle
    load_plugin_textdomain( 'wp-seo-master', false, dirname( WPSM_BASENAME ) . '/languages/' );

    // Ana eklenti sınıfını başlat
    WPSM\Class_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'wpsm_init' );
