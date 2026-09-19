import { useState } from 'react'

const files = [
  {
    id: 'menu',
    name: 'class-admin-menu.php',
    path: 'includes/Admin/class-admin-menu.php',
    description: 'Admin menü yapısı - Dashboard, Genel, Şema, Sosyal, Sitemap, Analiz',
    language: 'php',
    code: `<?php
/**
 * Admin Menü Sınıfı
 *
 * WordPress admin panelinde eklenti menüsünü oluşturur.
 *
 * @package WPSM\\Admin
 * @since 1.0.0
 */

namespace WPSM\\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Admin_Menu {

    private $options;
    const MENU_SLUG  = 'wp-seo-master';
    const CAPABILITY = 'manage_options';

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Admin menüsünü kaydet
     */
    public function register_menu() {
        // Ana menü (Dashboard)
        add_menu_page(
            __( 'WP SEO Master', 'wp-seo-master' ),
            __( 'WP SEO Master', 'wp-seo-master' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_dashboard' ),
            'dashicons-chart-line',
            80
        );

        // Alt menüler
        add_submenu_page( self::MENU_SLUG,
            __( 'Dashboard', 'wp-seo-master' ),
            __( 'Dashboard', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG,
            array( $this, 'render_dashboard' )
        );

        add_submenu_page( self::MENU_SLUG,
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG . '-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page( self::MENU_SLUG,
            __( 'Şema Ayarları', 'wp-seo-master' ),
            __( 'Şema', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG . '-schema',
            array( $this, 'render_schema' )
        );

        add_submenu_page( self::MENU_SLUG,
            __( 'Sosyal Medya', 'wp-seo-master' ),
            __( 'Sosyal Medya', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG . '-social',
            array( $this, 'render_social' )
        );

        add_submenu_page( self::MENU_SLUG,
            __( 'Sitemap', 'wp-seo-master' ),
            __( 'Sitemap', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG . '-sitemap',
            array( $this, 'render_sitemap' )
        );

        add_submenu_page( self::MENU_SLUG,
            __( 'İçerik Analizi', 'wp-seo-master' ),
            __( 'İçerik Analizi', 'wp-seo-master' ),
            self::CAPABILITY, self::MENU_SLUG . '-analyzer',
            array( $this, 'render_analyzer' )
        );
    }

    /**
     * Admin asset'lerini yükle
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) return;

        wp_enqueue_media(); // Media uploader için

        wp_enqueue_style( 'wpsm-admin',
            WPSM_URL . 'assets/css/admin.css',
            array(), WPSM_VERSION
        );

        wp_enqueue_script( 'wpsm-admin',
            WPSM_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            WPSM_VERSION, true
        );

        wp_localize_script( 'wpsm-admin', 'wpsmAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wpsm_admin_nonce' ),
            'i18n'    => array(
                'selectImage' => __( 'Görsel Seç', 'wp-seo-master' ),
                'useImage'    => __( 'Kullan', 'wp-seo-master' ),
                'saving'      => __( 'Kaydediliyor...', 'wp-seo-master' ),
                'saved'       => __( 'Kaydedildi!', 'wp-seo-master' ),
            ),
        ));
    }

    /**
     * Dashboard sayfasını render et
     */
    public function render_dashboard() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) );
        }
        include WPSM_INCLUDES_PATH . 'Admin/views/dashboard.php';
    }

    /**
     * Genel ayarlar sayfasını render et
     */
    public function render_settings() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) );
        }

        $tab = isset( $_GET['tab'] )
            ? sanitize_text_field( wp_unslash( $_GET['tab'] ) )
            : 'general';

        $view_file = WPSM_INCLUDES_PATH . 'Admin/views/settings-' . $tab . '.php';

        if ( ! file_exists( $view_file ) ) {
            $view_file = WPSM_INCLUDES_PATH . 'Admin/views/settings-general.php';
        }

        $settings = new Class_Settings( $this->options );
        include $view_file;
    }

    // Diğer render metodları...
    public function render_schema() { /* ... */ }
    public function render_social() { /* ... */ }
    public function render_sitemap() { /* ... */ }
    public function render_analyzer() { /* ... */ }
}`,
  },
  {
    id: 'settings',
    name: 'class-settings.php',
    path: 'includes/Admin/class-settings.php',
    description: 'Settings API - register_setting, sections, fields, sanitize',
    language: 'php',
    code: `<?php
/**
 * Ayarlar Sınıfı
 *
 * WordPress Settings API kullanarak eklenti ayarlarını yönetir.
 * Tab yapısı: general, schema, social, sitemap
 *
 * @package WPSM\\Admin
 * @since 1.0.0
 */

namespace WPSM\\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Settings {

    private $options;

    const SETTINGS_GROUP = 'wpsm_settings_group';
    const SETTINGS_NAME  = 'wpsm_settings';
    const NONCE_ACTION   = 'wpsm_save_settings';
    const NONCE_NAME     = 'wpsm_nonce';

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Ayarları kaydet (admin_init hook)
     */
    public function register_settings() {
        register_setting(
            self::SETTINGS_GROUP,
            self::SETTINGS_NAME,
            array( $this, 'sanitize_settings' )
        );

        $this->register_general_settings();
        $this->register_schema_settings();
        $this->register_social_settings();
        $this->register_sitemap_settings();
    }

    /**
     * Genel ayarları register et
     */
    private function register_general_settings() {
        add_settings_section(
            'wpsm_general_section',
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            array( $this, 'render_general_section_desc' ),
            'wpsm_settings_general'
        );

        add_settings_field(
            'wpsm_title_separator',
            __( 'Başlık Ayırıcı', 'wp-seo-master' ),
            array( $this, 'render_title_separator_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        add_settings_field(
            'wpsm_home_title',
            __( 'Ana Sayfa Başlığı', 'wp-seo-master' ),
            array( $this, 'render_home_title_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        add_settings_field(
            'wpsm_google_verification',
            __( 'Google Verification', 'wp-seo-master' ),
            array( $this, 'render_google_verification_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );
    }

    /**
     * Sosyal medya ayarlarını register et
     */
    private function register_social_settings() {
        add_settings_section(
            'wpsm_social_section',
            __( 'Sosyal Medya', 'wp-seo-master' ),
            array( $this, 'render_social_section_desc' ),
            'wpsm_settings_social'
        );

        add_settings_field(
            'wpsm_facebook_app_id',
            __( 'Facebook App ID', 'wp-seo-master' ),
            array( $this, 'render_facebook_app_id_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        add_settings_field(
            'wpsm_twitter_site',
            __( 'Twitter Kullanıcı Adı', 'wp-seo-master' ),
            array( $this, 'render_twitter_site_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        add_settings_field(
            'wpsm_default_og_image',
            __( 'Varsayılan OG Görseli', 'wp-seo-master' ),
            array( $this, 'render_default_og_image_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        add_settings_field(
            'wpsm_twitter_card_type',
            __( 'Twitter Card Türü', 'wp-seo-master' ),
            array( $this, 'render_twitter_card_type_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );
    }

    /**
     * Ayarları sanitize et
     *
     * @param array $input Ham giriş
     * @return array Sanitize edilmiş veri
     */
    public function sanitize_settings( $input ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->options->all();
        }

        $sanitized = array();

        // Title Separator
        if ( isset( $input['title_separator'] ) ) {
            $allowed = array( '|', '-', '–', '»', '/', '·', '•' );
            $sep = sanitize_text_field( $input['title_separator'] );
            $sanitized['title_separator'] = in_array( $sep, $allowed, true ) ? $sep : '|';
        }

        // Twitter Site (@ işareti kaldır)
        if ( isset( $input['twitter_site'] ) ) {
            $sanitized['twitter_site'] = ltrim(
                sanitize_text_field( $input['twitter_site'] ), '@'
            );
        }

        // OG Image URL
        if ( isset( $input['default_og_image'] ) ) {
            $sanitized['default_og_image'] = esc_url_raw( $input['default_og_image'] );
        }

        // Twitter Card Type
        if ( isset( $input['twitter_card_type'] ) ) {
            $allowed = array( 'summary', 'summary_large_image' );
            $type = sanitize_text_field( $input['twitter_card_type'] );
            $sanitized['twitter_card_type'] = in_array( $type, $allowed, true )
                ? $type : 'summary_large_image';
        }

        // Boolean alanlar
        $sanitized['enable_schema']   = isset( $input['enable_schema'] );
        $sanitized['enable_sitemap']  = isset( $input['enable_sitemap'] );
        $sanitized['enable_opengraph'] = isset( $input['enable_opengraph'] );
        $sanitized['enable_twitter']  = isset( $input['enable_twitter'] );

        // Text alanları
        $text_fields = array(
            'home_title', 'home_description', 'google_verification',
            'bing_verification', 'facebook_app_id', 'twitter_creator',
        );
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        return apply_filters( 'wpsm_sanitize_settings', $sanitized, $input );
    }

    // Field render metodları...
    public function render_title_separator_field() { /* select ile |, -, –, », / */ }
    public function render_home_title_field() { /* input + char counter */ }
    public function render_google_verification_field() { /* input */ }
    public function render_facebook_app_id_field() { /* input */ }
    public function render_twitter_site_field() { /* input with @ prefix */ }
    public function render_default_og_image_field() { /* media uploader */ }
    public function render_twitter_card_type_field() { /* select */ }
}`,
  },
  {
    id: 'general-view',
    name: 'settings-general.php',
    path: 'includes/Admin/views/settings-general.php',
    description: 'Genel ayarlar view - Title separator, verification, robots.txt',
    language: 'php',
    code: `<?php
/**
 * Genel Ayarlar Sayfası View
 *
 * @var \\WPSM\\Class_Options $options
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Mevcut değerler
$title_separator     = $options->get( 'title_separator', '|' );
$home_title          = $options->get( 'home_title', '' );
$home_description    = $options->get( 'home_description', '' );
$google_verification = $options->get( 'google_verification', '' );
$bing_verification   = $options->get( 'bing_verification', '' );
$robots_txt          = $options->get( 'robots_txt', '' );

// Kaydetme işlemi
$message = '';
if ( isset( $_POST['wpsm_save_general'] ) ) {
    // Nonce kontrolü
    if ( ! isset( $_POST['wpsm_nonce'] ) ||
         ! wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        $message = '<div class="notice notice-error"><p>' .
            esc_html__( 'Güvenlik doğrulaması başarısız.', 'wp-seo-master' ) .
            '</p></div>';
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        $message = '<div class="notice notice-error"><p>' .
            esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) .
            '</p></div>';
    } else {
        $data = array();
        if ( isset( $_POST['wpsm_settings'] ) ) {
            foreach ( $_POST['wpsm_settings'] as $key => $value ) {
                $key = sanitize_text_field( $key );
                $data[ $key ] = sanitize_text_field( $value );
            }
        }
        $options->update( $data );
        $message = '<div class="notice notice-success is-dismissible"><p>' .
            esc_html__( 'Ayarlar kaydedildi.', 'wp-seo-master' ) .
            '</p></div>';
    }
}

$default_robots = "User-agent: *\\nAllow: /\\n\\nSitemap: " . home_url( '/sitemap.xml' );
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e( 'Genel Ayarlar', 'wp-seo-master' ); ?>
    </h1>

    <?php echo $message; // phpcs:ignore ?>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=wp-seo-master-settings&tab=general" class="nav-tab nav-tab-active">
            <?php esc_html_e( 'Genel', 'wp-seo-master' ); ?>
        </a>
        <a href="?page=wp-seo-master-settings&tab=schema" class="nav-tab">
            <?php esc_html_e( 'Şema', 'wp-seo-master' ); ?>
        </a>
        <a href="?page=wp-seo-master-settings&tab=social" class="nav-tab">
            <?php esc_html_e( 'Sosyal Medya', 'wp-seo-master' ); ?>
        </a>
        <a href="?page=wp-seo-master-settings&tab=sitemap" class="nav-tab">
            <?php esc_html_e( 'Sitemap', 'wp-seo-master' ); ?>
        </a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_nonce' ); ?>

        <!-- Title Separator -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Başlık Ayırıcı', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="wpsm-title-separator"><?php esc_html_e( 'Ayırıcı', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <select name="wpsm_settings[title_separator]" id="wpsm-title-separator">
                                <?php foreach ( array('|'=>'|','-'=>'-','–'=>'–','»'=>'»','/'=>'/') as $v => $l ) : ?>
                                    <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $title_separator, $v ); ?>>
                                        <?php echo esc_html( $l ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__( 'Örnek: %s', 'wp-seo-master' ),
                                    '<strong>' . esc_html( get_bloginfo( 'name' ) ) . ' ' .
                                    esc_html( $title_separator ) . ' ' .
                                    esc_html__( 'Sayfa Başlığı', 'wp-seo-master' ) . '</strong>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Home SEO -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Ana Sayfa SEO', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="wpsm-home-title"><?php esc_html_e( 'Başlık', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <input type="text" name="wpsm_settings[home_title]" id="wpsm-home-title"
                                value="<?php echo esc_attr( $home_title ); ?>"
                                class="regular-text wpsm-char-input" data-max="60"
                                placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                            />
                            <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/60</span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpsm-home-desc"><?php esc_html_e( 'Açıklama', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <textarea name="wpsm_settings[home_description]" id="wpsm-home-desc"
                                class="large-text wpsm-char-input" data-max="160" rows="3"
                            ><?php echo esc_textarea( $home_description ); ?></textarea>
                            <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/160</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Verification Codes -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Webmaster Doğrulama', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="wpsm-google-v"><?php esc_html_e( 'Google', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <input type="text" name="wpsm_settings[google_verification]" id="wpsm-google-v"
                                value="<?php echo esc_attr( $google_verification ); ?>" class="regular-text"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpsm-bing-v"><?php esc_html_e( 'Bing', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <input type="text" name="wpsm_settings[bing_verification]" id="wpsm-bing-v"
                                value="<?php echo esc_attr( $bing_verification ); ?>" class="regular-text"
                            />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Robots.txt -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Robots.txt', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="wpsm-robots-txt"><?php esc_html_e( 'İçerik', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <textarea name="wpsm_settings[robots_txt]" id="wpsm-robots-txt"
                                class="large-text code" rows="10"
                            ><?php echo esc_textarea( ! empty( $robots_txt ) ? $robots_txt : $default_robots ); ?></textarea>
                            <button type="button" class="button" id="wpsm-reset-robots"
                                data-default="<?php echo esc_attr( $default_robots ); ?>"
                            >
                                <?php esc_html_e( 'Varsayılana Dön', 'wp-seo-master' ); ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="wpsm_save_general" class="button-primary"
                value="<?php esc_attr_e( 'Kaydet', 'wp-seo-master' ); ?>"
            />
        </p>
    </form>
</div>`,
  },
  {
    id: 'social-view',
    name: 'settings-social.php',
    path: 'includes/Admin/views/settings-social.php',
    description: 'Sosyal medya view - Facebook, Twitter, OG görsel, card tipi',
    language: 'php',
    code: `<?php
/**
 * Sosyal Medya Ayarları Sayfası View
 *
 * @var \\WPSM\\Class_Options $options
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Mevcut değerler
$facebook_app_id   = $options->get( 'facebook_app_id', '' );
$twitter_site      = $options->get( 'twitter_site', '' );
$default_og_image  = $options->get( 'default_og_image', '' );
$twitter_card_type = $options->get( 'twitter_card_type', 'summary_large_image' );
$enable_opengraph  = $options->get( 'enable_opengraph', true );
$enable_twitter    = $options->get( 'enable_twitter', true );

// Kaydetme
$message = '';
if ( isset( $_POST['wpsm_save_social'] ) ) {
    if ( ! isset( $_POST['wpsm_nonce'] ) ||
         ! wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        $message = '<div class="notice notice-error"><p>' .
            esc_html__( 'Doğrulama başarısız.', 'wp-seo-master' ) . '</p></div>';
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        $message = '<div class="notice notice-error"><p>' .
            esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) . '</p></div>';
    } else {
        $data = array();
        if ( isset( $_POST['wpsm_settings'] ) ) {
            foreach ( $_POST['wpsm_settings'] as $key => $value ) {
                $key = sanitize_text_field( $key );
                if ( strpos( $key, 'twitter' ) !== false ) {
                    $value = ltrim( sanitize_text_field( $value ), '@' );
                } elseif ( 'default_og_image' === $key ) {
                    $value = esc_url_raw( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
                $data[ $key ] = $value;
            }
        }
        // Checkbox'lar
        $data['enable_opengraph'] = isset( $_POST['wpsm_settings']['enable_opengraph'] );
        $data['enable_twitter']   = isset( $_POST['wpsm_settings']['enable_twitter'] );

        $options->update( $data );
        $message = '<div class="notice notice-success is-dismissible"><p>' .
            esc_html__( 'Ayarlar kaydedildi.', 'wp-seo-master' ) . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-share"></span>
        <?php esc_html_e( 'Sosyal Medya Ayarları', 'wp-seo-master' ); ?>
    </h1>

    <?php echo $message; // phpcs:ignore ?>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=wp-seo-master-settings&tab=general" class="nav-tab">Genel</a>
        <a href="?page=wp-seo-master-settings&tab=schema" class="nav-tab">Şema</a>
        <a href="?page=wp-seo-master-settings&tab=social" class="nav-tab nav-tab-active">Sosyal Medya</a>
        <a href="?page=wp-seo-master-settings&tab=sitemap" class="nav-tab">Sitemap</a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_nonce' ); ?>

        <!-- Facebook / Open Graph -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Facebook / Open Graph', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'OG Etkin', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_opengraph]" value="1"
                                    <?php checked( $enable_opengraph ); ?>
                                />
                                <?php esc_html_e( 'Open Graph meta etiketlerini etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpsm-fb-app-id"><?php esc_html_e( 'Facebook App ID', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <input type="text" name="wpsm_settings[facebook_app_id]" id="wpsm-fb-app-id"
                                value="<?php echo esc_attr( $facebook_app_id ); ?>" class="regular-text"
                            />
                            <p class="description">
                                <?php
                                printf(
                                    wp_kses(
                                        __( '<a href="%s" target="_blank">Facebook Developers</a> sayfasından alabilirsiniz.', 'wp-seo-master' ),
                                        array( 'a' => array( 'href' => array(), 'target' => array() ) )
                                    ),
                                    esc_url( 'https://developers.facebook.com/' )
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Varsayılan OG Görseli', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <div class="wpsm-media-upload-wrapper">
                                <input type="hidden" name="wpsm_settings[default_og_image]"
                                    value="<?php echo esc_url( $default_og_image ); ?>"
                                />
                                <div class="wpsm-media-preview" <?php echo empty( $default_og_image ) ? 'style="display:none;"' : ''; ?>>
                                    <img src="<?php echo esc_url( $default_og_image ); ?>" alt="OG" />
                                </div>
                                <button type="button" class="button wpsm-media-upload-btn">
                                    <?php esc_html_e( 'Görsel Seç', 'wp-seo-master' ); ?>
                                </button>
                                <button type="button" class="button wpsm-media-remove-btn"
                                    <?php echo empty( $default_og_image ) ? 'style="display:none;"' : ''; ?>
                                >
                                    <?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Önerilen: 1200 x 630px', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Twitter -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Twitter / X Cards', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Twitter Cards', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_twitter]" value="1"
                                    <?php checked( $enable_twitter ); ?>
                                />
                                <?php esc_html_e( 'Twitter Card etiketlerini etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpsm-twitter-site"><?php esc_html_e( 'Twitter Kullanıcı Adı', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span>@</span>
                                <input type="text" name="wpsm_settings[twitter_site]" id="wpsm-twitter-site"
                                    value="<?php echo esc_attr( $twitter_site ); ?>" class="regular-text"
                                    placeholder="kullaniciadi"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpsm-twitter-card-type"><?php esc_html_e( 'Card Türü', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <select name="wpsm_settings[twitter_card_type]" id="wpsm-twitter-card-type">
                                <option value="summary" <?php selected( $twitter_card_type, 'summary' ); ?>>
                                    <?php esc_html_e( 'Özet', 'wp-seo-master' ); ?>
                                </option>
                                <option value="summary_large_image" <?php selected( $twitter_card_type, 'summary_large_image' ); ?>>
                                    <?php esc_html_e( 'Büyük Görsel', 'wp-seo-master' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="wpsm_save_social" class="button-primary"
                value="<?php esc_attr_e( 'Kaydet', 'wp-seo-master' ); ?>"
            />
        </p>
    </form>
</div>`,
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
        <div className="text-center mb-12">
          <span className="inline-block px-3 py-1 text-xs font-medium bg-orange-500/10 text-orange-400 rounded-full border border-orange-500/20 mb-4">
            KAYNAK KOD
          </span>
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            Admin Arayüzü Dosyaları
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            WordPress admin paneli entegrasyonu: Menü yapısı, Settings API, tab navigasyonu,
            postbox form yapısı. Tüm çıktılar escape edilmiş, nonce korumalı.
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
        <div className="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

        {/* Admin UI Preview */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-6">Admin Panel Önizleme</h3>
          
          {/* Simulated WP Admin */}
          <div className="bg-[#f0f0f1] rounded-lg overflow-hidden border border-gray-300">
            {/* WP Admin Header */}
            <div className="bg-[#1d2327] px-4 py-2 flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <span className="text-white text-sm font-medium">WordPress</span>
              </div>
              <div className="flex items-center space-x-3">
                <span className="text-gray-400 text-xs">admin</span>
              </div>
            </div>

            <div className="flex">
              {/* Sidebar */}
              <div className="w-48 bg-[#1d2327] min-h-[400px] p-2 hidden sm:block">
                {[
                  { icon: '📊', label: 'Dashboard' },
                  { icon: '📝', label: 'Yazılar' },
                  { icon: '📄', label: 'Sayfalar' },
                  { icon: '📈', label: 'WP SEO Master', active: true },
                  { icon: '🎨', label: 'Görünüm' },
                  { icon: '🔌', label: 'Eklentiler' },
                  { icon: '⚙️', label: 'Ayarlar' },
                ].map((item, i) => (
                  <div
                    key={i}
                    className={`flex items-center space-x-2 px-3 py-2 rounded text-sm ${
                      item.active
                        ? 'bg-[#2271b1] text-white'
                        : 'text-gray-300 hover:bg-[#2c3338]'
                    }`}
                  >
                    <span>{item.icon}</span>
                    <span>{item.label}</span>
                  </div>
                ))}

                {/* Submenu */}
                <div className="ml-4 mt-1 space-y-1">
                  {['Dashboard', 'Genel Ayarlar', 'Şema', 'Sosyal Medya', 'Sitemap', 'İçerik Analizi'].map((sub, i) => (
                    <div
                      key={i}
                      className={`text-xs px-2 py-1 rounded ${
                        i === 1 ? 'bg-white/10 text-white' : 'text-gray-400'
                      }`}
                    >
                      {sub}
                    </div>
                  ))}
                </div>
              </div>

              {/* Main Content */}
              <div className="flex-1 p-4">
                <h1 className="text-xl font-semibold text-[#1d2327] mb-4">Genel Ayarlar</h1>

                {/* Tabs */}
                <div className="flex border-b border-gray-300 mb-4">
                  {['Genel', 'Şema', 'Sosyal Medya', 'Sitemap'].map((tab, i) => (
                    <div
                      key={i}
                      className={`px-4 py-2 text-sm border-b-2 ${
                        i === 0
                          ? 'border-[#2271b1] text-[#1d2327] bg-white -mb-px'
                          : 'border-transparent text-gray-500'
                      }`}
                    >
                      {tab}
                    </div>
                  ))}
                </div>

                {/* Postbox */}
                <div className="bg-white border border-gray-300 rounded-sm mb-4">
                  <div className="border-b border-gray-200 px-4 py-2 bg-white">
                    <h2 className="text-sm font-semibold text-[#1d2327]">Başlık Ayırıcı</h2>
                  </div>
                  <div className="p-4">
                    <div className="flex items-center space-x-3">
                      <select className="border border-gray-300 rounded px-3 py-1.5 text-sm">
                        <option>| (Pipe)</option>
                        <option>- (Tire)</option>
                        <option>» (Sağ Ok)</option>
                      </select>
                      <span className="text-xs text-gray-500">Örnek: Site Adı | Sayfa Başlığı</span>
                    </div>
                  </div>
                </div>

                <div className="bg-white border border-gray-300 rounded-sm mb-4">
                  <div className="border-b border-gray-200 px-4 py-2">
                    <h2 className="text-sm font-semibold text-[#1d2327]">Ana Sayfa SEO</h2>
                  </div>
                  <div className="p-4 space-y-3">
                    <div>
                      <label className="text-xs font-medium text-[#1d2327] block mb-1">Başlık</label>
                      <input type="text" className="w-full max-w-md border border-gray-300 rounded px-3 py-1.5 text-sm" placeholder="Site Başlığı" />
                      <span className="text-xs text-green-600 ml-2">0/60</span>
                    </div>
                    <div>
                      <label className="text-xs font-medium text-[#1d2327] block mb-1">Açıklama</label>
                      <textarea className="w-full max-w-md border border-gray-300 rounded px-3 py-1.5 text-sm h-16" placeholder="Meta açıklaması..." />
                      <span className="text-xs text-green-600 ml-2">0/160</span>
                    </div>
                  </div>
                </div>

                <div className="flex justify-end">
                  <button className="bg-[#2271b1] text-white px-4 py-1.5 rounded text-sm hover:bg-[#135e96]">
                    Değişiklikleri Kaydet
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: '6 Menü Sayfası', desc: 'Dashboard, Genel, Şema, Sosyal, Sitemap, Analiz' },
            { title: 'Settings API', desc: 'register_setting, sections, fields' },
            { title: 'Tab Navigasyon', desc: '?tab=general|schema|social|sitemap' },
            { title: 'Postbox Yapısı', desc: 'WordPress native admin UI' },
            { title: 'Nonce Koruması', desc: 'wpsm_save_settings nonce' },
            { title: 'Capability Check', desc: 'manage_options yetki kontrolü' },
            { title: 'Media Uploader', desc: 'wp.media ile görsel seçimi' },
            { title: 'Karakter Sayacı', desc: 'Title: 60, Desc: 160 (renk kodlu)' },
          ].map((item, i) => (
            <div key={i} className="p-4 bg-white/[0.02] border border-white/5 rounded-xl">
              <p className="text-sm font-medium text-purple-300">{item.title}</p>
              <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
