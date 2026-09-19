<?php
/**
 * SEO Metabox Sınıfı
 *
 * Post/Page/CPT edit ekranlarında SEO meta alanlarını gösteren metabox.
 * Tab yapısı: İçerik, Sosyal, Şema, Gelişmiş
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
 * Class Class_Metabox
 *
 * Post edit ekranında SEO ayarları metabox'u.
 * Gutenberg ve Classic Editor ile tam uyumlu.
 */
class Class_Metabox {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Metabox ID
     *
     * @var string
     */
    const METABOX_ID = 'wpsm-seo-metabox';

    /**
     * Nonce action
     *
     * @var string
     */
    const NONCE_ACTION = 'wpsm_metabox_save';

    /**
     * Nonce field name
     *
     * @var string
     */
    const NONCE_NAME = 'wpsm_metabox_nonce';

    /**
     * Kaydedilecek meta key'leri
     *
     * @var array
     */
    const META_KEYS = array(
        '_wpsm_title',
        '_wpsm_description',
        '_wpsm_focus_keyword',
        '_wpsm_canonical',
        '_wpsm_og_title',
        '_wpsm_og_description',
        '_wpsm_og_image',
        '_wpsm_twitter_title',
        '_wpsm_twitter_description',
        '_wpsm_twitter_image',
        '_wpsm_schema_type',
        '_wpsm_schema_data',
        '_wpsm_robots',
        '_wpsm_breadcrumb_title',
    );

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;

        // AJAX: Schema alanlarını yükle
        add_action( 'wp_ajax_wpsm_load_schema_fields', array( $this, 'ajax_load_schema_fields' ) );
    }

    /**
     * Metabox'ları kaydet
     *
     * Tüm public post type'lar için metabox ekler.
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
     * Desteklenen post type'ları döndür
     *
     * @return array
     */
    private function get_supported_post_types() {
        $post_types = get_post_types( array( 'public' => true ) );
        unset( $post_types['attachment'] );

        /**
         * Desteklenen post type'ları filtrele
         *
         * @param array $post_types
         */
        return apply_filters( 'wpsm_supported_post_types', $post_types );
    }

    /**
     * Post meta alanlarını REST API'ye kaydet
     *
     * Gutenberg uyumluluğu için show_in_rest=true, single=true.
     * auth_callback ile yetki kontrolü yapar.
     */
    private function register_post_meta() {
        $post_types = $this->get_supported_post_types();

        // String meta alanları
        $string_fields = array(
            '_wpsm_title',
            '_wpsm_description',
            '_wpsm_focus_keyword',
            '_wpsm_canonical',
            '_wpsm_og_title',
            '_wpsm_og_description',
            '_wpsm_og_image',
            '_wpsm_twitter_title',
            '_wpsm_twitter_description',
            '_wpsm_twitter_image',
            '_wpsm_schema_type',
            '_wpsm_breadcrumb_title',
        );

        foreach ( $post_types as $post_type ) {
            // String alanlar
            foreach ( $string_fields as $key ) {
                register_post_meta( $post_type, $key, array(
                    'show_in_rest'      => true,
                    'single'            => true,
                    'type'              => 'string',
                    'auth_callback'     => array( $this, 'meta_auth_callback' ),
                    'sanitize_callback' => 'sanitize_text_field',
                ));
            }

            // Schema Data (object/JSON)
            register_post_meta( $post_type, '_wpsm_schema_data', array(
                'show_in_rest'  => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => new \stdClass(),
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
     * Meta yetki kontrolü callback
     *
     * @param bool   $allowed  İzin durumu
     * @param string $meta_key Meta anahtarı
     * @param int    $post_id  Post ID
     * @return bool
     */
    public function meta_auth_callback( $allowed, $meta_key, $post_id ) {
        return current_user_can( 'edit_post', $post_id );
    }

    /**
     * Metabox içeriğini render et
     *
     * @param \WP_Post $post Post objesi
     */
    public function render_metabox( $post ) {
        // Nonce field
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

        $post_id = $post->ID;
        $meta    = $this->get_all_meta( $post_id );

        // Template'e Class_Metabox instance'ını da gönder
        $wpsm_metabox = $this;

        // Template'i yükle
        include WPSM_INCLUDES_PATH . 'Admin/views/metabox.php';
    }

    /**
     * Tüm meta değerlerini döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_all_meta( $post_id ) {
        $meta = array();

        foreach ( self::META_KEYS as $key ) {
            $value = get_post_meta( $post_id, $key, true );

            // Schema data JSON decode
            if ( '_wpsm_schema_data' === $key && ! empty( $value ) ) {
                $decoded = json_decode( $value, true );
                $value   = is_array( $decoded ) ? $decoded : array();
            }

            // Robots array
            if ( '_wpsm_robots' === $key && ! empty( $value ) ) {
                $value = is_array( $value ) ? $value : array( $value );
            }

            $meta[ $key ] = $value;
        }

        return $meta;
    }

    /**
     * Post kaydederken meta alanlarını kaydet
     *
     * save_post hook'u ile tetiklenir.
     *
     * @param int      $post_id Post ID
     * @param \WP_Post $post    Post objesi
     */
    public function save_meta( $post_id, $post ) {
        // --- Güvenlik Kontrolleri ---

        // 1. Nonce kontrolü
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
            return;
        }

        // 2. Autosave kontrolü
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Revision kontrolü
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // 4. Yetki kontrolü
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 5. Post type kontrolü
        $supported_types = $this->get_supported_post_types();
        if ( ! in_array( $post->post_type, $supported_types, true ) ) {
            return;
        }

        // --- Meta Kaydetme ---

        // SEO Title
        if ( isset( $_POST['wpsm_title'] ) ) {
            update_post_meta( $post_id, '_wpsm_title', sanitize_text_field( wp_unslash( $_POST['wpsm_title'] ) ) );
        }

        // Meta Description
        if ( isset( $_POST['wpsm_description'] ) ) {
            update_post_meta( $post_id, '_wpsm_description', sanitize_textarea_field( wp_unslash( $_POST['wpsm_description'] ) ) );
        }

        // Focus Keyword
        if ( isset( $_POST['wpsm_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_wpsm_focus_keyword', sanitize_text_field( wp_unslash( $_POST['wpsm_focus_keyword'] ) ) );
        }

        // Canonical URL
        if ( isset( $_POST['wpsm_canonical'] ) ) {
            update_post_meta( $post_id, '_wpsm_canonical', esc_url_raw( wp_unslash( $_POST['wpsm_canonical'] ) ) );
        }

        // OG Title
        if ( isset( $_POST['wpsm_og_title'] ) ) {
            update_post_meta( $post_id, '_wpsm_og_title', sanitize_text_field( wp_unslash( $_POST['wpsm_og_title'] ) ) );
        }

        // OG Description
        if ( isset( $_POST['wpsm_og_description'] ) ) {
            update_post_meta( $post_id, '_wpsm_og_description', sanitize_textarea_field( wp_unslash( $_POST['wpsm_og_description'] ) ) );
        }

        // OG Image
        if ( isset( $_POST['wpsm_og_image'] ) ) {
            update_post_meta( $post_id, '_wpsm_og_image', esc_url_raw( wp_unslash( $_POST['wpsm_og_image'] ) ) );
        }

        // Twitter Title
        if ( isset( $_POST['wpsm_twitter_title'] ) ) {
            update_post_meta( $post_id, '_wpsm_twitter_title', sanitize_text_field( wp_unslash( $_POST['wpsm_twitter_title'] ) ) );
        }

        // Twitter Description
        if ( isset( $_POST['wpsm_twitter_description'] ) ) {
            update_post_meta( $post_id, '_wpsm_twitter_description', sanitize_textarea_field( wp_unslash( $_POST['wpsm_twitter_description'] ) ) );
        }

        // Twitter Image
        if ( isset( $_POST['wpsm_twitter_image'] ) ) {
            update_post_meta( $post_id, '_wpsm_twitter_image', esc_url_raw( wp_unslash( $_POST['wpsm_twitter_image'] ) ) );
        }

        // Schema Type
        if ( isset( $_POST['wpsm_schema_type'] ) ) {
            $schema_type = sanitize_text_field( wp_unslash( $_POST['wpsm_schema_type'] ) );
            $allowed_types = array( 'none', 'article', 'faq', 'howto', 'product', 'localbusiness' );
            if ( in_array( $schema_type, $allowed_types, true ) ) {
                update_post_meta( $post_id, '_wpsm_schema_type', $schema_type );
            }
        }

        // Schema Data (JSON)
        if ( isset( $_POST['wpsm_schema_data'] ) ) {
            $schema_data = wp_unslash( $_POST['wpsm_schema_data'] );
            if ( is_array( $schema_data ) ) {
                $sanitized = $this->sanitize_schema_data( $schema_data );
                update_post_meta( $post_id, '_wpsm_schema_data', wp_json_encode( $sanitized ) );
            }
        }

        // Robots (checkbox array)
        $robots = array();
        if ( isset( $_POST['wpsm_robots'] ) && is_array( $_POST['wpsm_robots'] ) ) {
            $allowed_robots = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );
            foreach ( wp_unslash( $_POST['wpsm_robots'] ) as $robot ) {
                $robot = sanitize_text_field( $robot );
                if ( in_array( $robot, $allowed_robots, true ) ) {
                    $robots[] = $robot;
                }
            }
        }
        update_post_meta( $post_id, '_wpsm_robots', $robots );

        // Breadcrumb Title
        if ( isset( $_POST['wpsm_breadcrumb_title'] ) ) {
            update_post_meta( $post_id, '_wpsm_breadcrumb_title', sanitize_text_field( wp_unslash( $_POST['wpsm_breadcrumb_title'] ) ) );
        }

        /**
         * Meta kaydetme sonrası action
         *
         * @param int $post_id Post ID
         */
        do_action( 'wpsm_after_save_meta', $post_id );
    }

    /**
     * AJAX: Schema tipine göre alanları yükle
     */
    public function ajax_load_schema_fields() {
        // Nonce kontrolü
        check_ajax_referer( 'wpsm_metabox_save', 'nonce' );

        // Yetki kontrolü
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Yetkiniz yok.', 'wp-seo-master' ),
            ));
        }

        $schema_type = isset( $_POST['schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_type'] ) ) : '';

        $allowed_types = array( 'article', 'faq', 'howto', 'product', 'localbusiness' );
        if ( ! in_array( $schema_type, $allowed_types, true ) ) {
            wp_send_json_error( array(
                'message' => __( 'Geçersiz schema türü.', 'wp-seo-master' ),
            ));
        }

        // HTML çıktısını buffer'la al
        ob_start();
        $this->render_schema_fields( $schema_type, array() );
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html' => $html,
            'type' => $schema_type,
        ));
    }

    /**
     * Schema tipine göre dinamik alanları render et
     *
     * @param string $schema_type Schema tipi
     * @param array  $schema_data Mevcut schema verisi
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
            default:
                echo '<p class="wpsm-schema-empty-msg">' . esc_html__( 'Geçerli bir schema türü seçin.', 'wp-seo-master' ) . '</p>';
                break;
        }
    }

    /**
     * Article schema alanları
     */
    private function render_article_schema_fields( $data ) {
        $author   = isset( $data['author'] ) ? $data['author'] : '';
        $date_pub = isset( $data['datePublished'] ) ? $data['datePublished'] : '';
        $date_mod = isset( $data['dateModified'] ) ? $data['dateModified'] : '';
        $section  = isset( $data['articleSection'] ) ? $data['articleSection'] : '';
        ?>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Yazar', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[author]" value="<?php echo esc_attr( $author ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Yayın Tarihi', 'wp-seo-master' ); ?></label>
            <input type="date" name="wpsm_schema_data[datePublished]" value="<?php echo esc_attr( $date_pub ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Güncelleme Tarihi', 'wp-seo-master' ); ?></label>
            <input type="date" name="wpsm_schema_data[dateModified]" value="<?php echo esc_attr( $date_mod ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Kategori/Bölüm', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[articleSection]" value="<?php echo esc_attr( $section ); ?>" class="wpsm-input" />
        </div>
        <?php
    }

    /**
     * FAQ schema alanları
     */
    private function render_faq_schema_fields( $data ) {
        $questions = isset( $data['questions'] ) ? $data['questions'] : array( array( 'question' => '', 'answer' => '' ) );
        ?>
        <div id="wpsm-faq-questions" class="wpsm-schema-repeater">
            <?php foreach ( $questions as $index => $q ) : ?>
                <div class="wpsm-schema-repeater-item">
                    <div class="wpsm-schema-field">
                        <label><?php printf( esc_html__( 'Soru %d', 'wp-seo-master' ), $index + 1 ); ?></label>
                        <input type="text" name="wpsm_schema_data[questions][<?php echo esc_attr( $index ); ?>][question]" value="<?php echo esc_attr( $q['question'] ?? '' ); ?>" class="wpsm-input" />
                    </div>
                    <div class="wpsm-schema-field">
                        <label><?php esc_html_e( 'Cevap', 'wp-seo-master' ); ?></label>
                        <textarea name="wpsm_schema_data[questions][<?php echo esc_attr( $index ); ?>][answer]" class="wpsm-textarea" rows="3"><?php echo esc_textarea( $q['answer'] ?? '' ); ?></textarea>
                    </div>
                    <button type="button" class="button wpsm-schema-remove-item"><?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button wpsm-schema-add-item" data-type="faq">
            + <?php esc_html_e( 'Soru Ekle', 'wp-seo-master' ); ?>
        </button>
        <?php
    }

    /**
     * HowTo schema alanları
     */
    private function render_howto_schema_fields( $data ) {
        $description = isset( $data['description'] ) ? $data['description'] : '';
        $total_time  = isset( $data['totalTime'] ) ? $data['totalTime'] : '';
        $steps       = isset( $data['steps'] ) ? $data['steps'] : array( array( 'name' => '', 'text' => '' ) );
        ?>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Açıklama', 'wp-seo-master' ); ?></label>
            <textarea name="wpsm_schema_data[description]" class="wpsm-textarea" rows="2"><?php echo esc_textarea( $description ); ?></textarea>
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Toplam Süre (ISO 8601, örn: PT30M)', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[totalTime]" value="<?php echo esc_attr( $total_time ); ?>" class="wpsm-input" placeholder="PT30M" />
        </div>
        <div id="wpsm-howto-steps" class="wpsm-schema-repeater">
            <?php foreach ( $steps as $index => $step ) : ?>
                <div class="wpsm-schema-repeater-item">
                    <div class="wpsm-schema-field">
                        <label><?php printf( esc_html__( 'Adım %d - Başlık', 'wp-seo-master' ), $index + 1 ); ?></label>
                        <input type="text" name="wpsm_schema_data[steps][<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $step['name'] ?? '' ); ?>" class="wpsm-input" />
                    </div>
                    <div class="wpsm-schema-field">
                        <label><?php esc_html_e( 'Açıklama', 'wp-seo-master' ); ?></label>
                        <textarea name="wpsm_schema_data[steps][<?php echo esc_attr( $index ); ?>][text]" class="wpsm-textarea" rows="2"><?php echo esc_textarea( $step['text'] ?? '' ); ?></textarea>
                    </div>
                    <button type="button" class="button wpsm-schema-remove-item"><?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button wpsm-schema-add-item" data-type="howto">
            + <?php esc_html_e( 'Adım Ekle', 'wp-seo-master' ); ?>
        </button>
        <?php
    }

    /**
     * Product schema alanları
     */
    private function render_product_schema_fields( $data ) {
        $brand    = isset( $data['brand'] ) ? $data['brand'] : '';
        $sku      = isset( $data['sku'] ) ? $data['sku'] : '';
        $price    = isset( $data['price'] ) ? $data['price'] : '';
        $currency = isset( $data['priceCurrency'] ) ? $data['priceCurrency'] : 'TRY';
        $avail    = isset( $data['availability'] ) ? $data['availability'] : 'InStock';
        ?>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Marka', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[brand]" value="<?php echo esc_attr( $brand ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'SKU', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[sku]" value="<?php echo esc_attr( $sku ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Fiyat', 'wp-seo-master' ); ?></label>
            <input type="number" step="0.01" name="wpsm_schema_data[price]" value="<?php echo esc_attr( $price ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Para Birimi', 'wp-seo-master' ); ?></label>
            <select name="wpsm_schema_data[priceCurrency]" class="wpsm-select">
                <option value="TRY" <?php selected( $currency, 'TRY' ); ?>>TRY (₺)</option>
                <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD ($)</option>
                <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR (€)</option>
                <option value="GBP" <?php selected( $currency, 'GBP' ); ?>>GBP (£)</option>
            </select>
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Stok Durumu', 'wp-seo-master' ); ?></label>
            <select name="wpsm_schema_data[availability]" class="wpsm-select">
                <option value="InStock" <?php selected( $avail, 'InStock' ); ?>><?php esc_html_e( 'Stokta', 'wp-seo-master' ); ?></option>
                <option value="OutOfStock" <?php selected( $avail, 'OutOfStock' ); ?>><?php esc_html_e( 'Stokta Yok', 'wp-seo-master' ); ?></option>
                <option value="PreOrder" <?php selected( $avail, 'PreOrder' ); ?>><?php esc_html_e( 'Ön Sipariş', 'wp-seo-master' ); ?></option>
            </select>
        </div>
        <?php
    }

    /**
     * LocalBusiness schema alanları
     */
    private function render_localbusiness_schema_fields( $data ) {
        $name    = isset( $data['name'] ) ? $data['name'] : '';
        $address = isset( $data['address'] ) ? $data['address'] : '';
        $phone   = isset( $data['telephone'] ) ? $data['telephone'] : '';
        $price_r = isset( $data['priceRange'] ) ? $data['priceRange'] : '';
        $lat     = isset( $data['latitude'] ) ? $data['latitude'] : '';
        $lng     = isset( $data['longitude'] ) ? $data['longitude'] : '';
        ?>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'İşletme Adı', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[name]" value="<?php echo esc_attr( $name ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Adres', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[address]" value="<?php echo esc_attr( $address ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Telefon', 'wp-seo-master' ); ?></label>
            <input type="tel" name="wpsm_schema_data[telephone]" value="<?php echo esc_attr( $phone ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-field">
            <label><?php esc_html_e( 'Fiyat Aralığı (örn: ₺₺)', 'wp-seo-master' ); ?></label>
            <input type="text" name="wpsm_schema_data[priceRange]" value="<?php echo esc_attr( $price_r ); ?>" class="wpsm-input" />
        </div>
        <div class="wpsm-schema-row">
            <div class="wpsm-schema-field">
                <label><?php esc_html_e( 'Enlem', 'wp-seo-master' ); ?></label>
                <input type="text" name="wpsm_schema_data[latitude]" value="<?php echo esc_attr( $lat ); ?>" class="wpsm-input" />
            </div>
            <div class="wpsm-schema-field">
                <label><?php esc_html_e( 'Boylam', 'wp-seo-master' ); ?></label>
                <input type="text" name="wpsm_schema_data[longitude]" value="<?php echo esc_attr( $lng ); ?>" class="wpsm-input" />
            </div>
        </div>
        <?php
    }

    /**
     * Schema verisini sanitize et
     *
     * @param array $data Schema verisi
     * @return array
     */
    private function sanitize_schema_data( $data ) {
        $sanitized = array();

        foreach ( $data as $key => $value ) {
            $key = sanitize_text_field( $key );

            if ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_schema_data( $value );
            } elseif ( is_string( $value ) ) {
                $sanitized[ $key ] = sanitize_text_field( $value );
            } elseif ( is_numeric( $value ) ) {
                $sanitized[ $key ] = floatval( $value );
            } elseif ( is_bool( $value ) ) {
                $sanitized[ $key ] = (bool) $value;
            }
        }

        return $sanitized;
    }
}
