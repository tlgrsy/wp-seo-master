import { useState } from 'react'

const files = [
  {
    id: 'installer',
    name: 'class-installer.php',
    path: 'includes/class-installer.php',
    description: 'Kurulum sınıfı - Aktivasyon, deaktivasyon, migration altyapısı',
    language: 'php',
    code: `<?php
/**
 * Kurulum Sınıfı
 *
 * Eklenti aktivasyon ve deaktivasyon işlemlerini yönetir.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Installer {

    const DB_VERSION        = '1.0.0';
    const DB_VERSION_OPTION = 'wpsm_db_version';
    const SETTINGS_OPTION   = 'wpsm_settings';

    /**
     * Varsayılan ayarlar
     * İlk kurulumda bu değerler kaydedilir.
     */
    private static $defaults = array(
        // Genel Ayarlar
        'title_separator'        => '|',
        'enable_sitemap'         => true,
        'enable_schema'          => true,
        'enable_opengraph'       => true,
        'enable_twitter'         => true,
        'enable_breadcrumbs'     => true,

        // Schema Varsayılanları
        'default_schema_type'    => 'Article',

        // Sosyal Medya
        'twitter_site'           => '',
        'twitter_creator'        => '',
        'facebook_app_id'        => '',
        'facebook_admins'        => '',

        // Webmaster Doğrulama
        'google_verification'    => '',
        'bing_verification'      => '',
        'yandex_verification'    => '',
        'pinterest_verification' => '',

        // Sitemap Ayarları
        'sitemap_post_types'     => array( 'post', 'page' ),
        'sitemap_taxonomies'     => array( 'category' ),
        'sitemap_posts_per_page' => 1000,

        // Breadcrumb Ayarları
        'breadcrumb_home_text'   => 'Ana Sayfa',
        'breadcrumb_show_home'   => true,
        'breadcrumb_separator'   => '»',

        // Robots
        'global_noindex'         => array(),
        'rss_before_content'     => '',
        'rss_after_content'      => '',
    );

    /**
     * Eklenti aktivasyonu
     *
     * - Varsayılan ayarları kaydeder
     * - DB versiyonunu ayarlar
     * - Migration'ları çalıştırır
     * - Rewrite flush yapar
     */
    public function activate() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // Varsayılan ayarları kaydet
        $this->set_default_options();

        // DB versiyonunu kontrol et
        $this->check_db_version();

        // Rewrite kurallarını yenile
        flush_rewrite_rules();

        // Aktivasyon transient'i (yönlendirme için)
        set_transient( 'wpsm_activation_redirect', true, 30 );

        do_action( 'wpsm_activated', ! get_option( self::DB_VERSION_OPTION ) );
    }

    /**
     * Eklenti deaktivasyonu
     *
     * Veri silmez - sadece geçici işlemleri temizler.
     * Veri silme uninstall.php'de yapılır.
     */
    public function deactivate() {
        if ( ! current_user_can( 'deactivate_plugins' ) ) {
            return;
        }

        // Rewrite kurallarını temizle
        flush_rewrite_rules();

        // Cron job'ları temizle
        wp_clear_scheduled_hook( 'wpsm_sitemap_regeneration' );
        wp_clear_scheduled_hook( 'wpsm_content_analysis' );
        wp_clear_scheduled_hook( 'wpsm_cleanup_temp_files' );

        // Transient'leri temizle
        delete_transient( 'wpsm_activation_redirect' );
        delete_transient( 'wpsm_sitemap_cache' );

        do_action( 'wpsm_deactivated' );
    }

    /**
     * Varsayılan ayarları kaydet
     *
     * Mevcut ayarları koruyarak sadece eksik olanları ekler.
     * Güncelleme sırasında kullanıcı ayarları kaybolmaz.
     */
    private function set_default_options() {
        $existing = get_option( self::SETTINGS_OPTION, array() );
        $settings = wp_parse_args( $existing, self::$defaults );
        $settings = apply_filters( 'wpsm_default_settings', $settings );
        update_option( self::SETTINGS_OPTION, $settings );
    }

    /**
     * DB versiyonunu kontrol et
     *
     * Migration altyapısı:
     * - Kayıtlı versiyonu kontrol eder
     * - Gerekli migration'ları sırayla çalıştırır
     * - Versiyonu günceller
     */
    private function check_db_version() {
        $current_db_version = get_option( self::DB_VERSION_OPTION, '0' );

        if ( version_compare( $current_db_version, self::DB_VERSION, '>=' ) ) {
            return;
        }

        $this->run_migrations( $current_db_version );
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Migration'ları çalıştır
     *
     * @param string $from_version Mevcut versiyon
     */
    private function run_migrations( $from_version ) {
        $migrations = array(
            '1.0.0' => 'migrate_1_0_0',
        );

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
     * İlk kurulum migration'ı
     */
    private function migrate_1_0_0() {
        // İlk sürüm - varsayılan ayarlar zaten kaydedildi
        // Gelecekteki migration'lar için örnek:
        //
        // $settings = get_option( self::SETTINGS_OPTION, array() );
        // $settings['new_setting'] = 'default_value';
        // update_option( self::SETTINGS_OPTION, $settings );
    }

    /**
     * Varsayılan ayarları döndür
     *
     * @return array
     */
    public static function get_defaults() {
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
     * Yeni kurulum mu kontrol et
     *
     * @return bool
     */
    public static function is_fresh_install() {
        return ! get_option( self::DB_VERSION_OPTION );
    }
}`,
  },
  {
    id: 'options',
    name: 'class-options.php',
    path: 'includes/class-options.php',
    description: 'Ayar yönetimi - get/set/all/update, sanitize, static cache',
    language: 'php',
    code: `<?php
/**
 * Ayar Yönetimi Sınıfı
 *
 * get_option('wpsm_settings') üzerinden çalışır.
 * Static cache ile aynı request'te tekrar sorgu atmaz.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Options {

    const OPTION_NAME = 'wpsm_settings';

    /**
     * Static cache
     * Aynı request'te birden fazla get() çağrısı yapıldığında
     * veritabanına tekrar sorgu göndermez.
     */
    private static $cache = null;

    /**
     * Sanitize callback'leri
     * Her ayar tipi için uygun sanitize fonksiyonu.
     */
    private static $sanitize_callbacks = array(
        'title_separator'        => 'sanitize_text_field',
        'enable_sitemap'         => 'boolean',
        'enable_schema'          => 'boolean',
        'enable_opengraph'       => 'boolean',
        'enable_twitter'         => 'boolean',
        'enable_breadcrumbs'     => 'boolean',
        'default_schema_type'    => 'sanitize_text_field',
        'twitter_site'           => 'sanitize_text_field',
        'twitter_creator'        => 'sanitize_text_field',
        'facebook_app_id'        => 'sanitize_text_field',
        'facebook_admins'        => 'sanitize_text_field',
        'google_verification'    => 'sanitize_text_field',
        'bing_verification'      => 'sanitize_text_field',
        'yandex_verification'    => 'sanitize_text_field',
        'pinterest_verification' => 'sanitize_text_field',
        'sitemap_post_types'     => 'array_sanitize',
        'sitemap_taxonomies'     => 'array_sanitize',
        'sitemap_posts_per_page' => 'intval',
        'breadcrumb_home_text'   => 'sanitize_text_field',
        'breadcrumb_show_home'   => 'boolean',
        'breadcrumb_separator'   => 'sanitize_text_field',
        'global_noindex'         => 'array_sanitize',
        'rss_before_content'     => 'wp_kses_post',
        'rss_after_content'      => 'wp_kses_post',
    );

    public function __construct() {
        $this->load_cache();
    }

    /**
     * Cache'i yükle
     */
    private function load_cache() {
        if ( null === self::$cache ) {
            self::$cache = get_option( self::OPTION_NAME, array() );
            if ( empty( self::$cache ) ) {
                self::$cache = Class_Installer::get_defaults();
            }
        }
    }

    /**
     * Belirli bir ayar değerini döndür
     *
     * @param string $key     Ayar anahtarı
     * @param mixed  $default Varsayılan değer
     * @return mixed
     */
    public function get( $key, $default = null ) {
        $this->load_cache();

        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

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
     * @return bool
     */
    public function set( $key, $value ) {
        $this->load_cache();
        self::$cache[ $key ] = $this->sanitize_value( $key, $value );
        return update_option( self::OPTION_NAME, self::$cache );
    }

    /**
     * Tüm ayarları döndür
     *
     * @return array
     */
    public function all() {
        $this->load_cache();
        $defaults = Class_Installer::get_defaults();
        return wp_parse_args( self::$cache, $defaults );
    }

    /**
     * Birden fazla ayarı güncelle
     *
     * @param array $data Güncellenecek ayarlar
     * @return bool
     */
    public function update( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        $this->load_cache();

        foreach ( $data as $key => $value ) {
            self::$cache[ $key ] = $this->sanitize_value( $key, $value );
        }

        do_action( 'wpsm_before_settings_update', $data, self::$cache );
        $result = update_option( self::OPTION_NAME, self::$cache );
        do_action( 'wpsm_after_settings_update', $data, $result );

        return $result;
    }

    /**
     * Değeri sanitize et
     *
     * @param string $key   Ayar anahtarı
     * @param mixed  $value Ayar değeri
     * @return mixed
     */
    private function sanitize_value( $key, $value ) {
        if ( ! isset( self::$sanitize_callbacks[ $key ] ) ) {
            return sanitize_text_field( $value );
        }

        $callback = self::$sanitize_callbacks[ $key ];

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
                if ( is_callable( $callback ) ) {
                    return call_user_func( $callback, $value );
                }
                return sanitize_text_field( $value );
        }
    }

    /**
     * Cache'i temizle
     */
    public function clear_cache() {
        self::$cache = null;
    }

    /**
     * Cache'i yeniden yükle
     *
     * @return array
     */
    public function refresh() {
        $this->clear_cache();
        $this->load_cache();
        return self::$cache;
    }

    /**
     * Ayar var mı kontrol et
     *
     * @param string $key
     * @return bool
     */
    public function has( $key ) {
        $this->load_cache();
        return isset( self::$cache[ $key ] );
    }

    /**
     * Tüm ayarları sıfırla
     *
     * @return bool
     */
    public function reset() {
        $defaults = Class_Installer::get_defaults();
        self::$cache = $defaults;
        return update_option( self::OPTION_NAME, $defaults );
    }

    /**
     * Ayarları dışa aktar (JSON)
     *
     * @return string
     */
    public function export() {
        return wp_json_encode( $this->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }

    /**
     * Ayarları içe aktar (JSON)
     *
     * @param string $json
     * @return bool|array
     */
    public function import( $json ) {
        $data = json_decode( $json, true );
        if ( ! is_array( $data ) ) {
            return false;
        }

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
}`,
  },
  {
    id: 'i18n',
    name: 'class-i18n.php',
    path: 'includes/class-i18n.php',
    description: 'Çoklu dil desteği - Text domain, .mo/.po dosya yönetimi',
    language: 'php',
    code: `<?php
/**
 * Uluslararasılaştırma (i18n) Sınıfı
 *
 * Eklenti dil dosyalarını yükler.
 * Text domain: wp-seo-master
 *
 * Kullanım:
 * - __('Metin', 'wp-seo-master')
 * - _e('Metin', 'wp-seo-master')
 * - esc_html__('Metin', 'wp-seo-master')
 * - _n('tekil', 'çoğul', $count, 'wp-seo-master')
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_I18n {

    /**
     * Text domain
     */
    const TEXT_DOMAIN = 'wp-seo-master';

    /**
     * Dil dosyaları dizini
     */
    const LANGUAGES_DIR = 'languages';

    /**
     * Yüklendi mi
     */
    private static $loaded = false;

    /**
     * Plugin text domain'ini yükle
     *
     * WordPress'in init veya plugins_loaded hook'unda çağrılır.
     *
     * Dosya yapısı:
     * - wp-seo-master-tr_TR.po
     * - wp-seo-master-tr_TR.mo
     * - wp-seo-master-en_US.po
     * - wp-seo-master-en_US.mo
     *
     * @return bool
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
     * Text domain yüklü mü
     *
     * @return bool
     */
    public function is_loaded() {
        return self::$loaded;
    }

    /**
     * Çeviri durumunu döndür
     *
     * @return array
     */
    public function get_translations() {
        $translations = array(
            'tr_TR' => array(
                'name'    => 'Türkçe',
                'native'  => 'Türkçe',
                'status'  => 'complete',
                'version' => '1.0.0',
            ),
            'en_US' => array(
                'name'    => 'English',
                'native'  => 'English (US)',
                'status'  => 'complete',
                'version' => '1.0.0',
            ),
        );

        return apply_filters( 'wpsm_translations', $translations );
    }

    /**
     * Belirli bir dil mevcut mu
     *
     * @param string $locale Dil kodu (örn: tr_TR)
     * @return bool
     */
    public function translation_exists( $locale ) {
        $mo_file = $this->get_languages_dir() . '/' . self::TEXT_DOMAIN . '-' . $locale . '.mo';
        return file_exists( $mo_file );
    }
}`,
  },
]

export default function CodeViewer() {
  const [activeFile, setActiveFile] = useState(files[0].id)
  const [copied, setCopied] = useState(false)

  const currentFile = files.find(f => f.id === activeFile) || files[0]

  const handleCopy = () => {
    navigator.clipboard.writeText(currentFile.code)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const highlightCode = (code: string, language: string) => {
    let highlighted = code
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')

    if (language === 'php') {
      highlighted = highlighted
        .replace(/\/\/.*$/gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\/\*\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/(&lt;\?php)/g, '<span class="text-red-400 font-bold">$1</span>')
        .replace(/\b(class|function|private|public|protected|static|return|new|if|else|foreach|for|while|namespace|use|define|require_once|array|true|false|null|self|const|isset|echo|exit|switch|case|break|default)\b/g, '<span class="text-purple-400 font-medium">$1</span>')
        .replace(/(\$[a-zA-Z_]\w*)/g, '<span class="text-blue-300">$1</span>')
        .replace(/'([^'\\]*(?:\\.[^'\\]*)*)'/g, "'<span class=\"text-green-300\">$1</span>'")
    }

    return highlighted
  }

  return (
    <section id="code" className="py-24 relative">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-12">
          <span className="inline-block px-3 py-1 text-xs font-medium bg-orange-500/10 text-orange-400 rounded-full border border-orange-500/20 mb-4">
            KAYNAK KOD
          </span>
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            Kurulum & Ayar Yönetimi
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            Eklenti kurulumu, ayar yönetimi ve çoklu dil desteği için 3 temel dosya.
            Migration altyapısı, static cache, sanitize callback sistemi.
          </p>
        </div>

        {/* File Tabs */}
        <div className="flex flex-wrap gap-2 mb-4">
          {files.map((file) => (
            <button
              key={file.id}
              onClick={() => setActiveFile(file.id)}
              className={`px-4 py-2 text-sm font-medium rounded-lg transition-all ${
                activeFile === file.id
                  ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
                  : 'bg-white/5 text-gray-400 border border-white/5 hover:bg-white/10 hover:text-white'
              }`}
            >
              {file.name}
            </button>
          ))}
        </div>

        {/* File Info */}
        <div className="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div>
            <p className="text-sm text-gray-400">
              <span className="text-gray-500">Yol:</span>{' '}
              <code className="text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded text-xs">{currentFile.path}</code>
            </p>
            <p className="text-xs text-gray-500 mt-1">{currentFile.description}</p>
          </div>
          <button
            onClick={handleCopy}
            className="flex items-center space-x-2 px-3 py-1.5 text-xs font-medium bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 transition-all text-gray-400 hover:text-white"
          >
            {copied ? (
              <>
                <svg className="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                <span className="text-green-400">Kopyalandı!</span>
              </>
            ) : (
              <>
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Kopyala</span>
              </>
            )}
          </button>
        </div>

        {/* Code Block */}
        <div className="bg-gray-900/80 border border-white/5 rounded-2xl overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-white/5 bg-white/[0.02]">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 rounded-full bg-red-500/80" />
              <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
              <div className="w-3 h-3 rounded-full bg-green-500/80" />
            </div>
            <span className="text-xs text-gray-500 font-mono">{currentFile.name}</span>
            <span className="text-xs text-gray-600 uppercase">{currentFile.language}</span>
          </div>

          <div className="overflow-x-auto p-4 max-h-[600px] overflow-y-auto">
            <pre className="text-sm leading-relaxed">
              <code
                dangerouslySetInnerHTML={{
                  __html: highlightCode(currentFile.code, currentFile.language)
                }}
                className="text-gray-300 font-mono"
              />
            </pre>
          </div>
        </div>

        {/* Download Links */}
        <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
          {files.map((file) => (
            <a
              key={file.id}
              href={`/wp-seo-master/${file.path}`}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-xl hover:bg-white/5 hover:border-white/10 transition-all group"
            >
              <div>
                <p className="text-sm font-medium text-white group-hover:text-purple-300 transition-colors">
                  {file.name}
                </p>
                <p className="text-xs text-gray-500 mt-0.5">{file.path}</p>
              </div>
              <svg className="w-5 h-5 text-gray-500 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          ))}
        </div>

        {/* Features Summary */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <div className="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white mb-4">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
            <h3 className="text-lg font-semibold text-white mb-2">Installer</h3>
            <ul className="space-y-2 text-sm text-gray-400">
              <li className="flex items-start">
                <span className="text-green-400 mr-2">✓</span>
                Varsayılan ayarlar kaydı
              </li>
              <li className="flex items-start">
                <span className="text-green-400 mr-2">✓</span>
                DB version migration
              </li>
              <li className="flex items-start">
                <span className="text-green-400 mr-2">✓</span>
                Cron & transient temizliği
              </li>
              <li className="flex items-start">
                <span className="text-green-400 mr-2">✓</span>
                Rewrite flush
              </li>
            </ul>
          </div>

          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white mb-4">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 className="text-lg font-semibold text-white mb-2">Options</h3>
            <ul className="space-y-2 text-sm text-gray-400">
              <li className="flex items-start">
                <span className="text-blue-400 mr-2">✓</span>
                get/set/all/update API
              </li>
              <li className="flex items-start">
                <span className="text-blue-400 mr-2">✓</span>
                Static cache (tek sorgu)
              </li>
              <li className="flex items-start">
                <span className="text-blue-400 mr-2">✓</span>
                Sanitize callbacks
              </li>
              <li className="flex items-start">
                <span className="text-blue-400 mr-2">✓</span>
                Import/Export (JSON)
              </li>
            </ul>
          </div>

          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <div className="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center text-white mb-4">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
              </svg>
            </div>
            <h3 className="text-lg font-semibold text-white mb-2">i18n</h3>
            <ul className="space-y-2 text-sm text-gray-400">
              <li className="flex items-start">
                <span className="text-purple-400 mr-2">✓</span>
                Text domain: wp-seo-master
              </li>
              <li className="flex items-start">
                <span className="text-purple-400 mr-2">✓</span>
                .mo/.po dosya yönetimi
              </li>
              <li className="flex items-start">
                <span className="text-purple-400 mr-2">✓</span>
                Çift dizin desteği
              </li>
              <li className="flex items-start">
                <span className="text-purple-400 mr-2">✓</span>
                Locale filtre desteği
              </li>
            </ul>
          </div>
        </div>

        {/* Default Settings Table */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">Varsayılan Ayarlar</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-white/10">
                  <th className="text-left py-3 px-4 text-gray-400 font-medium">Ayar</th>
                  <th className="text-left py-3 px-4 text-gray-400 font-medium">Varsayılan Değer</th>
                  <th className="text-left py-3 px-4 text-gray-400 font-medium">Tip</th>
                </tr>
              </thead>
              <tbody className="text-gray-300">
                {[
                  ['title_separator', '|', 'string'],
                  ['enable_sitemap', 'true', 'boolean'],
                  ['enable_schema', 'true', 'boolean'],
                  ['enable_opengraph', 'true', 'boolean'],
                  ['enable_twitter', 'true', 'boolean'],
                  ['enable_breadcrumbs', 'true', 'boolean'],
                  ['default_schema_type', 'Article', 'string'],
                  ['twitter_site', "''", 'string'],
                  ['facebook_app_id', "''", 'string'],
                  ['sitemap_post_types', "['post', 'page']", 'array'],
                  ['sitemap_posts_per_page', '1000', 'int'],
                  ['breadcrumb_separator', '»', 'string'],
                ].map(([key, value, type], i) => (
                  <tr key={i} className="border-b border-white/5">
                    <td className="py-2 px-4 font-mono text-xs text-purple-300">{key}</td>
                    <td className="py-2 px-4 font-mono text-xs text-green-300">{value}</td>
                    <td className="py-2 px-4">
                      <span className={`text-xs px-2 py-0.5 rounded ${
                        type === 'boolean' ? 'bg-yellow-500/10 text-yellow-400' :
                        type === 'array' ? 'bg-blue-500/10 text-blue-400' :
                        type === 'int' ? 'bg-orange-500/10 text-orange-400' :
                        'bg-gray-500/10 text-gray-400'
                      }`}>
                        {type}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  )
}
