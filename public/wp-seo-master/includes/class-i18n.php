<?php
/**
 * Uluslararasılaştırma (i18n) Sınıfı
 *
 * Eklenti dil dosyalarını yükler.
 * Text domain: wp-seo-master
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
 * Class Class_I18n
 *
 * Eklenti çeviri dosyalarını yükler.
 * WordPress'in standart i18n mekanizmasını kullanır.
 *
 * Kullanım:
 * - __('Metin', 'wp-seo-master')
 * - _e('Metin', 'wp-seo-master')
 * - esc_html__('Metin', 'wp-seo-master')
 * - esc_attr__('Metin', 'wp-seo-master')
 * - _n('tekil', 'çoğul', $count, 'wp-seo-master')
 * - _x('Metin', 'context', 'wp-seo-master')
 */
class Class_I18n {

    /**
     * Text domain
     *
     * @var string
     */
    const TEXT_DOMAIN = 'wp-seo-master';

    /**
     * Dil dosyaları dizini
     *
     * @var string
     */
    const LANGUAGES_DIR = 'languages';

    /**
     * Text domain yüklendi mi
     *
     * @var bool
     */
    private static $loaded = false;

    /**
     * Plugin text domain'ini yükle
     *
     * WordPress'in init veya plugins_loaded hook'unda çağrılır.
     * Dil dosyaları /languages/ dizininde aranır.
     *
     * Dosya yapısı:
     * - wp-seo-master-tr_TR.po
     * - wp-seo-master-tr_TR.mo
     * - wp-seo-master-en_US.po
     * - wp-seo-master-en_US.mo
     *
     * @return bool Başarılıysa true
     */
    public function load_plugin_textdomain() {
        // Zaten yüklendiyse tekrar yükleme
        if ( self::$loaded ) {
            return true;
        }

        $locale = determine_locale();

        /**
         * Locale filtresi
         *
         * Eklenti için farklı locale kullanılmak istenirse filtre ile değiştirilebilir.
         *
         * @param string $locale WordPress locale
         */
        $locale = apply_filters( 'plugin_locale', $locale, self::TEXT_DOMAIN );

        // /wp-content/languages/plugins/ dizininden yükle (öncelikli)
        $mofile = WP_LANG_DIR . '/plugins/' . self::TEXT_DOMAIN . '-' . $locale . '.mo';
        
        if ( load_textdomain( self::TEXT_DOMAIN, $mofile ) ) {
            self::$loaded = true;
            return true;
        }

        // Eklenti /languages/ dizininden yükle
        $result = load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname( WPSM_BASENAME ) . '/' . self::LANGUAGES_DIR
        );

        if ( $result ) {
            self::$loaded = true;
        }

        return $result;
    }

    /**
     * Text domain'i döndür
     *
     * @return string
     */
    public function get_text_domain() {
        return self::TEXT_DOMAIN;
    }

    /**
     * Dil dosyaları dizinini döndür
     *
     * @return string Tam yol
     */
    public function get_languages_dir() {
        return WPSM_PATH . self::LANGUAGES_DIR;
    }

    /**
     * Mevcut locale'i döndür
     *
     * @return string
     */
    public function get_locale() {
        return determine_locale();
    }

    /**
     * Text domain'in yüklü olup olmadığını kontrol et
     *
     * @return bool
     */
    public function is_loaded() {
        return self::$loaded;
    }

    /**
     * Çeviri durumunu döndür
     *
     * Eklentinin çevrildiği dillerin listesini verir.
     *
     * @return array Dil kodları
     */
    public function get_translations() {
        $translations = array(
            'tr_TR' => array(
                'name'     => 'Türkçe',
                'native'   => 'Türkçe',
                'status'   => 'complete',
                'version'  => '1.0.0',
                'updated'  => '2024-01-01',
            ),
            'en_US' => array(
                'name'     => 'English',
                'native'   => 'English (US)',
                'status'   => 'complete',
                'version'  => '1.0.0',
                'updated'  => '2024-01-01',
            ),
        );

        /**
         * Çeviri listesini filtrele
         *
         * @param array $translations Çeviri bilgileri
         */
        return apply_filters( 'wpsm_translations', $translations );
    }

    /**
     * Belirli bir dilin mevcut olup olmadığını kontrol et
     *
     * @param string $locale Dil kodu (örn: tr_TR)
     * @return bool
     */
    public function translation_exists( $locale ) {
        $mo_file = $this->get_languages_dir() . '/' . self::TEXT_DOMAIN . '-' . $locale . '.mo';
        return file_exists( $mo_file );
    }
}
