<?php
/**
 * Kurulum Sınıfı
 *
 * Eklenti aktivasyon ve deaktivasyon işlemlerini yönetir.
 * Varsayılan ayarları kaydeder, veritabanı versiyonunu kontrol eder.
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
 * Class Class_Installer
 *
 * Eklenti kurulum, aktivasyon ve deaktivasyon işlemleri.
 * Migration altyapısı ile gelecekteki veritabanı değişikliklerini destekler.
 */
class Class_Installer {

    /**
     * Veritabanı versiyonu
     *
     * Her migration'da artırılır.
     *
     * @var string
     */
    const DB_VERSION = '1.0.0';

    /**
     * Veritabanı versiyonu option adı
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'wpsm_db_version';

    /**
     * Ayarlar option adı
     *
     * @var string
     */
    const SETTINGS_OPTION = 'wpsm_settings';

    /**
     * Varsayılan ayarlar
     *
     * Eklenti ilk etkinleştirildiğinde bu değerler kaydedilir.
     *
     * @var array
     */
    private static $defaults = array(
        // Genel Ayarlar
        'title_separator'     => '|',
        'enable_sitemap'      => true,
        'enable_schema'       => true,
        'enable_opengraph'    => true,
        'enable_twitter'      => true,
        'enable_breadcrumbs'  => true,

        // Schema Varsayılanları
        'default_schema_type' => 'Article',

        // Sosyal Medya
        'twitter_site'        => '',
        'twitter_creator'     => '',
        'facebook_app_id'     => '',
        'facebook_admins'     => '',

        // Webmaster Araçları Doğrulama Kodları
        'google_verification' => '',
        'bing_verification'   => '',
        'yandex_verification' => '',
        'pinterest_verification' => '',

        // Sitemap Ayarları
        'sitemap_post_types'  => array( 'post', 'page' ),
        'sitemap_taxonomies'  => array( 'category' ),
        'sitemap_posts_per_page' => 1000,

        // Breadcrumb Ayarları
        'breadcrumb_home_text'    => 'Ana Sayfa',
        'breadcrumb_show_home'    => true,
        'breadcrumb_separator'    => '»',

        // Robots
        'global_noindex'      => array(),
        'rss_before_content'  => '',
        'rss_after_content'   => '',
    );

    /**
     * Eklenti aktivasyonu
     *
     * İlk kurulumda veya güncellemede çalışır.
     * - Varsayılan ayarları kaydeder
     * - Veritabanı versiyonunu ayarlar
     * - Migration'ları çalıştırır
     * - Rewrite kurallarını flush eder
     */
    public function activate() {
        // Yetki kontrolü
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // Varsayılan ayarları kaydet
        $this->set_default_options();

        // Veritabanı versiyonunu kontrol et ve migration'ları çalıştır
        $this->check_db_version();

        // Rewrite kurallarını yenile
        $this->flush_rewrite_rules();

        // Aktivasyon transient'i ayarla (yönlendirme için)
        set_transient( 'wpsm_activation_redirect', true, 30 );

        /**
         * Aktivasyon sonrası action
         *
         * @param bool $is_new_install Yeni kurulum mu?
         */
        do_action( 'wpsm_activated', ! get_option( self::DB_VERSION_OPTION ) );
    }

    /**
     * Eklenti deaktivasyonu
     *
     * Eklenti devre dışı bırakıldığında çalışır.
     * Veri silmez - sadece geçici işlemleri temizler.
     *
     * NOT: Veri silme işlemi uninstall.php dosyasında yapılır.
     */
    public function deactivate() {
        // Yetki kontrolü
        if ( ! current_user_can( 'deactivate_plugins' ) ) {
            return;
        }

        // Rewrite kurallarını temizle
        flush_rewrite_rules();

        // Zamanlanmış görevleri temizle (cron jobs)
        $this->clear_scheduled_events();

        // Geçici verileri temizle
        $this->clear_transients();

        /**
         * Deaktivasyon sonrası action
         */
        do_action( 'wpsm_deactivated' );
    }

    /**
     * Varsayılan ayarları kaydet
     *
     * Mevcut ayarları koruyarak sadece eksik olanları ekler.
     * Bu sayede güncelleme sırasında kullanıcı ayarları kaybolmaz.
     */
    private function set_default_options() {
        $existing = get_option( self::SETTINGS_OPTION, array() );

        // Mevcut ayarları koru, eksik olanları ekle
        $settings = wp_parse_args( $existing, self::$defaults );

        /**
         * Varsayılan ayarları filtrele
         *
         * @param array $settings Ayarlar dizisi
         */
        $settings = apply_filters( 'wpsm_default_settings', $settings );

        update_option( self::SETTINGS_OPTION, $settings );
    }

    /**
     * Veritabanı versiyonunu kontrol et
     *
     * Migration altyapısı:
     * - Kayıtlı DB versiyonunu kontrol eder
     * - Gerekli migration'ları sırayla çalıştırır
     * - Versiyonu günceller
     */
    private function check_db_version() {
        $current_db_version = get_option( self::DB_VERSION_OPTION, '0' );

        // Versiyon aynıysa migration gerekmez
        if ( version_compare( $current_db_version, self::DB_VERSION, '>=' ) ) {
            return;
        }

        // Migration'ları çalıştır
        $this->run_migrations( $current_db_version );

        // Versiyonu güncelle
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Migration'ları çalıştır
     *
     * Belirli bir versiyondan sonraki tüm migration'ları çalıştırır.
     * Her migration bir kez çalışır, tekrar çalışmaz.
     *
     * @param string $from_version Mevcut versiyon
     */
    private function run_migrations( $from_version ) {
        // Migration listesi - her yeni versiyonda eklenir
        $migrations = array(
            '1.0.0' => 'migrate_1_0_0',
        );

        /**
         * Migration listesini filtrele
         *
         * @param array  $migrations Migration listesi (version => method)
         * @param string $from_version Mevcut versiyon
         */
        $migrations = apply_filters( 'wpsm_migrations', $migrations, $from_version );

        foreach ( $migrations as $version => $method ) {
            if ( version_compare( $from_version, $version, '<' ) ) {
                if ( method_exists( $this, $method ) ) {
                    $this->$method();
                }
            }
        }
    }

    /**
     * İlk kurulum migration'ı (1.0.0)
     *
     * İlk sürüm için özel bir migration gerekmez,
     * ancak altyapıyı göstermek için bırakılmıştır.
     */
    private function migrate_1_0_0() {
        // İlk sürüm - varsayılan ayarlar zaten set_default_options() ile kaydedildi
        // Gelecekteki migration'lar için örnek:
        //
        // Örnek: Yeni bir ayar ekleme
        // $settings = get_option( self::SETTINGS_OPTION, array() );
        // $settings['new_setting'] = 'default_value';
        // update_option( self::SETTINGS_OPTION, $settings );
        //
        // Örnek: Meta key değişikliği
        // global $wpdb;
        // $wpdb->query(
        //     "UPDATE {$wpdb->postmeta} SET meta_key = '_new_key' WHERE meta_key = '_old_key'"
        // );
    }

    /**
     * Rewrite kurallarını yenile
     *
     * Sitemap ve diğer custom rewrite kuralları için gerekli.
     */
    private function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    /**
     * Zamanlanmış görevleri temizle
     *
     * Eklenti tarafından oluşturulan cron job'ları kaldırır.
     */
    private function clear_scheduled_events() {
        // Sitemap yeniden oluşturma cron'u
        wp_clear_scheduled_hook( 'wpsm_sitemap_regeneration' );

        // İçerik analizi cron'u
        wp_clear_scheduled_hook( 'wpsm_content_analysis' );

        // Geçici dosya temizleme cron'u
        wp_clear_scheduled_hook( 'wpsm_cleanup_temp_files' );

        /**
         * Cron temizleme action'ı
         *
         * Eklenti geliştiricileri kendi cron'larını burada temizleyebilir.
         */
        do_action( 'wpsm_clear_scheduled_events' );
    }

    /**
     * Geçici verileri temizle
     *
     * Eklenti tarafından oluşturulan transient'leri kaldırır.
     */
    private function clear_transients() {
        delete_transient( 'wpsm_activation_redirect' );
        delete_transient( 'wpsm_sitemap_cache' );
        delete_transient( 'wpsm_schema_cache' );

        /**
         * Transient temizleme action'ı
         */
        do_action( 'wpsm_clear_transients' );
    }

    /**
     * Varsayılan ayarları döndür
     *
     * @return array Varsayılan ayarlar
     */
    public static function get_defaults() {
        /**
         * Varsayılan ayarları filtrele
         *
         * @param array $defaults Varsayılan ayarlar
         */
        return apply_filters( 'wpsm_installation_defaults', self::$defaults );
    }

    /**
     * Mevcut DB versiyonunu döndür
     *
     * @return string
     */
    public static function get_db_version() {
        return get_option( self::DB_VERSION_OPTION, '0' );
    }

    /**
     * Eklentinin yeni mi kurulduğunu kontrol et
     *
     * @return bool
     */
    public static function is_fresh_install() {
        return ! get_option( self::DB_VERSION_OPTION );
    }
}
