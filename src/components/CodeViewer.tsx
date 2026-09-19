import { useState } from 'react'

const files = [
  {
    id: 'main',
    name: 'wp-seo-master.php',
    path: 'wp-seo-master/wp-seo-master.php',
    description: 'Ana eklenti dosyası - Bootstrap, sabitler, autoloader, hook tanımları',
    language: 'php',
    code: `<?php
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
 * WPSM\\Admin\\Class_Admin_Menu → includes/Admin/class-admin-menu.php
 * WPSM\\Frontend\\Class_Meta_Tags → includes/Frontend/class-meta-tags.php
 */
spl_autoload_register( function ( $class_name ) {
    $prefix = 'WPSM\\\\';
    $prefix_length = strlen( $prefix );

    if ( strncmp( $prefix, $class_name, $prefix_length ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class_name, $prefix_length );
    $parts = explode( '\\\\', $relative_class );
    $class_file = array_pop( $parts );

    // Alt çizgileri tireye çevir, küçük harfe çevir
    $class_file = str_replace( '_', '-', strtolower( $class_file ) );

    $file_path = WPSM_INCLUDES_PATH;

    if ( ! empty( $parts ) ) {
        $file_path .= implode( '/', $parts ) . '/';
    }

    $file_path .= 'class-' . $class_file . '.php';

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
});

/**
 * Eklenti aktivasyon hook'u
 */
function wpsm_activate() {
    require_once WPSM_INCLUDES_PATH . 'class-installer.php';
    $installer = new WPSM\\Class_Installer();
    $installer->activate();
}
register_activation_hook( __FILE__, 'wpsm_activate' );

/**
 * Eklenti deaktivasyon hook'u
 */
function wpsm_deactivate() {
    require_once WPSM_INCLUDES_PATH . 'class-installer.php';
    $installer = new WPSM\\Class_Installer();
    $installer->deactivate();
}
register_deactivation_hook( __FILE__, 'wpsm_deactivate' );

/**
 * Eklentiyi başlat
 */
function wpsm_init() {
    load_plugin_textdomain( 'wp-seo-master', false, dirname( WPSM_BASENAME ) . '/languages/' );
    WPSM\\Class_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'wpsm_init' );`,
  },
  {
    id: 'plugin',
    name: 'class-plugin.php',
    path: 'includes/class-plugin.php',
    description: 'Ana eklenti sınıfı - Singleton pattern, modül yönetimi, hook kaydı',
    language: 'php',
    code: `<?php
/**
 * Ana Eklenti Sınıfı
 *
 * Singleton pattern ile tüm eklenti modüllerini yönetir.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Plugin {

    /** @var Class_Plugin|null Singleton instance */
    private static $instance = null;

    /** @var Class_Options|null */
    private $options = null;

    /** @var Class_I18n|null */
    private $i18n = null;

    /** @var array Admin modülleri */
    private $admin_modules = array();

    /** @var array Frontend modülleri */
    private $frontend_modules = array();

    /**
     * Singleton instance döndürür
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Kurucu metod (private - Singleton)
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->run();
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \\Exception( 'Singleton sınıflar unserialize edilemez.' );
    }

    /**
     * Tüm bağımlılıkları yükle
     */
    private function load_dependencies() {
        $this->options = new Class_Options();
        $this->i18n    = new Class_I18n();

        // Admin modülleri
        if ( is_admin() ) {
            $this->admin_modules['menu']     = new Admin\\Class_Admin_Menu( $this->options );
            $this->admin_modules['settings'] = new Admin\\Class_Settings( $this->options );
            $this->admin_modules['metabox']  = new Admin\\Class_Metabox( $this->options );
        }

        // Frontend modülleri
        $this->frontend_modules['meta_tags']     = new Frontend\\Class_Meta_Tags( $this->options );
        $this->frontend_modules['opengraph']     = new Frontend\\Class_Opengraph( $this->options );
        $this->frontend_modules['twitter_cards'] = new Frontend\\Class_Twitter_Cards( $this->options );
        $this->frontend_modules['canonical']     = new Frontend\\Class_Canonical( $this->options );
        $this->frontend_modules['breadcrumbs']   = new Frontend\\Class_Breadcrumbs( $this->options );
        $this->frontend_modules['robots']        = new Frontend\\Class_Robots( $this->options );

        // Schema modülleri
        $this->frontend_modules['schema_manager'] = new Schema\\Class_Schema_Manager( $this->options );

        // Sitemap
        $this->frontend_modules['sitemap_generator'] = new Sitemap\\Class_Sitemap_Generator( $this->options );

        // İçerik analiz
        $this->admin_modules['content_analyzer'] = new Analyzer\\Class_Content_Analyzer( $this->options );
    }

    /**
     * Eklentiyi çalıştır
     */
    private function run() {
        if ( is_admin() ) {
            $this->register_admin_hooks();
        }
        $this->register_frontend_hooks();
        $this->register_common_hooks();
    }

    /**
     * Admin hook'larını register et
     */
    private function register_admin_hooks() {
        add_action( 'admin_menu', array( $this->admin_modules['menu'], 'register_menu' ) );
        add_action( 'admin_init', array( $this->admin_modules['settings'], 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this->admin_modules['metabox'], 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this->admin_modules['metabox'], 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Frontend hook'larını register et
     */
    private function register_frontend_hooks() {
        add_action( 'wp_head', array( $this->frontend_modules['meta_tags'], 'output_meta_tags' ), 1 );
        add_action( 'wp_head', array( $this->frontend_modules['opengraph'], 'output_opengraph' ), 5 );
        add_action( 'wp_head', array( $this->frontend_modules['twitter_cards'], 'output_twitter_cards' ), 6 );
        add_action( 'wp_head', array( $this->frontend_modules['canonical'], 'output_canonical' ), 2 );
        add_filter( 'wp_robots', array( $this->frontend_modules['robots'], 'modify_robots' ) );
        add_action( 'wp_head', array( $this->frontend_modules['schema_manager'], 'output_schema' ), 10 );
        add_shortcode( 'wpsm_breadcrumbs', array( $this->frontend_modules['breadcrumbs'], 'render_breadcrumbs' ) );
    }

    /**
     * REST API alanlarını kaydet
     */
    public function register_rest_fields() {
        $post_types = get_post_types( array( 'public' => true ) );
        foreach ( $post_types as $post_type ) {
            register_rest_field( $post_type, 'wpsm_title', array(
                'get_callback' => function( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_title', true );
                },
                'update_callback' => function( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_title', sanitize_text_field( $value ) );
                },
            ));
        }
    }
}`,
  },
  {
    id: 'autoloader',
    name: 'class-autoloader.php',
    path: 'includes/class-autoloader.php',
    description: 'PSR-4 benzeri otomatik sınıf yükleme mekanizması',
    language: 'php',
    code: `<?php
/**
 * Autoloader Sınıfı
 *
 * Namespace tabanlı otomatik sınıf yükleyici.
 *
 * Dönüşüm Örnekleri:
 * - WPSM\\Class_Plugin → includes/class-plugin.php
 * - WPSM\\Admin\\Class_Settings → includes/Admin/class-settings.php
 * - WPSM\\Schema\\Class_Schema_Manager → includes/Schema/class-schema-manager.php
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Autoloader {

    /** @var string Namespace ön eki */
    private $prefix = 'WPSM\\\\';

    /** @var string Kök dizin yolu */
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
     */
    public function register() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }

    /**
     * Otomatik sınıf yükleme metodu
     *
     * @param string $class_name Tam sınıf adı (namespace dahil)
     * @return bool Başarılıysa true, bulunamazsa false
     */
    public function autoload( $class_name ) {
        // Sadece WPSM namespace'ini işle
        if ( ! $this->is_our_namespace( $class_name ) ) {
            return false;
        }

        // Dosya yolunu oluştur
        $file_path = $this->get_file_path( $class_name );

        // Dosya varsa yükle
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            return true;
        }

        return false;
    }

    /**
     * Namespace kontrolü
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
        $parts = explode( '\\\\', $relative_class );

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

        return $file_path . $class_file;
    }

    /**
     * Sınıf adını dosya adına çevir
     *
     * Class_Admin_Menu → class-admin-menu.php
     */
    private function class_name_to_file( $class_name ) {
        $file_name = str_replace( '_', '-', $class_name );
        $file_name = strtolower( $file_name );
        return $file_name . '.php';
    }

    /**
     * Debug - sınıf adının hangi dosyaya eşleneceğini göster
     */
    public function debug_resolve( $class_name ) {
        return $this->get_file_path( $class_name );
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

  // Basit syntax highlighting
  const highlightCode = (code: string) => {
    return code
      .replace(/\/\/.*$/gm, '<span class="text-gray-500">$&</span>')
      .replace(/\/\*\*[\s\S]*?\*\//gm, '<span class="text-gray-500">$&</span>')
      .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500">$&</span>')
      .replace(/(&lt;\?php)/g, '<span class="text-red-400">$1</span>')
      .replace(/\b(class|function|private|public|static|return|new|if|else|foreach|namespace|use|define|require_once|array|true|false|null|self)\b/g, '<span class="text-purple-400">$1</span>')
      .replace(/(\$[a-zA-Z_]\w*)/g, '<span class="text-blue-300">$1</span>')
      .replace(/'([^']*)'/g, "'<span class=\"text-green-300\">$1</span>'")
      .replace(/"([^"]*)"/g, '"<span class="text-green-300">$1</span>"')
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
            PHP Dosyaları
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            Eklentinin 3 temel dosyasının tam kaynak kodu. Her dosya WPCS standartlarına uygun, Türkçe yorum satırları ile dokumente edilmiştir.
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
        <div className="mb-4 flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-400">
              <span className="text-gray-500">Yol:</span>{' '}
              <code className="text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded">{currentFile.path}</code>
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
          {/* Code Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-white/5 bg-white/[0.02]">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 rounded-full bg-red-500/80" />
              <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
              <div className="w-3 h-3 rounded-full bg-green-500/80" />
            </div>
            <span className="text-xs text-gray-500 font-mono">{currentFile.name}</span>
            <span className="text-xs text-gray-600">PHP</span>
          </div>

          {/* Code Content */}
          <div className="overflow-x-auto p-4">
            <pre className="text-sm leading-relaxed">
              <code
                dangerouslySetInnerHTML={{
                  __html: highlightCode(
                    currentFile.code
                      .replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                  )
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
      </div>
    </section>
  )
}
