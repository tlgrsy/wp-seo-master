import { useState } from 'react'

const files = [
  {
    id: 'metabox',
    name: 'class-metabox.php',
    path: 'includes/Admin/class-metabox.php',
    description: 'SEO Metabox sınıfı - Tab yapısı, kayıt, AJAX, REST API desteği',
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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        $post_types = get_post_types( array( 'public' => true ) );
        unset( $post_types['attachment'] );

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
     * show_in_rest=true, auth_callback ile yetki kontrolü
     */
    private function register_post_meta() {
        $post_types = get_post_types( array( 'public' => true ) );

        foreach ( $post_types as $post_type ) {
            register_post_meta( $post_type, '_wpsm_title', array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => array( $this, 'meta_auth_callback' ),
                'sanitize_callback' => 'sanitize_text_field',
            ));

            register_post_meta( $post_type, '_wpsm_description', array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => array( $this, 'meta_auth_callback' ),
                'sanitize_callback' => 'sanitize_textarea_field',
            ));

            // ... diğer meta alanları da aynı şekilde kaydedilir
        }
    }

    /**
     * Meta yetki kontrolü callback
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
        $meta = $this->get_all_meta( $post_id );
        include WPSM_INCLUDES_PATH . 'Admin/views/metabox.php';
    }

    /**
     * save_post hook'u ile kaydetme
     * Nonce, autosave, revision ve yetki kontrolleri
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

        // Robots (checkbox array)
        $robots = array();
        $allowed_robots = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );
        if ( isset( $_POST['wpsm_robots'] ) && is_array( $_POST['wpsm_robots'] ) ) {
            foreach ( $_POST['wpsm_robots'] as $robot ) {
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

        $allowed_types = array( 'article', 'faq', 'howto', 'product', 'localbusiness' );
        if ( ! in_array( $schema_type, $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz schema türü.', 'wp-seo-master' ) ) );
        }

        ob_start();
        $this->render_schema_fields( $schema_type, array() );
        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html, 'type' => $schema_type ) );
    }
}`,
  },
  {
    id: 'view',
    name: 'views/metabox.php',
    path: 'includes/Admin/views/metabox.php',
    description: 'Metabox template - Tab yapısı, form alanları, SERP önizleme',
    language: 'php',
    code: `<?php
/**
 * SEO Metabox Template
 *
 * Tab yapısı: İçerik, Sosyal, Şema, Gelişmiş
 *
 * @var int    $post_id Post ID
 * @var array  $meta    Meta değerleri
 * @var object $post    WP_Post objesi
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Meta değerlerini değişkenlere ata
$title         = isset( $meta['_wpsm_title'] ) ? $meta['_wpsm_title'] : '';
$description   = isset( $meta['_wpsm_description'] ) ? $meta['_wpsm_description'] : '';
$focus_keyword = isset( $meta['_wpsm_focus_keyword'] ) ? $meta['_wpsm_focus_keyword'] : '';
$canonical     = isset( $meta['_wpsm_canonical'] ) ? $meta['_wpsm_canonical'] : '';
$og_title      = isset( $meta['_wpsm_og_title'] ) ? $meta['_wpsm_og_title'] : '';
$og_image      = isset( $meta['_wpsm_og_image'] ) ? $meta['_wpsm_og_image'] : '';
$schema_type   = isset( $meta['_wpsm_schema_type'] ) ? $meta['_wpsm_schema_type'] : 'none';
$robots        = isset( $meta['_wpsm_robots'] ) ? $meta['_wpsm_robots'] : array();
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

            <!-- OG Title -->
            <div class="wpsm-field-group">
                <label for="wpsm-og-title" class="wpsm-field-label">OG Başlığı</label>
                <input type="text" id="wpsm-og-title" name="wpsm_og_title"
                    value="<?php echo esc_attr( $og_title ); ?>" class="wpsm-input"
                />
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
                    <button type="button" class="button wpsm-media-remove-btn">Kaldır</button>
                </div>
            </div>

            <!-- Twitter alanları da benzer şekilde... -->
        </div>

        <!-- SEKME 3: ŞEMA -->
        <div class="wpsm-tab-panel" id="wpsm-tab-schema">
            <div class="wpsm-field-group">
                <label for="wpsm-schema-type" class="wpsm-field-label">Schema Türü</label>
                <select id="wpsm-schema-type" name="wpsm_schema_type" class="wpsm-select">
                    <option value="none">— Seçiniz —</option>
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
                <div class="wpsm-schema-dynamic-fields"></div>
            </div>
        </div>

        <!-- SEKME 4: GELİŞMİŞ -->
        <div class="wpsm-tab-panel" id="wpsm-tab-advanced">
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Robots Meta', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-checkbox-group">
                <label class="wpsm-checkbox-label">
                    <input type="checkbox" name="wpsm_robots[]" value="noindex"
                        <?php checked( in_array( 'noindex', $robots, true ) ); ?>
                    />
                    <span class="wpsm-checkbox-text">
                        <strong>noindex</strong> — Arama motorlarına indeksletme
                    </span>
                </label>
                <label class="wpsm-checkbox-label">
                    <input type="checkbox" name="wpsm_robots[]" value="nofollow"
                        <?php checked( in_array( 'nofollow', $robots, true ) ); ?>
                    />
                    <span class="wpsm-checkbox-text">
                        <strong>nofollow</strong> — Bağlantıları takip ettirme
                    </span>
                </label>
                <label class="wpsm-checkbox-label">
                    <input type="checkbox" name="wpsm_robots[]" value="noarchive"
                        <?php checked( in_array( 'noarchive', $robots, true ) ); ?>
                    />
                    <span class="wpsm-checkbox-text">
                        <strong>noarchive</strong> — Önbelleğe alma
                    </span>
                </label>
            </div>

            <!-- Breadcrumb Başlığı -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Breadcrumb', 'wp-seo-master' ); ?></h4>
            </div>
            <div class="wpsm-field-group">
                <label class="wpsm-field-label">Breadcrumb Başlığı</label>
                <input type="text" name="wpsm_breadcrumb_title" class="wpsm-input"
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
    description: 'Metabox JavaScript - Karakter sayacı, tab geçişleri, media uploader, AJAX',
    language: 'javascript',
    code: `/**
 * WP SEO Master - Admin JavaScript
 *
 * - Karakter sayacı (Title: 60, Description: 160)
 * - Tab geçişleri
 * - Media uploader (wp.media)
 * - Schema tipi değişince AJAX ile alanları getir
 * - SERP önizleme güncelleme
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
         * Renk kodları:
         * - Yeşil: <= 80% (önerilen aralık)
         * - Sarı: 80-100% (uyarı)
         * - Kırmızı: > 100% (aşıldı)
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
            $counter.removeClass('wpsm-char-green wpsm-char-yellow wpsm-char-red');

            var percentage = (currentLength / maxChars) * 100;

            if (percentage <= 80) {
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
                var $preview = $wrapper.find('.wpsm-media-preview img');
                var $previewWrap = $wrapper.find('.wpsm-media-preview');
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
                    $preview.attr('src', imageUrl);
                    $previewWrap.show();
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

                if (schemaType === 'none' || schemaType === '') {
                    $dynamicFields.empty().hide();
                    return;
                }

                $loader.show();

                $.ajax({
                    url: wpsmAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wpsm_load_schema_fields',
                        schema_type: schemaType,
                        nonce: wpsmAdmin.nonce
                    },
                    success: function (response) {
                        $loader.hide();
                        if (response.success) {
                            $dynamicFields.html(response.data.html).show();
                        }
                    },
                    error: function () {
                        $loader.hide();
                        $dynamicFields.html('<p class="wpsm-error">Bağlantı hatası.</p>').show();
                    }
                });
            });
        },

        /**
         * Schema Repeater (FAQ, HowTo)
         */
        initSchemaRepeater: function () {
            $(document).on('click', '.wpsm-schema-add-item', function (e) {
                e.preventDefault();
                var $container = $(this).prev('.wpsm-schema-repeater');
                var type = $(this).data('type');
                var index = $container.find('.wpsm-schema-repeater-item').length;
                // Dinamik HTML ekleme...
            });

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
    description: 'Metabox CSS - Tab yapısı, form alanları, karakter sayacı, SERP önizleme',
    language: 'css',
    code: `/**
 * WP SEO Master - Admin CSS
 *
 * Metabox stilleri:
 * - Tab yapısı
 * - Form alanları
 * - Karakter sayacı renkleri
 * - Media uploader
 * - Schema alanları
 * - SERP önizleme
 */

/* TAB NAVIGATION */
.wpsm-tabs-nav {
    display: flex;
    border-bottom: 1px solid #dcdcde;
    background: #f6f7f7;
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
    margin-bottom: 6px;
}

.wpsm-input,
.wpsm-textarea,
.wpsm-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 13px;
}

.wpsm-input:focus,
.wpsm-textarea:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}

/* CHARACTER COUNTER */
.wpsm-char-counter {
    font-size: 11px;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 10px;
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

/* CHECKBOX GROUP */
.wpsm-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 8px;
}

.wpsm-checkbox-label:hover {
    background: #f6f7f7;
}

/* MEDIA UPLOADER */
.wpsm-media-preview {
    width: 120px;
    height: 80px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    overflow: hidden;
}

.wpsm-media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* SCHEMA FIELDS */
.wpsm-schema-fields-wrapper {
    margin-top: 16px;
    padding: 16px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 6px;
}

.wpsm-schema-repeater-item {
    padding: 14px;
    margin-bottom: 10px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
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
        .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\b(var|let|const|function|return|if|else|for|while|this|new|typeof|instanceof|true|false|null|undefined)\b/g, '<span class="text-purple-400 font-medium">$1</span>')
        .replace(/'([^'\\]*(?:\\.[^'\\]*)*)'/g, "'<span class=\"text-green-300\">$1</span>'")
        .replace(/"([^"\\]*(?:\\.[^"\\]*)*)"/g, '"<span class="text-green-300">$1</span>"')
    } else if (language === 'css') {
      highlighted = highlighted
        .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/(\.[\w-]+)/g, '<span class="text-yellow-300">$1</span>')
        .replace(/(#[\da-fA-F]{3,8})/g, '<span class="text-orange-300">$1</span>')
        .replace(/(\d+(?:\.\d+)?(?:px|em|rem|%|vh|vw|s|ms))/g, '<span class="text-blue-300">$1</span>')
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
            Metabox Dosyaları
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            Post editör metabox'u için 4 dosya: PHP sınıfı, template, JavaScript ve CSS.
            Tab yapısı, karakter sayacı, media uploader, AJAX schema alanları.
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
          {/* Code Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-white/5 bg-white/[0.02]">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 rounded-full bg-red-500/80" />
              <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
              <div className="w-3 h-3 rounded-full bg-green-500/80" />
            </div>
            <span className="text-xs text-gray-500 font-mono">{currentFile.name}</span>
            <span className="text-xs text-gray-600 uppercase">{currentFile.language}</span>
          </div>

          {/* Code Content */}
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

        {/* Features Summary */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">Metabox Özellikleri</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { title: '4 Sekme', desc: 'İçerik, Sosyal, Şema, Gelişmiş' },
              { title: 'Karakter Sayacı', desc: 'Title: 60, Desc: 160 (renk kodlu)' },
              { title: 'Media Uploader', desc: 'wp.media ile OG/Twitter görsel seçimi' },
              { title: 'AJAX Schema', desc: 'Tip seçilince dinamik alan yükleme' },
              { title: 'SERP Önizleme', desc: 'Gerçek zamanlı Google sonucu preview' },
              { title: 'Nonce + Yetki', desc: 'Güvenli form kaydetme' },
              { title: 'REST API', desc: 'Gutenberg uyumlu post meta kaydı' },
              { title: 'Sanitization', desc: 'Tüm girdiler sanitize edilir' },
            ].map((item, i) => (
              <div key={i} className="p-3 bg-white/[0.02] border border-white/5 rounded-lg">
                <p className="text-sm font-medium text-purple-300">{item.title}</p>
                <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
