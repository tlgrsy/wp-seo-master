<?php
/**
 * Ana Eklenti Sınıfı
 *
 * Singleton pattern ile tüm eklenti modüllerini yönetir.
 * Admin ve frontend hook'larını ayrı ayrı register eder.
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
 * Class Class_Plugin
 *
 * Eklentinin ana sınıfı. Singleton pattern uygulanmıştır.
 * Tüm bağımlılıkları yükler ve hook'ları register eder.
 */
class Class_Plugin {

    /**
     * Singleton instance
     *
     * @var Class_Plugin|null
     */
    private static $instance = null;

    /**
     * Options sınıfı instance
     *
     * @var Class_Options|null
     */
    private $options = null;

    /**
     * i18n sınıfı instance
     *
     * @var Class_I18n|null
     */
    private $i18n = null;

    /**
     * Admin modülleri
     *
     * @var array
     */
    private $admin_modules = array();

    /**
     * Frontend modülleri
     *
     * @var array
     */
    private $frontend_modules = array();

    /**
     * Singleton instance döndürür
     *
     * @return Class_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Kurucu metod (private - Singleton pattern)
     *
     * Bağımlılıkları yükler ve hook'ları register eder.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->run();
    }

    /**
     * Singleton klonlamayı engelle
     */
    private function __clone() {}

    /**
     * unserialize engelle
     */
    public function __wakeup() {
        throw new \Exception( 'Singleton sınıflar unserialize edilemez.' );
    }

    /**
     * Tüm bağımlılıkları yükle
     *
     * Eklentinin ihtiyaç duyduğu tüm sınıf dosyalarını autoloader
     * aracılığıyla yükler. Autoloader wp-seo-master.php dosyasında
     * tanımlı olduğundan, burada sadece instance oluşturulur.
     */
    private function load_dependencies() {
        // Temel sınıflar
        $this->options = new Class_Options();
        $this->i18n    = new Class_I18n();

        // Admin modülleri
        if ( is_admin() ) {
            $this->admin_modules['menu']    = new Admin\Class_Admin_Menu( $this->options );
            $this->admin_modules['settings'] = new Admin\Class_Settings( $this->options );
            $this->admin_modules['metabox']  = new Admin\Class_Metabox( $this->options );
        }

        // Frontend modülleri (hem admin hem frontend'te gerekli olabilir)
        $this->frontend_modules['meta_tags']     = new Frontend\Class_Meta_Tags( $this->options );
        $this->frontend_modules['opengraph']     = new Frontend\Class_Opengraph( $this->options );
        $this->frontend_modules['twitter_cards'] = new Frontend\Class_Twitter_Cards( $this->options );
        $this->frontend_modules['canonical']     = new Frontend\Class_Canonical( $this->options );
        $this->frontend_modules['breadcrumbs']   = new Frontend\Class_Breadcrumbs( $this->options );
        $this->frontend_modules['robots']        = new Frontend\Class_Robots( $this->options );

        // Schema modülleri
        $this->frontend_modules['schema_manager']       = new Schema\Class_Schema_Manager( $this->options );
        $this->frontend_modules['article_schema']       = new Schema\Class_Article_Schema( $this->options );
        $this->frontend_modules['faq_schema']           = new Schema\Class_FAQ_Schema( $this->options );
        $this->frontend_modules['howto_schema']         = new Schema\Class_Howto_Schema( $this->options );
        $this->frontend_modules['product_schema']       = new Schema\Class_Product_Schema( $this->options );
        $this->frontend_modules['localbusiness_schema'] = new Schema\Class_Localbusiness_Schema( $this->options );
        $this->frontend_modules['breadcrumb_schema']    = new Schema\Class_Breadcrumb_Schema( $this->options );

        // Sitemap modülleri
        $this->frontend_modules['sitemap_generator'] = new Sitemap\Class_Sitemap_Generator( $this->options );
        $this->frontend_modules['sitemap_index']     = new Sitemap\Class_Sitemap_Index( $this->options );

        // İçerik analiz modülü
        $this->admin_modules['content_analyzer'] = new Analyzer\Class_Content_Analyzer( $this->options );
    }

    /**
     * Dil ayarlarını yapılandır
     */
    private function set_locale() {
        $this->i18n->load_plugin_textdomain();
    }

    /**
     * Eklentiyi çalıştır
     *
     * Admin ve frontend hook'larını ayrı ayrı register eder.
     * is_admin() kontrolü ile admin/frontend ayrımı yapılır.
     */
    private function run() {
        // Admin hook'ları
        if ( is_admin() ) {
            $this->register_admin_hooks();
        }

        // Frontend hook'ları (her zaman çalışır)
        $this->register_frontend_hooks();

        // Ortak hook'lar (REST API vb.)
        $this->register_common_hooks();
    }

    /**
     * Admin hook'larını register et
     *
     * Admin panelinde gerekli olan tüm action ve filter'ları ekler.
     */
    private function register_admin_hooks() {
        // Admin menü
        add_action( 'admin_menu', array( $this->admin_modules['menu'], 'register_menu' ) );

        // Ayarlar sayfası
        add_action( 'admin_init', array( $this->admin_modules['settings'], 'register_settings' ) );

        // Metabox (yazı düzenleme ekranı)
        add_action( 'add_meta_boxes', array( $this->admin_modules['metabox'], 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this->admin_modules['metabox'], 'save_meta' ), 10, 2 );

        // Admin asset'leri
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // İçerik analiz - AJAX
        add_action( 'wp_ajax_wpsm_analyze_content', array( $this->admin_modules['content_analyzer'], 'ajax_analyze' ) );
    }

    /**
     * Frontend hook'larını register et
     *
     * Site frontend'inde gerekli olan tüm action ve filter'ları ekler.
     */
    private function register_frontend_hooks() {
        // Meta etiketleri (title, description, keywords)
        add_action( 'wp_head', array( $this->frontend_modules['meta_tags'], 'output_meta_tags' ), 1 );

        // Open Graph etiketleri
        add_action( 'wp_head', array( $this->frontend_modules['opengraph'], 'output_opengraph' ), 5 );

        // Twitter Cards etiketleri
        add_action( 'wp_head', array( $this->frontend_modules['twitter_cards'], 'output_twitter_cards' ), 6 );

        // Canonical URL
        add_action( 'wp_head', array( $this->frontend_modules['canonical'], 'output_canonical' ), 2 );

        // Robots meta
        add_filter( 'wp_robots', array( $this->frontend_modules['robots'], 'modify_robots' ) );

        // Schema markup
        add_action( 'wp_head', array( $this->frontend_modules['schema_manager'], 'output_schema' ), 10 );

        // Breadcrumbs (shortcode olarak da kullanılabilir)
        add_shortcode( 'wpsm_breadcrumbs', array( $this->frontend_modules['breadcrumbs'], 'render_breadcrumbs' ) );

        // Sitemap
        add_action( 'init', array( $this->frontend_modules['sitemap_generator'], 'register_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this->frontend_modules['sitemap_generator'], 'render_sitemap' ) );
    }

    /**
     * Ortak hook'ları register et
     *
     * REST API endpoint'leri ve diğer ortak işlevler.
     */
    private function register_common_hooks() {
        // REST API meta alanları
        add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
    }

    /**
     * Admin CSS ve JS dosyalarını yükle
     *
     * @param string $hook Sayfa hook adı
     */
    public function enqueue_admin_assets( $hook ) {
        // Sadece eklenti sayfalarında yükle
        $plugin_pages = array(
            'toplevel_page_wp-seo-master',
            'wp-seo-master_page_wpsm-settings',
        );

        if ( ! in_array( $hook, $plugin_pages, true ) ) {
            // Post edit ekranında da yükle (metabox için)
            global $post;
            if ( ! $post ) {
                return;
            }
        }

        // CSS
        wp_enqueue_style(
            'wpsm-admin',
            WPSM_URL . 'assets/css/admin.css',
            array(),
            WPSM_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'wpsm-admin',
            WPSM_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-blocks', 'wp-element' ),
            WPSM_VERSION,
            true
        );

        // Localize script - JS'e veri aktar
        wp_localize_script( 'wpsm-admin', 'wpsmAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wpsm_admin_nonce' ),
            'i18n'    => array(
                'analyzing' => __( 'Analiz ediliyor...', 'wp-seo-master' ),
                'success'   => __( 'Analiz tamamlandı.', 'wp-seo-master' ),
                'error'     => __( 'Bir hata oluştu.', 'wp-seo-master' ),
            ),
        ));
    }

    /**
     * REST API alanlarını kaydet
     *
     * Post meta alanlarını REST API üzerinden okunabilir/yazılabilir yapar.
     */
    public function register_rest_fields() {
        $post_types = get_post_types( array( 'public' => true ) );

        foreach ( $post_types as $post_type ) {
            // SEO Title
            register_rest_field( $post_type, 'wpsm_title', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_title', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_title', sanitize_text_field( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'SEO başlığı', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));

            // Meta Description
            register_rest_field( $post_type, 'wpsm_description', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_description', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_description', sanitize_textarea_field( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'Meta açıklaması', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));

            // Meta Keywords
            register_rest_field( $post_type, 'wpsm_keywords', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_keywords', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_keywords', sanitize_text_field( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'Meta anahtar kelimeleri', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));

            // Robots
            register_rest_field( $post_type, 'wpsm_robots', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_robots', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_robots', sanitize_text_field( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'Robots direktifleri', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));

            // Canonical URL
            register_rest_field( $post_type, 'wpsm_canonical', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_canonical', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_canonical', esc_url_raw( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'Canonical URL', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));

            // OG Image
            register_rest_field( $post_type, 'wpsm_og_image', array(
                'get_callback'    => function ( $post ) {
                    return get_post_meta( $post['id'], '_wpsm_og_image', true );
                },
                'update_callback' => function ( $value, $post ) {
                    update_post_meta( $post->ID, '_wpsm_og_image', esc_url_raw( $value ) );
                },
                'schema'          => array(
                    'description' => __( 'Open Graph görseli', 'wp-seo-master' ),
                    'type'        => 'string',
                ),
            ));
        }
    }

    /**
     * Options instance'ını döndür
     *
     * @return Class_Options
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Belirli bir modülü döndür
     *
     * @param string $module Modül adı
     * @return object|null
     */
    public function get_module( $module ) {
        if ( isset( $this->admin_modules[ $module ] ) ) {
            return $this->admin_modules[ $module ];
        }
        if ( isset( $this->frontend_modules[ $module ] ) ) {
            return $this->frontend_modules[ $module ];
        }
        return null;
    }
}
