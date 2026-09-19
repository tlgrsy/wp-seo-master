<?php
/**
 * Admin Menü Sınıfı
 *
 * WordPress admin panelinde eklenti menüsünü oluşturur.
 * Ana menü ve alt menü sayfalarını register eder.
 *
 * @package WPSM\Admin
 * @since 1.0.0
 */

namespace WPSM\Admin;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Class_Admin_Menu
 *
 * Admin panelinde menü yapısını oluşturur.
 * Her sayfa için ilgili view dosyasını include eder.
 */
class Class_Admin_Menu {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Menü slug
     *
     * @var string
     */
    const MENU_SLUG = 'wp-seo-master';

    /**
     * Gerekli yetki
     *
     * @var string
     */
    const CAPABILITY = 'manage_options';

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Admin menüsünü kaydet
     *
     * admin_menu hook'u ile çağrılır.
     * Ana menü ve alt menü sayfalarını oluşturur.
     */
    public function register_menu() {
        // Ana menü sayfası (Dashboard)
        add_menu_page(
            __( 'WP SEO Master', 'wp-seo-master' ),
            __( 'WP SEO Master', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_dashboard' ),
            'dashicons-chart-line',
            80
        );

        // Alt menü: Dashboard
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Dashboard', 'wp-seo-master' ),
            __( 'Dashboard', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_dashboard' )
        );

        // Alt menü: Genel Ayarlar
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG . '-settings',
            array( $this, 'render_settings' )
        );

        // Alt menü: Şema Ayarları
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Şema Ayarları', 'wp-seo-master' ),
            __( 'Şema', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG . '-schema',
            array( $this, 'render_schema' )
        );

        // Alt menü: Sosyal Medya
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Sosyal Medya', 'wp-seo-master' ),
            __( 'Sosyal Medya', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG . '-social',
            array( $this, 'render_social' )
        );

        // Alt menü: Sitemap
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Sitemap', 'wp-seo-master' ),
            __( 'Sitemap', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG . '-sitemap',
            array( $this, 'render_sitemap' )
        );

        // Alt menü: İçerik Analizi
        add_submenu_page(
            self::MENU_SLUG,
            __( 'İçerik Analizi', 'wp-seo-master' ),
            __( 'İçerik Analizi', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG . '-analyzer',
            array( $this, 'render_analyzer' )
        );
    }

    /**
     * Admin asset'lerini yükle
     *
     * Sadece eklenti sayfalarında CSS ve JS dosyalarını yükler.
     * Media uploader için wp_enqueue_media() çağrılır.
     *
     * @param string $hook Sayfa hook adı
     */
    public function enqueue_admin_assets( $hook ) {
        // Sadece eklenti sayfalarında yükle
        if ( strpos( $hook, self::MENU_SLUG ) === false ) {
            return;
        }

        // Media uploader için
        wp_enqueue_media();

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
            array( 'jquery', 'wp-util' ),
            WPSM_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'wpsm-admin', 'wpsmAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wpsm_admin_nonce' ),
            'i18n'    => array(
                'selectImage' => __( 'Görsel Seç', 'wp-seo-master' ),
                'useImage'    => __( 'Kullan', 'wp-seo-master' ),
                'removeImage' => __( 'Kaldır', 'wp-seo-master' ),
                'confirm'     => __( 'Emin misiniz?', 'wp-seo-master' ),
                'saving'      => __( 'Kaydediliyor...', 'wp-seo-master' ),
                'saved'       => __( 'Kaydedildi!', 'wp-seo-master' ),
                'error'       => __( 'Bir hata oluştu.', 'wp-seo-master' ),
            ),
        ));
    }

    /**
     * Dashboard sayfasını render et
     */
    public function render_dashboard() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        include WPSM_INCLUDES_PATH . 'Admin/views/dashboard.php';
    }

    /**
     * Genel ayarlar sayfasını render et
     */
    public function render_settings() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        // Tab parametresini al
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

        // View dosyasını belirle
        $view_file = WPSM_INCLUDES_PATH . 'Admin/views/settings-' . $tab . '.php';

        // Dosya var mı kontrol et
        if ( ! file_exists( $view_file ) ) {
            $view_file = WPSM_INCLUDES_PATH . 'Admin/views/settings-general.php';
        }

        // Ayarlar instance
        $settings = new Class_Settings( $this->options );

        include $view_file;
    }

    /**
     * Şema ayarları sayfasını render et
     */
    public function render_schema() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        include WPSM_INCLUDES_PATH . 'Admin/views/settings-schema.php';
    }

    /**
     * Sosyal medya ayarları sayfasını render et
     */
    public function render_social() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        include WPSM_INCLUDES_PATH . 'Admin/views/settings-social.php';
    }

    /**
     * Sitemap ayarları sayfasını render et
     */
    public function render_sitemap() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        include WPSM_INCLUDES_PATH . 'Admin/views/settings-sitemap.php';
    }

    /**
     * İçerik analizi sayfasını render et
     */
    public function render_analyzer() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'wp-seo-master' ) );
        }

        include WPSM_INCLUDES_PATH . 'Admin/views/analyzer.php';
    }

    /**
     * Admin sayfa başlığını render et
     *
     * Tüm admin sayfalarında ortak header.
     *
     * @param string $title Sayfa başlığı
     * @param string $icon  Dashicons sınıfı
     */
    public function render_page_header( $title, $icon = 'dashicons-chart-line' ) {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
                <?php echo esc_html( $title ); ?>
            </h1>
            <span class="wpsm-version">v<?php echo esc_html( WPSM_VERSION ); ?></span>
            <hr class="wp-header-end">
        <?php
    }

    /**
     * Admin sayfa footer'ını render et
     */
    public function render_page_footer() {
        ?>
        </div><!-- .wrap -->
        <?php
    }

    /**
     * Admin notice göster
     *
     * @param string $message Mesaj
     * @param string $type    Tip (success, error, warning, info)
     */
    public function render_notice( $message, $type = 'success' ) {
        $allowed_types = array( 'success', 'error', 'warning', 'info' );
        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'info';
        }

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr( $type ),
            esc_html( $message )
        );
    }
}
