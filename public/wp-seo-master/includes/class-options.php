<?php
/**
 * Ayar Yönetimi Sınıfı
 *
 * Eklenti ayarlarını yönetir.
 * get_option('wpsm_settings') üzerinden çalışır.
 * Static cache ile aynı request'te tekrar sorgu atmaz.
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
 * Class Class_Options
 *
 * Eklenti ayar yönetimi.
 * Tek bir option (wpsm_settings) altında tüm ayarları saklar.
 * Cache mekanizması ile performans optimize edilmiştir.
 */
class Class_Options {

    /**
     * Option adı
     *
     * @var string
     */
    const OPTION_NAME = 'wpsm_settings';

    /**
     * Statik cache
     *
     * Aynı request'te birden fazla get() çağrısı yapıldığında
     * veritabanına tekrar sorgu göndermez.
     *
     * @var array|null
     */
    private static $cache = null;

    /**
     * Sanitize callback'leri
     *
     * Her ayar tipi için uygun sanitize fonksiyonu.
     *
     * @var array
     */
    private static $sanitize_callbacks = array(
        // Genel Ayarlar
        'title_separator'     => 'sanitize_text_field',
        'enable_sitemap'      => 'boolean',
        'enable_schema'       => 'boolean',
        'enable_opengraph'    => 'boolean',
        'enable_twitter'      => 'boolean',
        'enable_breadcrumbs'  => 'boolean',

        // Schema
        'default_schema_type' => 'sanitize_text_field',

        // Sosyal Medya
        'twitter_site'        => 'sanitize_text_field',
        'twitter_creator'     => 'sanitize_text_field',
        'facebook_app_id'     => 'sanitize_text_field',
        'facebook_admins'     => 'sanitize_text_field',

        // Webmaster Doğrulama
        'google_verification' => 'sanitize_text_field',
        'bing_verification'   => 'sanitize_text_field',
        'yandex_verification' => 'sanitize_text_field',
        'pinterest_verification' => 'sanitize_text_field',

        // Sitemap
        'sitemap_post_types'  => 'array_sanitize',
        'sitemap_taxonomies'  => 'array_sanitize',
        'sitemap_posts_per_page' => 'intval',

        // Breadcrumb
        'breadcrumb_home_text' => 'sanitize_text_field',
        'breadcrumb_show_home' => 'boolean',
        'breadcrumb_separator' => 'sanitize_text_field',

        // Robots
        'global_noindex'      => 'array_sanitize',
        'rss_before_content'  => 'wp_kses_post',
        'rss_after_content'   => 'wp_kses_post',
    );

    /**
     * Kurucu metod
     *
     * Cache'i başlatır.
     */
    public function __construct() {
        $this->load_cache();
    }

    /**
     * Cache'i yükle
     *
     * Veritabanından ayarları okur ve cache'e alır.
     */
    private function load_cache() {
        if ( null === self::$cache ) {
            self::$cache = get_option( self::OPTION_NAME, array() );

            // Cache boşsa varsayılanları yükle
            if ( empty( self::$cache ) ) {
                self::$cache = Class_Installer::get_defaults();
            }
        }
    }

    /**
     * Belirli bir ayar değerini döndür
     *
     * @param string $key     Ayar anahtarı
     * @param mixed  $default Varsayılan değer (ayar yoksa döner)
     * @return mixed Ayar değeri
     */
    public function get( $key, $default = null ) {
        $this->load_cache();

        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

        // Varsayılan değerlerden kontrol et
        $defaults = Class_Installer::get_defaults();
        if ( isset( $defaults[ $key ] ) ) {
            return $defaults[ $key ];
        }

        return $default;
    }

    /**
     * Belirli bir ayarı kaydet
     *
     * @param string $key   Ayar anahtarı
     * @param mixed  $value Ayar değeri
     * @return bool Başarılıysa true
     */
    public function set( $key, $value ) {
        $this->load_cache();

        // Sanitize et
        $value = $this->sanitize_value( $key, $value );

        // Cache'i güncelle
        self::$cache[ $key ] = $value;

        // Veritabanına kaydet
        return update_option( self::OPTION_NAME, self::$cache );
    }

    /**
     * Tüm ayarları döndür
     *
     * @return array Tüm ayarlar
     */
    public function all() {
        $this->load_cache();

        // Varsayılanlarla birleştir (eksik olanları ekle)
        $defaults = Class_Installer::get_defaults();
        return wp_parse_args( self::$cache, $defaults );
    }

    /**
     * Birden fazla ayarı güncelle
     *
     * @param array $data Güncellenecek ayarlar (key => value)
     * @return bool Başarılıysa true
     */
    public function update( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        $this->load_cache();

        // Her değeri sanitize et
        foreach ( $data as $key => $value ) {
            self::$cache[ $key ] = $this->sanitize_value( $key, $value );
        }

        /**
         * Ayarlar güncellenmeden önce action
         *
         * @param array $data    Güncellenen veriler
         * @param array $cache   Mevcut cache
         */
        do_action( 'wpsm_before_settings_update', $data, self::$cache );

        // Veritabanına kaydet
        $result = update_option( self::OPTION_NAME, self::$cache );

        /**
         * Ayarlar güncellendikten sonra action
         *
         * @param array $data   Güncellenen veriler
         * @param bool  $result Güncelleme sonucu
         */
        do_action( 'wpsm_after_settings_update', $data, $result );

        return $result;
    }

    /**
     * Belirli bir ayarı sil
     *
     * @param string $key Silinecek ayar anahtarı
     * @return bool Başarılıysa true
     */
    public function delete( $key ) {
        $this->load_cache();

        if ( isset( self::$cache[ $key ] ) ) {
            unset( self::$cache[ $key ] );
            return update_option( self::OPTION_NAME, self::$cache );
        }

        return false;
    }

    /**
     * Değeri sanitize et
     *
     * Ayar tipine göre uygun sanitize fonksiyonunu uygular.
     *
     * @param string $key   Ayar anahtarı
     * @param mixed  $value Ayar değeri
     * @return mixed Sanitize edilmiş değer
     */
    private function sanitize_value( $key, $value ) {
        // Callback var mı kontrol et
        if ( ! isset( self::$sanitize_callbacks[ $key ] ) ) {
            // Callback yoksa text field olarak sanitize et
            return sanitize_text_field( $value );
        }

        $callback = self::$sanitize_callbacks[ $key ];

        // Özel callback'ler
        switch ( $callback ) {
            case 'boolean':
                return (bool) $value;

            case 'intval':
                return intval( $value );

            case 'array_sanitize':
                if ( ! is_array( $value ) ) {
                    return array();
                }
                return array_map( 'sanitize_text_field', $value );

            case 'wp_kses_post':
                return wp_kses_post( $value );

            default:
                // WordPress sanitize fonksiyonu
                if ( is_callable( $callback ) ) {
                    return call_user_func( $callback, $value );
                }
                return sanitize_text_field( $value );
        }
    }

    /**
     * Cache'i temizle
     *
     * Ayarlar dışarıdan değiştirildiğinde cache'i yenilemek için kullanılır.
     */
    public function clear_cache() {
        self::$cache = null;
    }

    /**
     * Cache'i yeniden yükle
     *
     * @return array Yeniden yüklenen ayarlar
     */
    public function refresh() {
        $this->clear_cache();
        $this->load_cache();
        return self::$cache;
    }

    /**
     * Ayarın var olup olmadığını kontrol et
     *
     * @param string $key Ayar anahtarı
     * @return bool
     */
    public function has( $key ) {
        $this->load_cache();
        return isset( self::$cache[ $key ] );
    }

    /**
     * Tüm ayarları sıfırla (varsayılanlara döndür)
     *
     * @return bool Başarılıysa true
     */
    public function reset() {
        $defaults = Class_Installer::get_defaults();
        self::$cache = $defaults;

        /**
         * Ayarlar sıfırlanmadan önce action
         */
        do_action( 'wpsm_before_settings_reset' );

        $result = update_option( self::OPTION_NAME, $defaults );

        /**
         * Ayarlar sıfırlandıktan sonra action
         *
         * @param bool $result Sıfırlama sonucu
         */
        do_action( 'wpsm_after_settings_reset', $result );

        return $result;
    }

    /**
     * Ayarları dışa aktar (JSON)
     *
     * @return string JSON formatında ayarlar
     */
    public function export() {
        return wp_json_encode( $this->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /**
     * Ayarları içe aktar (JSON)
     *
     * @param string $json JSON formatında ayarlar
     * @return bool|array Başarılıysa ayarlar dizisi, başarısızsa false
     */
    public function import( $json ) {
        $data = json_decode( $json, true );

        if ( ! is_array( $data ) ) {
            return false;
        }

        // Sadece bilinen ayarları kabul et
        $defaults = Class_Installer::get_defaults();
        $sanitized = array();

        foreach ( $data as $key => $value ) {
            if ( array_key_exists( $key, $defaults ) ) {
                $sanitized[ $key ] = $this->sanitize_value( $key, $value );
            }
        }

        if ( empty( $sanitized ) ) {
            return false;
        }

        $this->update( $sanitized );

        return $sanitized;
    }

    /**
     * Option adını döndür
     *
     * @return string
     */
    public function get_option_name() {
        return self::OPTION_NAME;
    }
}
