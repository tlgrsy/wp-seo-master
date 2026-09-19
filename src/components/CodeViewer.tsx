import { useState } from 'react'

const files = [
  {
    id: 'metabox',
    name: 'class-metabox.php',
    path: 'includes/Admin/class-metabox.php',
    description: 'SEO Metabox sınıfı - 4 sekme, AJAX schema, REST API, Gutenberg uyumlu',
    language: 'php',
    code: `<?php
/**
 * SEO Metabox Sınıfı
 *
 * Post/Page/CPT edit ekranlarında SEO meta alanlarını gösteren metabox.
 * Tab yapısı: İçerik, Sosyal, Şema, Gelişmiş
 *
 * @package WPSM\\Admin
 * @since 1.0.0
 */

namespace WPSM\\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Metabox {

    private $options;

    const METABOX_ID   = 'wpsm-seo-metabox';
    const NONCE_ACTION = 'wpsm_metabox_save';
    const NONCE_NAME   = 'wpsm_metabox_nonce';

    const META_KEYS = array(
        '_wpsm_title', '_wpsm_description', '_wpsm_focus_keyword',
        '_wpsm_canonical', '_wpsm_og_title', '_wpsm_og_description',
        '_wpsm_og_image', '_wpsm_twitter_title', '_wpsm_twitter_description',
        '_wpsm_twitter_image', '_wpsm_schema_type', '_wpsm_schema_data',
        '_wpsm_robots', '_wpsm_breadcrumb_title',
    );

    public function __construct( $options ) {
        $this->options = $options;
        add_action( 'wp_ajax_wpsm_load_schema_fields', array( $this, 'ajax_load_schema_fields' ) );
    }

    /**
     * Metabox'ları kaydet
     * context: normal, priority: high
     */
    public function add_meta_boxes() {
        $post_types = $this->get_supported_post_types();

        foreach ( $post_types as $post_type ) {
            add_meta_box(
                self::METABOX_ID,
                __( 'WP SEO Master', 'wp-seo-master' ),
                array( $this, 'render_metabox' ),
                $post_type,
                'normal',
                'high'
            );
        }

        // Gutenberg uyumluluğu - REST API meta kaydı
        $this->register_post_meta();
    }

    /**
     * Post meta alanlarını REST API'ye kaydet
     * show_in_rest=true, single=true, auth_callback ile yetki kontrolü
     */
    private function register_post_meta() {
        $post_types = $this->get_supported_post_types();

        $string_fields = array(
            '_wpsm_title', '_wpsm_description', '_wpsm_focus_keyword',
            '_wpsm_canonical', '_wpsm_og_title', '_wpsm_og_description',
            '_wpsm_og_image', '_wpsm_twitter_title', '_wpsm_twitter_description',
            '_wpsm_twitter_image', '_wpsm_schema_type', '_wpsm_breadcrumb_title',
        );

        foreach ( $post_types as $post_type ) {
            foreach ( $string_fields as $key ) {
                register_post_meta( $post_type, $key, array(
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => 'string',
                    'auth_callback'     => array( $this, 'meta_auth_callback' ),
                    'sanitize_callback' => 'sanitize_text_field',
                ));
            }

            // Schema Data (object)
            register_post_meta( $post_type, '_wpsm_schema_data', array(
                'show_in_rest'  => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => new \\stdClass(),
                    ),
                ),
                'single'        => true,
                'type'          => 'object',
                'auth_callback' => array( $this, 'meta_auth_callback' ),
            ));

            // Robots (array)
            register_post_meta( $post_type, '_wpsm_robots', array(
                'show_in_rest'  => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array( 'type' => 'string' ),
                    ),
                ),
                'single'        => true,
                'type'          => 'array',
                'auth_callback' => array( $this, 'meta_auth_callback' ),
            ));
        }
    }

    /**
     * Meta yetki kontrolü
     */
    public function meta_auth_callback( $allowed, $meta_key, $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    }

    /**
     * Metabox içeriğini render et
     */
    public function render_metabox( $post ) {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

        $post_id = $post->ID;
        $meta    = $this->get_all_meta( $post_id );

        // Template'e instance'ı gönder ($this yerine $wpsm_metabox)
        $wpsm_metabox = $this;

        include WPSM_INCLUDES_PATH . 'Admin/views/metabox.php';
    }

    /**
     * save_post hook'u ile kaydetme
     */
    public function save_meta( $post_id, $post ) {
        // 1. Nonce kontrolü
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) return;
        if ( ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) return;

        // 2. Autosave kontrolü
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // 3. Revision kontrolü
        if ( wp_is_post_revision( $post_id ) ) return;

        // 4. Yetki kontrolü
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Meta alanlarını kaydet (sanitize ile)
        if ( isset( $_POST['wpsm_title'] ) ) {
            update_post_meta( $post_id, '_wpsm_title',
                sanitize_text_field( wp_unslash( $_POST['wpsm_title'] ) )
            );
        }

        if ( isset( $_POST['wpsm_description'] ) ) {
            update_post_meta( $post_id, '_wpsm_description',
                sanitize_textarea_field( wp_unslash( $_POST['wpsm_description'] ) )
            );
        }

        // Schema Type
        if ( isset( $_POST['wpsm_schema_type'] ) ) {
            $allowed = array( 'none', 'article', 'faq', 'howto', 'product', 'localbusiness' );
            $type = sanitize_text_field( wp_unslash( $_POST['wpsm_schema_type'] ) );
            if ( in_array( $type, $allowed, true ) ) {
                update_post_meta( $post_id, '_wpsm_schema_type', $type );
            }
        }

        // Schema Data (JSON)
        if ( isset( $_POST['wpsm_schema_data'] ) && is_array( $_POST['wpsm_schema_data'] ) ) {
            $sanitized = $this->sanitize_schema_data( wp_unslash( $_POST['wpsm_schema_data'] ) );
            update_post_meta( $post_id, '_wpsm_schema_data', wp_json_encode( $sanitized ) );
        }

        // Robots (checkbox array)
        $robots = array();
        $allowed_robots = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );
        if ( isset( $_POST['wpsm_robots'] ) && is_array( $_POST['wpsm_robots'] ) ) {
            foreach ( wp_unslash( $_POST['wpsm_robots'] ) as $robot ) {
                $robot = sanitize_text_field( $robot );
                if ( in_array( $robot, $allowed_robots, true ) ) {
                    $robots[] = $robot;
                }
            }
        }
        update_post_meta( $post_id, '_wpsm_robots', $robots );
    }

    /**
     * AJAX: Schema tipine göre alanları yükle
     */
    public function ajax_load_schema_fields() {
        check_ajax_referer( 'wpsm_metabox_save', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'wp-seo-master' ) ) );
        }

        $schema_type = isset( $_POST['schema_type'] )
            ? sanitize_text_field( wp_unslash( $_POST['schema_type'] ) )
            : '';

        $allowed = array( 'article', 'faq', 'howto', 'product', 'localbusiness' );
        if ( ! in_array( $schema_type, $allowed, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz tür.', 'wp-seo-master' ) ) );
        }

        ob_start();
        $this->render_schema_fields( $schema_type, array() );
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html, 'type' => $schema_type ) );
    }

    /**
     * Schema alanlarını render et
     */
    public function render_schema_fields( $schema_type, $schema_data = array() ) {
        switch ( $schema_type ) {
            case 'article':
                $this->render_article_schema_fields( $schema_data );
                break;
            case 'faq':
                $this->render_faq_schema_fields( $schema_data );
                break;
            case 'howto':
                $this->render_howto_schema_fields( $schema_data );
                break;
            case 'product':
                $this->render_product_schema_fields( $schema_data );
                break;
            case 'localbusiness':
                $this->render_localbusiness_schema_fields( $schema_data );
                break;
        }
    }

    // Schema field render metodları...
    private function render_article_schema_fields( $data ) { /* ... */ }
    private function render_faq_schema_fields( $data ) { /* ... */ }
    private function render_howto_schema_fields( $data ) { /* ... */ }
    private function render_product_schema_fields( $data ) { /* ... */ }
    private function render_localbusiness_schema_fields( $data ) { /* ... */ }

    private function sanitize_schema_data( $data ) {
        $sanitized = array();
        foreach ( $data as $key => $value ) {
            $key = sanitize_text_field( $key );
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_schema_data( $value );
            } elseif ( is_string( $value ) ) {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }
        return $sanitized;
    }

    private function get_supported_post_types() {
        $post_types = get_post_types( array( 'public' => true ) );
        unset( $post_types['attachment'] );
        return apply_filters( 'wpsm_supported_post_types', $post_types );
    }

    private function get_all_meta( $post_id ) {
        $meta = array();
        foreach ( self::META_KEYS as $key ) {
            $value = get_post_meta( $post_id, $key, true );
            if ( '_wpsm_schema_data' === $key && ! empty( $value ) ) {
                $decoded = json_decode( $value, true );
                $value = is_array( $decoded ) ? $decoded : array();
            }
            if ( '_wpsm_robots' === $key && ! empty( $value ) ) {
                $value = is_array( $value ) ? $value : array( $value );
            }
            $meta[ $key ] = $value;
        }
        return $meta;
    }
}`,
  },
  {
    id: 'view',
    name: 'views/metabox.php',
    path: 'includes/Admin/views/metabox.php',
    description: 'Metabox template - 4 sekme, SERP preview, media uploader, schema repeater',
    language: 'php',
    code: `<?php
/**
 * SEO Metabox Template
 *
 * @var int         $post_id      Post ID
 * @var array       $meta         Meta değerleri
 * @var object      $post         WP_Post objesi
 * @var Class_Metabox $wpsm_metabox Metabox instance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Meta değerlerini değişkenlere ata
$title         = isset( $meta['_wpsm_title'] ) ? $meta['_wpsm_title'] : '';
$description   = isset( $meta['_wpsm_description'] ) ? $meta['_wpsm_description'] : '';
$focus_keyword = isset( $meta['_wpsm_focus_keyword'] ) ? $meta['_wpsm_focus_keyword'] : '';
$canonical     = isset( $meta['_wpsm_canonical'] ) ? $meta['_wpsm_canonical'] : '';
$og_title      = isset( $meta['_wpsm_og_title'] ) ? $meta['_wpsm_og_title'] : '';
$og_description = isset( $meta['_wpsm_og_description'] ) ? $meta['_wpsm_og_description'] : '';
$og_image      = isset( $meta['_wpsm_og_image'] ) ? $meta['_wpsm_og_image'] : '';
$twitter_title = isset( $meta['_wpsm_twitter_title'] ) ? $meta['_wpsm_twitter_title'] : '';
$twitter_description = isset( $meta['_wpsm_twitter_description'] ) ? $meta['_wpsm_twitter_description'] : '';
$twitter_image = isset( $meta['_wpsm_twitter_image'] ) ? $meta['_wpsm_twitter_image'] : '';
$schema_type   = isset( $meta['_wpsm_schema_type'] ) ? $meta['_wpsm_schema_type'] : 'none';
$schema_data   = isset( $meta['_wpsm_schema_data'] ) ? $meta['_wpsm_schema_data'] : array();
$robots        = isset( $meta['_wpsm_robots'] ) ? $meta['_wpsm_robots'] : array();
$breadcrumb_title = isset( $meta['_wpsm_breadcrumb_title'] ) ? $meta['_wpsm_breadcrumb_title'] : '';
$schema_data_json = ! empty( $schema_data ) ? wp_json_encode( $schema_data ) : '{}';
?>

<div class="wpsm-metabox-wrapper">
    <!-- Tab Navigation -->
    <nav class="wpsm-tabs-nav">
        <button type="button" class="wpsm-tab-btn active" data-tab="content">
            <span class="dashicons dashicons-edit"></span>
            <?php esc_html_e( 'İçerik', 'wp-seo-master' ); ?>
        </button>
        <button type="button" class="wpsm-tab-btn" data-tab="social">
            <span class="dashicons dashicons-share"></span>
            <?php esc_html_e( 'Sosyal', 'wp-seo-master' ); ?>
        </button>
        <button type="button" class="wpsm-tab-btn" data-tab="schema">
            <span class="dashicons dashicons-editor-code"></span>
            <?php esc_html_e( 'Şema', 'wp-seo-master' ); ?>
        </button>
        <button type="button" class="wpsm-tab-btn" data-tab="advanced">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e( 'Gelişmiş', 'wp-seo-master' ); ?>
        </button>
    </nav>

    <div class="wpsm-tabs-content">

        <!-- SEKME 1: İÇERİK -->
        <div class="wpsm-tab-panel active" id="wpsm-tab-content">
            <!-- SEO Title (60 karakter sayacı) -->
            <div class="wpsm-field-group">
                <label for="wpsm-title" class="wpsm-field-label">
                    <?php esc_html_e( 'SEO Başlığı', 'wp-seo-master' ); ?>
                    <span class="wpsm-char-counter" data-target="wpsm-title" data-max="60">
                        <span class="wpsm-char-count">0</span>/60
                    </span>
                </label>
                <input type="text" id="wpsm-title" name="wpsm_title"
                    value="<?php echo esc_attr( $title ); ?>"
                    class="wpsm-input wpsm-char-input"
                    placeholder="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                />
            </div>

            <!-- Meta Description (160 karakter sayacı) -->
            <div class="wpsm-field-group">
                <label for="wpsm-description" class="wpsm-field-label">
                    <?php esc_html_e( 'Meta Açıklaması', 'wp-seo-master' ); ?>
                    <span class="wpsm-char-counter" data-target="wpsm-description" data-max="160">
                        <span class="wpsm-char-count">0</span>/160
                    </span>
                </label>
                <textarea id="wpsm-description" name="wpsm_description"
                    class="wpsm-textarea wpsm-char-input" rows="3"
                ><?php echo esc_textarea( $description ); ?></textarea>
            </div>

            <!-- Focus Keyword -->
            <div class="wpsm-field-group">
                <label for="wpsm-focus-keyword" class="wpsm-field-label">
                    <?php esc_html_e( 'Odak Anahtar Kelime', 'wp-seo-master' ); ?>
                </label>
                <input type="text" id="wpsm-focus-keyword" name="wpsm_focus_keyword"
                    value="<?php echo esc_attr( $focus_keyword ); ?>" class="wpsm-input"
                />
            </div>

            <!-- Canonical URL -->
            <div class="wpsm-field-group">
                <label for="wpsm-canonical" class="wpsm-field-label">
                    <?php esc_html_e( 'Canonical URL', 'wp-seo-master' ); ?>
                </label>
                <input type="url" id="wpsm-canonical" name="wpsm_canonical"
                    value="<?php echo esc_url( $canonical ); ?>" class="wpsm-input"
                    placeholder="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                />
            </div>

            <!-- SERP Önizleme -->
            <div class="wpsm-serp-preview">
                <div class="wpsm-serp-title" id="wpsm-serp-title-preview">
                    <?php echo esc_html( ! empty( $title ) ? $title : get_the_title( $post_id ) ); ?>
                </div>
                <div class="wpsm-serp-url"><?php echo esc_url( get_permalink( $post_id ) ); ?></div>
                <div class="wpsm-serp-desc" id="wpsm-serp-desc-preview">
                    <?php echo esc_html( ! empty( $description ) ? $description : 'Meta açıklamanız...' ); ?>
                </div>
            </div>
        </div>

        <!-- SEKME 2: SOSYAL -->
        <div class="wpsm-tab-panel" id="wpsm-tab-social">
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Open Graph', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-og-title" class="wpsm-field-label">OG Başlığı</label>
                <input type="text" id="wpsm-og-title" name="wpsm_og_title"
                    value="<?php echo esc_attr( $og_title ); ?>" class="wpsm-input"
                />
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-og-description" class="wpsm-field-label">OG Açıklaması</label>
                <textarea id="wpsm-og-description" name="wpsm_og_description"
                    class="wpsm-textarea" rows="2"
                ><?php echo esc_textarea( $og_description ); ?></textarea>
            </div>

            <!-- OG Image (Media Uploader) -->
            <div class="wpsm-field-group">
                <label class="wpsm-field-label">OG Görseli</label>
                <div class="wpsm-media-upload-wrapper" data-field="wpsm-og-image">
                    <input type="hidden" id="wpsm-og-image" name="wpsm_og_image"
                        value="<?php echo esc_url( $og_image ); ?>"
                    />
                    <div class="wpsm-media-preview" <?php echo empty( $og_image ) ? 'style="display:none;"' : ''; ?>>
                        <img src="<?php echo esc_url( $og_image ); ?>" alt="OG" />
                    </div>
                    <button type="button" class="button wpsm-media-upload-btn">Görsel Seç</button>
                    <button type="button" class="button wpsm-media-remove-btn"
                        <?php echo empty( $og_image ) ? 'style="display:none;"' : ''; ?>>Kaldır</button>
                </div>
            </div>

            <!-- Twitter alanları -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Twitter Cards', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-twitter-title" class="wpsm-field-label">Twitter Başlığı</label>
                <input type="text" id="wpsm-twitter-title" name="wpsm_twitter_title"
                    value="<?php echo esc_attr( $twitter_title ); ?>" class="wpsm-input"
                />
            </div>

            <div class="wpsm-field-group">
                <label class="wpsm-field-label">Twitter Görseli</label>
                <div class="wpsm-media-upload-wrapper" data-field="wpsm-twitter-image">
                    <input type="hidden" id="wpsm-twitter-image" name="wpsm_twitter_image"
                        value="<?php echo esc_url( $twitter_image ); ?>"
                    />
                    <div class="wpsm-media-preview" <?php echo empty( $twitter_image ) ? 'style="display:none;"' : ''; ?>>
                        <img src="<?php echo esc_url( $twitter_image ); ?>" alt="Twitter" />
                    </div>
                    <button type="button" class="button wpsm-media-upload-btn">Görsel Seç</button>
                    <button type="button" class="button wpsm-media-remove-btn"
                        <?php echo empty( $twitter_image ) ? 'style="display:none;"' : ''; ?>>Kaldır</button>
                </div>
            </div>
        </div>

        <!-- SEKME 3: ŞEMA -->
        <div class="wpsm-tab-panel" id="wpsm-tab-schema">
            <div class="wpsm-field-group">
                <label for="wpsm-schema-type" class="wpsm-field-label">Schema Türü</label>
                <select id="wpsm-schema-type" name="wpsm_schema_type" class="wpsm-select">
                    <option value="none" <?php selected( $schema_type, 'none' ); ?>>— Seçiniz —</option>
                    <option value="article" <?php selected( $schema_type, 'article' ); ?>>Article</option>
                    <option value="faq" <?php selected( $schema_type, 'faq' ); ?>>FAQ</option>
                    <option value="howto" <?php selected( $schema_type, 'howto' ); ?>>HowTo</option>
                    <option value="product" <?php selected( $schema_type, 'product' ); ?>>Product</option>
                    <option value="localbusiness" <?php selected( $schema_type, 'localbusiness' ); ?>>LocalBusiness</option>
                </select>
            </div>

            <!-- Dinamik Schema Alanları (AJAX ile yüklenir) -->
            <div id="wpsm-schema-fields" class="wpsm-schema-fields-wrapper">
                <div class="wpsm-schema-loader" style="display:none;">
                    <span class="spinner is-active"></span> Yükleniyor...
                </div>
                <div class="wpsm-schema-dynamic-fields"
                    data-schema-type="<?php echo esc_attr( $schema_type ); ?>">
                    <?php
                    if ( 'none' !== $schema_type && ! empty( $schema_type ) ) {
                        // $wpsm_metabox = Class_Metabox instance
                        if ( isset( $wpsm_metabox ) && is_object( $wpsm_metabox ) ) {
                            $wpsm_metabox->render_schema_fields( $schema_type, $schema_data );
                        }
                    } else {
                        echo '<div class="wpsm-schema-empty"><p>' .
                            esc_html__( 'Bir schema türü seçerek ilgili alanları görüntüleyin.', 'wp-seo-master' ) .
                            '</p></div>';
                    }
                    ?>
                </div>
            </div>

            <input type="hidden" id="wpsm-schema-data-json" name="wpsm_schema_data"
                value="<?php echo esc_attr( $schema_data_json ); ?>"
            />
        </div>

        <!-- SEKME 4: GELİŞMİŞ -->
        <div class="wpsm-tab-panel" id="wpsm-tab-advanced">
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Robots Meta', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-checkbox-group">
                <?php
                $robot_options = array(
                    'noindex'      => __( 'Arama motorlarına indeksletme', 'wp-seo-master' ),
                    'nofollow'     => __( 'Bağlantıları takip ettirme', 'wp-seo-master' ),
                    'noarchive'    => __( 'Önbelleğe alma', 'wp-seo-master' ),
                    'nosnippet'    => __( 'Snippet gösterme', 'wp-seo-master' ),
                    'noimageindex' => __( 'Görselleri indeksletme', 'wp-seo-master' ),
                );
                foreach ( $robot_options as $robot_key => $robot_label ) :
                ?>
                    <label class="wpsm-checkbox-label">
                        <input type="checkbox" name="wpsm_robots[]" value="<?php echo esc_attr( $robot_key ); ?>"
                            <?php checked( in_array( $robot_key, $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong><?php echo esc_html( $robot_key ); ?></strong> —
                            <?php echo esc_html( $robot_label ); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Breadcrumb', 'wp-seo-master' ); ?></h4>
            </div>
            <div class="wpsm-field-group">
                <label for="wpsm-breadcrumb-title" class="wpsm-field-label">Breadcrumb Başlığı</label>
                <input type="text" id="wpsm-breadcrumb-title" name="wpsm_breadcrumb_title"
                    value="<?php echo esc_attr( $breadcrumb_title ); ?>" class="wpsm-input"
                    placeholder="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                />
            </div>
        </div>

    </div>
</div>`,
  },
  {
    id: 'js',
    name: 'admin.js',
    path: 'assets/js/admin.js',
    description: 'Metabox JavaScript - Karakter sayacı, tab, media uploader, AJAX schema',
    language: 'javascript',
    code: `/**
 * WP SEO Master - Admin JavaScript
 *
 * - Karakter sayacı (Title: 60, Description: 160)
 * - Tab geçişleri
 * - Media uploader (wp.media)
 * - Schema tipi değişince AJAX ile alanları getir
 * - SERP önizleme güncelleme
 * - Schema repeater (FAQ, HowTo)
 */

(function ($) {
    'use strict';

    var WPSM_Admin = {

        init: function () {
            this.initCharCounters();
            this.initTabs();
            this.initMediaUploader();
            this.initSchemaTypeChange();
            this.initSchemaRepeater();
            this.initSerpPreview();
        },

        /**
         * Karakter Sayacı
         * Renk: yeşil (<=80%), sarı (80-100%), kırmızı (>100%)
         */
        initCharCounters: function () {
            var self = this;

            $('.wpsm-char-counter').each(function () {
                var $counter = $(this);
                var targetId = $counter.data('target');
                var maxChars = parseInt($counter.data('max'), 10);
                var $input = $('#' + targetId);

                if (!$input.length) return;

                // İlk yükleme
                self.updateCharCounter($input, $counter, maxChars);

                // Input değiştiğinde güncelle
                $input.on('input keyup', function () {
                    self.updateCharCounter($(this), $counter, maxChars);
                });
            });
        },

        updateCharCounter: function ($input, $counter, maxChars) {
            var currentLength = $input.val().length;
            var $countSpan = $counter.find('.wpsm-char-count');

            $countSpan.text(currentLength);
            $counter.removeClass('wpsm-char-green wpsm-char-yellow wpsm-char-red wpsm-char-gray');

            var percentage = (currentLength / maxChars) * 100;

            if (currentLength === 0) {
                $counter.addClass('wpsm-char-gray');
            } else if (percentage <= 80) {
                $counter.addClass('wpsm-char-green');
            } else if (percentage <= 100) {
                $counter.addClass('wpsm-char-yellow');
            } else {
                $counter.addClass('wpsm-char-red');
            }
        },

        /**
         * Tab Geçişleri
         */
        initTabs: function () {
            $(document).on('click', '.wpsm-tab-btn', function (e) {
                e.preventDefault();
                var tabId = $(this).data('tab');

                $('.wpsm-tab-btn').removeClass('active');
                $(this).addClass('active');

                $('.wpsm-tab-panel').removeClass('active');
                $('#wpsm-tab-' + tabId).addClass('active');
            });
        },

        /**
         * Media Uploader (wp.media)
         */
        initMediaUploader: function () {
            var mediaFrame;

            $(document).on('click', '.wpsm-media-upload-btn', function (e) {
                e.preventDefault();

                var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
                var $input = $wrapper.find('input[type="hidden"]');
                var $preview = $wrapper.find('.wpsm-media-preview');
                var $previewImg = $preview.find('img');
                var $removeBtn = $wrapper.find('.wpsm-media-remove-btn');

                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }

                mediaFrame = wp.media({
                    title: wpsmAdmin.i18n.selectImage || 'Görsel Seç',
                    button: { text: wpsmAdmin.i18n.useImage || 'Kullan' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaFrame.on('select', function () {
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    var imageUrl = attachment.sizes && attachment.sizes.medium
                        ? attachment.sizes.medium.url
                        : attachment.url;

                    $input.val(imageUrl);
                    $previewImg.attr('src', imageUrl);
                    $preview.show();
                    $removeBtn.show();
                });

                mediaFrame.open();
            });

            // Görsel kaldır
            $(document).on('click', '.wpsm-media-remove-btn', function (e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
                $wrapper.find('input[type="hidden"]').val('');
                $wrapper.find('.wpsm-media-preview').hide();
                $(this).hide();
            });
        },

        /**
         * Schema Tipi Değişimi (AJAX)
         */
        initSchemaTypeChange: function () {
            $('#wpsm-schema-type').on('change', function () {
                var schemaType = $(this).val();
                var $fieldsWrapper = $('#wpsm-schema-fields');
                var $dynamicFields = $fieldsWrapper.find('.wpsm-schema-dynamic-fields');
                var $loader = $fieldsWrapper.find('.wpsm-schema-loader');
                var $empty = $fieldsWrapper.find('.wpsm-schema-empty');

                if (schemaType === 'none' || schemaType === '') {
                    $dynamicFields.empty().hide();
                    $fieldsWrapper.find('.wpsm-schema-empty').show();
                    return;
                }

                $empty.hide();
                $loader.show();

                $.ajax({
                    url: wpsmAdmin.ajaxUrl,
                    type: 'POST',
                     {
                        action: 'wpsm_load_schema_fields',
                        schema_type: schemaType,
                        nonce: wpsmAdmin.nonce
                    },
                    success: function (response) {
                        $loader.hide();
                        if (response.success) {
                            $dynamicFields.html(response.data.html).show();
                            $dynamicFields.attr('data-schema-type', schemaType);
                        } else {
                            $dynamicFields.html(
                                '<p class="wpsm-error">' +
                                (response.data.message || 'Hata') +
                                '</p>'
                            ).show();
                        }
                    },
                    error: function () {
                        $loader.hide();
                        $dynamicFields.html(
                            '<p class="wpsm-error">Bağlantı hatası.</p>'
                        ).show();
                    }
                });
            });
        },

        /**
         * Schema Repeater (FAQ, HowTo)
         */
        initSchemaRepeater: function () {
            // Eleman ekle
            $(document).on('click', '.wpsm-schema-add-item', function (e) {
                e.preventDefault();
                var $container = $(this).prev('.wpsm-schema-repeater');
                var type = $(this).data('type');
                var index = $container.find('.wpsm-schema-repeater-item').length;
                var html = '';

                if (type === 'faq') {
                    html = '<div class="wpsm-schema-repeater-item">' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Soru ' + (index + 1) + '</label>' +
                        '<input type="text" name="wpsm_schema_data[questions][' + index + '][question]" class="wpsm-input" />' +
                        '</div>' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Cevap</label>' +
                        '<textarea name="wpsm_schema_data[questions][' + index + '][answer]" class="wpsm-textarea" rows="3"></textarea>' +
                        '</div>' +
                        '<button type="button" class="button wpsm-schema-remove-item">Kaldır</button>' +
                        '</div>';
                } else if (type === 'howto') {
                    html = '<div class="wpsm-schema-repeater-item">' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Adım ' + (index + 1) + '</label>' +
                        '<input type="text" name="wpsm_schema_data[steps][' + index + '][name]" class="wpsm-input" />' +
                        '</div>' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Açıklama</label>' +
                        '<textarea name="wpsm_schema_data[steps][' + index + '][text]" class="wpsm-textarea" rows="2"></textarea>' +
                        '</div>' +
                        '<button type="button" class="button wpsm-schema-remove-item">Kaldır</button>' +
                        '</div>';
                }

                $container.append(html);
            });

            // Eleman kaldır
            $(document).on('click', '.wpsm-schema-remove-item', function (e) {
                e.preventDefault();
                $(this).closest('.wpsm-schema-repeater-item').remove();
            });
        },

        /**
         * SERP Önizleme
         */
        initSerpPreview: function () {
            $('#wpsm-title').on('input keyup', function () {
                var value = $(this).val();
                $('#wpsm-serp-title-preview').text(
                    value || $(this).attr('placeholder') || 'Sayfa Başlığı'
                );
            });

            $('#wpsm-description').on('input keyup', function () {
                var value = $(this).val();
                $('#wpsm-serp-desc-preview').text(
                    value || 'Meta açıklamanız burada görünecek...'
                );
            });
        }
    };

    $(document).ready(function () {
        if ($('#wpsm-seo-metabox').length) {
            WPSM_Admin.init();
        }
    });

})(jQuery);`,
  },
  {
    id: 'css',
    name: 'admin.css',
    path: 'assets/css/admin.css',
    description: 'Metabox CSS - Tab, form, karakter sayacı, SERP preview, schema, media',
    language: 'css',
    code: `/**
 * WP SEO Master - Admin CSS
 *
 * - Tab yapısı
 * - Form alanları
 * - Karakter sayacı renkleri (yeşil/sarı/kırmızı)
 * - Media uploader
 * - Schema alanları
 * - SERP önizleme
 * - Checkbox'lar
 */

/* WRAPPER */
.wpsm-metabox-wrapper {
    margin: -6px -12px -12px;
}

/* TAB NAVIGATION */
.wpsm-tabs-nav {
    display: flex;
    border-bottom: 1px solid #dcdcde;
    background: #f6f7f7;
    padding: 0;
    margin: 0;
}

.wpsm-tab-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    background: transparent;
    color: #50575e;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    margin-bottom: -1px;
}

.wpsm-tab-btn:hover {
    color: #1d2327;
    background: rgba(0, 0, 0, 0.02);
}

.wpsm-tab-btn.active {
    color: #2271b1;
    border-bottom-color: #2271b1;
    background: #fff;
}

.wpsm-tab-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* TAB CONTENT */
.wpsm-tabs-content {
    padding: 16px 20px;
    background: #fff;
}

.wpsm-tab-panel {
    display: none;
}

.wpsm-tab-panel.active {
    display: block;
}

/* FIELDS */
.wpsm-field-group {
    margin-bottom: 20px;
}

.wpsm-field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 13px;
    color: #1d2327;
    margin-bottom: 6px;
}

.wpsm-field-desc {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
    font-style: italic;
}

.wpsm-input,
.wpsm-textarea,
.wpsm-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 13px;
    line-height: 1.5;
    color: #2c3338;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.wpsm-input:focus,
.wpsm-textarea:focus,
.wpsm-select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.wpsm-textarea {
    resize: vertical;
    min-height: 60px;
}

/* CHARACTER COUNTER */
.wpsm-char-counter {
    font-size: 11px;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.wpsm-char-gray {
    color: #8c8f94;
    background: #f0f0f1;
}

.wpsm-char-green {
    color: #00a32a;
    background: #edfaef;
}

.wpsm-char-yellow {
    color: #996800;
    background: #fcf9e8;
}

.wpsm-char-red {
    color: #d63638;
    background: #fcf0f1;
}

/* SERP PREVIEW */
.wpsm-serp-preview {
    margin-top: 16px;
    padding: 16px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    font-family: Arial, sans-serif;
}

.wpsm-serp-title {
    font-size: 18px;
    color: #1a0dab;
    line-height: 1.3;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wpsm-serp-url {
    font-size: 13px;
    color: #006621;
    margin-bottom: 4px;
}

.wpsm-serp-desc {
    font-size: 13px;
    color: #4d5156;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* SECTION HEADERS */
.wpsm-section-header {
    margin: 24px 0 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #dcdcde;
}

.wpsm-section-header:first-child {
    margin-top: 0;
}

.wpsm-section-header h4 {
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
    margin: 0;
}

/* MEDIA UPLOADER */
.wpsm-media-upload-wrapper {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 10px;
}

.wpsm-media-preview {
    width: 120px;
    height: 80px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    overflow: hidden;
    background: #f6f7f7;
}

.wpsm-media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* CHECKBOX GROUP */
.wpsm-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wpsm-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    transition: background 0.2s, border-color 0.2s;
}

.wpsm-checkbox-label:hover {
    background: #f6f7f7;
    border-color: #8c8f94;
}

.wpsm-checkbox-text {
    font-size: 13px;
    color: #50575e;
    line-height: 1.5;
}

.wpsm-checkbox-text strong {
    color: #1d2327;
}

/* SCHEMA FIELDS */
.wpsm-schema-fields-wrapper {
    margin-top: 16px;
    padding: 16px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 6px;
}

.wpsm-schema-empty {
    text-align: center;
    padding: 20px;
    color: #646970;
}

.wpsm-schema-loader {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    color: #646970;
}

.wpsm-schema-field {
    margin-bottom: 14px;
}

.wpsm-schema-field:last-child {
    margin-bottom: 0;
}

.wpsm-schema-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 4px;
}

.wpsm-schema-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Schema Repeater */
.wpsm-schema-repeater {
    margin-bottom: 12px;
}

.wpsm-schema-repeater-item {
    padding: 14px;
    margin-bottom: 10px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
}

.wpsm-schema-repeater-item .wpsm-schema-remove-item {
    margin-top: 8px;
    color: #d63638 !important;
    border-color: #d63638 !important;
}

.wpsm-schema-add-item {
    margin-top: 8px;
}

/* ERROR */
.wpsm-error {
    color: #d63638;
    padding: 8px 12px;
    background: #fcf0f1;
    border: 1px solid #d63638;
    border-radius: 4px;
    font-size: 13px;
}

/* RESPONSIVE */
@media screen and (max-width: 782px) {
    .wpsm-tabs-nav {
        flex-wrap: wrap;
    }

    .wpsm-tab-btn {
        flex: 1;
        justify-content: center;
        padding: 8px 12px;
        font-size: 12px;
    }

    .wpsm-schema-row {
        grid-template-columns: 1fr;
    }

    .wpsm-media-upload-wrapper {
        flex-direction: column;
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
    } else if (language === 'javascript') {
      highlighted = highlighted
        .replace(/\/\/.*$/gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\/\*\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\b(var|let|const|function|return|if|else|for|while|this|new|typeof|true|false|null|undefined)\b/g, '<span class="text-purple-400 font-medium">$1</span>')
        .replace(/'([^'\\]*(?:\\.[^'\\]*)*)'/g, "'<span class=\"text-green-300\">$1</span>'")
    } else if (language === 'css') {
      highlighted = highlighted
        .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/(\.[\w-]+)/g, '<span class="text-yellow-300">$1</span>')
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
            Post Editör Metabox
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            4 dosya: PHP sınıfı, template, JavaScript ve CSS.
            Tab yapısı, karakter sayacı, media uploader, AJAX schema alanları,
            SERP önizleme, Gutenberg uyumlu REST API meta kaydı.
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
                <p className="text-xs text-gray-500 mt-0.5">{file.language.toUpperCase()}</p>
              </div>
              <svg className="w-5 h-5 text-gray-500 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          ))}
        </div>

        {/* Metabox Preview */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-6">Metabox Önizleme</h3>
          
          <div className="bg-[#f0f0f1] rounded-lg overflow-hidden border border-gray-300">
            {/* Metabox Header */}
            <div className="bg-white border-b border-gray-300 px-4 py-2">
              <h2 className="text-sm font-semibold text-[#1d2327]">WP SEO Master</h2>
            </div>

            {/* Tabs */}
            <div className="flex bg-[#f6f7f7] border-b border-gray-300">
              {[
                { icon: '✏️', label: 'İçerik', active: true },
                { icon: '🔗', label: 'Sosyal', active: false },
                { icon: '{ }', label: 'Şema', active: false },
                { icon: '⚙️', label: 'Gelişmiş', active: false },
              ].map((tab, i) => (
                <div
                  key={i}
                  className={`flex items-center gap-1 px-4 py-2.5 text-xs font-medium border-b-2 ${
                    tab.active
                      ? 'border-[#2271b1] text-[#2271b1] bg-white -mb-px'
                      : 'border-transparent text-gray-500'
                  }`}
                >
                  <span>{tab.icon}</span>
                  <span>{tab.label}</span>
                </div>
              ))}
            </div>

            {/* Content */}
            <div className="bg-white p-4 space-y-4">
              {/* SEO Title */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-semibold text-[#1d2327]">SEO Başlığı</label>
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-green-50 text-green-600 font-medium">24/60</span>
                </div>
                <input type="text" className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm" placeholder="Sayfa başlığı..." defaultValue="WordPress SEO Eklentisi" />
              </div>

              {/* Meta Description */}
              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-semibold text-[#1d2327]">Meta Açıklaması</label>
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-600 font-medium">142/160</span>
                </div>
                <textarea className="w-full border border-gray-300 rounded px-3 py-1.5 text-sm h-16" placeholder="Meta açıklaması...">WordPress için en iyi SEO eklentisi. Schema.org desteği, meta etiketleri, sitemap ve daha fazlası.</textarea>
              </div>

              {/* SERP Preview */}
              <div className="bg-[#f6f7f7] border border-gray-200 rounded p-3">
                <div className="text-base text-[#1a0dab] font-medium mb-1">WordPress SEO Eklentisi</div>
                <div className="text-xs text-[#006621] mb-1">example.com/wordpress-seo-eklentisi</div>
                <div className="text-xs text-[#4d5156]">WordPress için en iyi SEO eklentisi. Schema.org desteği, meta etiketleri...</div>
              </div>
            </div>
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: '4 Sekme', desc: 'İçerik, Sosyal, Şema, Gelişmiş' },
            { title: 'Karakter Sayacı', desc: 'Title: 60, Desc: 160 (renk kodlu)' },
            { title: 'Media Uploader', desc: 'wp.media ile OG/Twitter görsel' },
            { title: 'AJAX Schema', desc: 'Tip seçilince dinamik alan yükleme' },
            { title: 'SERP Önizleme', desc: 'Gerçek zamanlı Google preview' },
            { title: 'Nonce + Yetki', desc: 'Güvenli form kaydetme' },
            { title: 'REST API', desc: 'Gutenberg uyumlu post meta' },
            { title: 'Sanitization', desc: 'Tüm girdiler sanitize edilir' },
          ].map((item, i) => (
            <div key={i} className="p-4 bg-white/[0.02] border border-white/5 rounded-xl">
              <p className="text-sm font-medium text-purple-300">{item.title}</p>
              <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
            </div>
          ))}
        </div>

        {/* Meta Keys Table */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">Kaydedilen Meta Key'leri</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            {[
              '_wpsm_title', '_wpsm_description', '_wpsm_focus_keyword',
              '_wpsm_canonical', '_wpsm_og_title', '_wpsm_og_description',
              '_wpsm_og_image', '_wpsm_twitter_title', '_wpsm_twitter_description',
              '_wpsm_twitter_image', '_wpsm_schema_type', '_wpsm_schema_data',
              '_wpsm_robots', '_wpsm_breadcrumb_title',
            ].map((key, i) => (
              <code key={i} className="text-xs bg-purple-500/10 text-purple-300 px-3 py-1.5 rounded border border-purple-500/20">
                {key}
              </code>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
