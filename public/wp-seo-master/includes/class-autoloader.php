<?php
/**
 * Autoloader Sınıfı
 *
 * PSR-4 benzeri otomatik sınıf yükleme mekanizması.
 * Namespace yapısını dosya yolu yapısına çevirir.
 *
 * Dönüşüm Kuralları:
 * - WPSM\ → includes/
 * - WPSM\Admin\ → includes/Admin/
 * - WPSM\Frontend\ → includes/Frontend/
 * - WPSM\Schema\ → includes/Schema/
 * - Class_Name → class-name.php (alt çizgi → tire, küçük harf)
 *
 * Örnekler:
 * - WPSM\Class_Plugin → includes/class-plugin.php
 * - WPSM\Admin\Class_Settings → includes/Admin/class-settings.php
 * - WPSM\Frontend\Class_Meta_Tags → includes/Frontend/class-meta-tags.php
 * - WPSM\Schema\Class_Schema_Manager → includes/Schema/class-schema-manager.php
 * - WPSM\Sitemap\Class_Sitemap_Generator → includes/Sitemap/class-sitemap-generator.php
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
 * Class Class_Autoloader
 *
 * Namespace tabanlı otomatik sınıf yükleyici.
 * WordPress Coding Standards'a uygun dosya isimlendirmesi kullanır.
 */
class Class_Autoloader {

    /**
     * Namespace ön eki
     *
     * @var string
     */
    private $prefix = 'WPSM\\';

    /**
     * Kök dizin yolu
     *
     * @var string
     */
    private $base_dir;

    /**
     * Kurucu metod
     *
     * @param string $base_dir Eklenti includes dizini yolu
     */
    public function __construct( $base_dir = '' ) {
        $this->base_dir = ! empty( $base_dir ) ? $base_dir : WPSM_INCLUDES_PATH;
    }

    /**
     * Autoloader'ı register et
     *
     * spl_autoload_register ile bu sınıfın autoload metodunu kaydeder.
     * Bu metod statik olarak da çağrılabilir.
     *
     * @return void
     */
    public function register() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }

    /**
     * Otomatik sınıf yükleme metodu
     *
     * Verilen sınıf adını dosya yoluna çevirir ve dosyayı yükler.
     *
     * Dönüşüm adımları:
     * 1. WPSM\ ön eki kontrol edilir
     * 2. Namespace parçaları dizin yapısına çevrilir
     * 3. Sınıf adı dosya adına çevrilir:
     *    - Class_Admin_Menu → class-admin-menu.php
     *    - Alt çizgiler tireye çevrilir
     *    - Tüm harfler küçük harfe yapılır
     * 4. Dosya varlığı kontrol edilir ve yüklenir
     *
     * @param string $class_name Tam sınıf adı (namespace dahil)
     * @return bool Başarılıysa true, dosya bulunamazsa false
     */
    public function autoload( $class_name ) {
        // Sadece WPSM namespace'ini işle
        if ( ! $this->is_our_namespace( $class_name ) ) {
            return false;
        }

        // Dosya yolunu oluştur
        $file_path = $this->get_file_path( $class_name );

        // Dosya varsa yükle
        if ( $this->file_exists( $file_path ) ) {
            require_once $file_path;
            return true;
        }

        return false;
    }

    /**
     * Verilen sınıf adının WPSM namespace'ine ait olup olmadığını kontrol et
     *
     * @param string $class_name Tam sınıf adı
     * @return bool
     */
    private function is_our_namespace( $class_name ) {
        return strncmp( $this->prefix, $class_name, strlen( $this->prefix ) ) === 0;
    }

    /**
     * Sınıf adından dosya yolunu oluştur
     *
     * @param string $class_name Tam sınıf adı
     * @return string Dosya yolu
     */
    public function get_file_path( $class_name ) {
        // Namespace'in geri kalanını al
        $relative_class = substr( $class_name, strlen( $this->prefix ) );

        // Namespace ayırıcılarını dizin ayırıcısına çevir
        $parts = explode( '\\', $relative_class );

        // Son eleman sınıf adı
        $class_file = array_pop( $parts );

        // Sınıf adını dosya adına çevir
        $class_file = $this->class_name_to_file( $class_file );

        // Temel yolu oluştur
        $file_path = trailingslashit( $this->base_dir );

        // Alt dizinleri ekle
        if ( ! empty( $parts ) ) {
            $file_path .= implode( '/', $parts ) . '/';
        }

        // Dosya adını ekle
        $file_path .= $class_file;

        return $file_path;
    }

    /**
     * Sınıf adını dosya adına çevir
     *
     * WordPress dosya isimlendirme standartlarına uygun dönüşüm yapar:
     * - Class_Admin_Menu → class-admin-menu.php
     * - Class_Meta_Tags → class-meta-tags.php
     * - Class_Schema_Manager → class-schema-manager.php
     *
     * @param string $class_name Sınıf adı (namespace olmadan)
     * @return string Dosya adı
     */
    private function class_name_to_file( $class_name ) {
        // Alt çizgileri tireye çevir
        $file_name = str_replace( '_', '-', $class_name );

        // Küçük harfe çevir
        $file_name = strtolower( $file_name );

        // .php uzantısı ekle
        return $file_name . '.php';
    }

    /**
     * Dosyanın varlığını kontrol et
     *
     * @param string $file_path Dosya yolu
     * @return bool
     */
    private function file_exists( $file_path ) {
        return file_exists( $file_path );
    }

    /**
     * Kök dizin yolunu döndür
     *
     * @return string
     */
    public function get_base_dir() {
        return $this->base_dir;
    }

    /**
     * Kök dizin yolunu ayarla
     *
     * @param string $base_dir Dizin yolu
     * @return void
     */
    public function set_base_dir( $base_dir ) {
        $this->base_dir = trailingslashit( $base_dir );
    }

    /**
     * Namespace ön ekini döndür
     *
     * @return string
     */
    public function get_prefix() {
        return $this->prefix;
    }

    /**
     * Debug amaçlı - sınıf adının hangi dosyaya eşleneceğini göster
     *
     * @param string $class_name Tam sınıf adı
     * @return string Eşleşen dosya yolu
     */
    public function debug_resolve( $class_name ) {
        return $this->get_file_path( $class_name );
    }
}
