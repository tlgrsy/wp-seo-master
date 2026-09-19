<?php
/**
 * Ayarlar Sınıfı
 *
 * WordPress Settings API kullanarak eklenti ayarlarını yönetir.
 * register_setting, add_settings_section, add_settings_field kullanır.
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
 * Class Class_Settings
 *
 * WordPress Settings API ile ayar yönetimi.
 * Tab yapısı: general, schema, social, sitemap
 */
class Class_Settings {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Settings group adı
     *
     * @var string
     */
    const SETTINGS_GROUP = 'wpsm_settings_group';

    /**
     * Settings option adı
     *
     * @var string
     */
    const SETTINGS_NAME = 'wpsm_settings';

    /**
     * Nonce action
     *
     * @var string
     */
    const NONCE_ACTION = 'wpsm_save_settings';

    /**
     * Nonce field name
     *
     * @var string
     */
    const NONCE_NAME = 'wpsm_nonce';

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Ayarları kaydet
     *
     * admin_init hook'u ile çağrılır.
     * Settings API ile ayarları register eder.
     */
    public function register_settings() {
        // Ayarları register et
        register_setting(
            self::SETTINGS_GROUP,
            self::SETTINGS_NAME,
            array( $this, 'sanitize_settings' )
        );

        // Sekmeleri ve alanları kaydet
        $this->register_general_settings();
        $this->register_schema_settings();
        $this->register_social_settings();
        $this->register_sitemap_settings();
    }

    /**
     * Genel ayarları kaydet
     */
    private function register_general_settings() {
        // Section: Genel
        add_settings_section(
            'wpsm_general_section',
            __( 'Genel Ayarlar', 'wp-seo-master' ),
            array( $this, 'render_general_section_desc' ),
            'wpsm_settings_general'
        );

        // Field: Title Separator
        add_settings_field(
            'wpsm_title_separator',
            __( 'Başlık Ayırıcı', 'wp-seo-master' ),
            array( $this, 'render_title_separator_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        // Field: Home Title
        add_settings_field(
            'wpsm_home_title',
            __( 'Ana Sayfa Başlığı', 'wp-seo-master' ),
            array( $this, 'render_home_title_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        // Field: Home Description
        add_settings_field(
            'wpsm_home_description',
            __( 'Ana Sayfa Açıklaması', 'wp-seo-master' ),
            array( $this, 'render_home_description_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        // Field: Google Verification
        add_settings_field(
            'wpsm_google_verification',
            __( 'Google Verification Kodu', 'wp-seo-master' ),
            array( $this, 'render_google_verification_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );

        // Field: Bing Verification
        add_settings_field(
            'wpsm_bing_verification',
            __( 'Bing Verification Kodu', 'wp-seo-master' ),
            array( $this, 'render_bing_verification_field' ),
            'wpsm_settings_general',
            'wpsm_general_section'
        );
    }

    /**
     * Şema ayarlarını kaydet
     */
    private function register_schema_settings() {
        add_settings_section(
            'wpsm_schema_section',
            __( 'Şema Ayarları', 'wp-seo-master' ),
            array( $this, 'render_schema_section_desc' ),
            'wpsm_settings_schema'
        );

        // Field: Default Schema Type
        add_settings_field(
            'wpsm_default_schema_type',
            __( 'Varsayılan Şema Türü', 'wp-seo-master' ),
            array( $this, 'render_default_schema_type_field' ),
            'wpsm_settings_schema',
            'wpsm_schema_section'
        );

        // Field: Enable Schema
        add_settings_field(
            'wpsm_enable_schema',
            __( 'Şema Etkin', 'wp-seo-master' ),
            array( $this, 'render_enable_schema_field' ),
            'wpsm_settings_schema',
            'wpsm_schema_section'
        );
    }

    /**
     * Sosyal medya ayarlarını kaydet
     */
    private function register_social_settings() {
        add_settings_section(
            'wpsm_social_section',
            __( 'Sosyal Medya Ayarları', 'wp-seo-master' ),
            array( $this, 'render_social_section_desc' ),
            'wpsm_settings_social'
        );

        // Field: Facebook App ID
        add_settings_field(
            'wpsm_facebook_app_id',
            __( 'Facebook App ID', 'wp-seo-master' ),
            array( $this, 'render_facebook_app_id_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        // Field: Twitter Site
        add_settings_field(
            'wpsm_twitter_site',
            __( 'Twitter Kullanıcı Adı', 'wp-seo-master' ),
            array( $this, 'render_twitter_site_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        // Field: Default OG Image
        add_settings_field(
            'wpsm_default_og_image',
            __( 'Varsayılan OG Görseli', 'wp-seo-master' ),
            array( $this, 'render_default_og_image_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );

        // Field: Twitter Card Type
        add_settings_field(
            'wpsm_twitter_card_type',
            __( 'Twitter Card Türü', 'wp-seo-master' ),
            array( $this, 'render_twitter_card_type_field' ),
            'wpsm_settings_social',
            'wpsm_social_section'
        );
    }

    /**
     * Sitemap ayarlarını kaydet
     */
    private function register_sitemap_settings() {
        add_settings_section(
            'wpsm_sitemap_section',
            __( 'Sitemap Ayarları', 'wp-seo-master' ),
            array( $this, 'render_sitemap_section_desc' ),
            'wpsm_settings_sitemap'
        );

        // Field: Enable Sitemap
        add_settings_field(
            'wpsm_enable_sitemap',
            __( 'Sitemap Etkin', 'wp-seo-master' ),
            array( $this, 'render_enable_sitemap_field' ),
            'wpsm_settings_sitemap',
            'wpsm_sitemap_section'
        );

        // Field: Sitemap Post Types
        add_settings_field(
            'wpsm_sitemap_post_types',
            __( 'Dahil Edilecek Post Türleri', 'wp-seo-master' ),
            array( $this, 'render_sitemap_post_types_field' ),
            'wpsm_settings_sitemap',
            'wpsm_sitemap_section'
        );
    }

    /**
     * Ayarları sanitize et
     *
     * register_setting callback'i.
     * Tüm girdileri sanitize eder.
     *
     * @param array $input Ham giriş verisi
     * @return array Sanitize edilmiş veri
     */
    public function sanitize_settings( $input ) {
        // Yetki kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->options->all();
        }

        $sanitized = array();

        // Title Separator
        if ( isset( $input['title_separator'] ) ) {
            $allowed_separators = array( '|', '-', '–', '»', '/', '·', '•' );
            $separator = sanitize_text_field( $input['title_separator'] );
            $sanitized['title_separator'] = in_array( $separator, $allowed_separators, true ) ? $separator : '|';
        }

        // Home Title
        if ( isset( $input['home_title'] ) ) {
            $sanitized['home_title'] = sanitize_text_field( $input['home_title'] );
        }

        // Home Description
        if ( isset( $input['home_description'] ) ) {
            $sanitized['home_description'] = sanitize_textarea_field( $input['home_description'] );
        }

        // Google Verification
        if ( isset( $input['google_verification'] ) ) {
            $sanitized['google_verification'] = sanitize_text_field( $input['google_verification'] );
        }

        // Bing Verification
        if ( isset( $input['bing_verification'] ) ) {
            $sanitized['bing_verification'] = sanitize_text_field( $input['bing_verification'] );
        }

        // Default Schema Type
        if ( isset( $input['default_schema_type'] ) ) {
            $allowed_types = array( 'Article', 'WebPage', 'BlogPosting', 'NewsArticle', 'None' );
            $type = sanitize_text_field( $input['default_schema_type'] );
            $sanitized['default_schema_type'] = in_array( $type, $allowed_types, true ) ? $type : 'Article';
        }

        // Enable Schema
        $sanitized['enable_schema'] = isset( $input['enable_schema'] ) ? true : false;

        // Facebook App ID
        if ( isset( $input['facebook_app_id'] ) ) {
            $sanitized['facebook_app_id'] = sanitize_text_field( $input['facebook_app_id'] );
        }

        // Twitter Site
        if ( isset( $input['twitter_site'] ) ) {
            $twitter_site = sanitize_text_field( $input['twitter_site'] );
            // @ işareti ile başlıyorsa kaldır
            $twitter_site = ltrim( $twitter_site, '@' );
            $sanitized['twitter_site'] = $twitter_site;
        }

        // Default OG Image
        if ( isset( $input['default_og_image'] ) ) {
            $sanitized['default_og_image'] = esc_url_raw( $input['default_og_image'] );
        }

        // Twitter Card Type
        if ( isset( $input['twitter_card_type'] ) ) {
            $allowed_card_types = array( 'summary', 'summary_large_image' );
            $card_type = sanitize_text_field( $input['twitter_card_type'] );
            $sanitized['twitter_card_type'] = in_array( $card_type, $allowed_card_types, true ) ? $card_type : 'summary_large_image';
        }

        // Enable Sitemap
        $sanitized['enable_sitemap'] = isset( $input['enable_sitemap'] ) ? true : false;

        // Sitemap Post Types
        if ( isset( $input['sitemap_post_types'] ) && is_array( $input['sitemap_post_types'] ) ) {
            $sanitized['sitemap_post_types'] = array_map( 'sanitize_text_field', $input['sitemap_post_types'] );
        } else {
            $sanitized['sitemap_post_types'] = array();
        }

        /**
         * Sanitize sonrası filtre
         *
         * @param array $sanitized Sanitize edilmiş veri
         * @param array $input     Ham giriş verisi
         */
        return apply_filters( 'wpsm_sanitize_settings', $sanitized, $input );
    }

    /**
     * Section açıklamaları
     */
    public function render_general_section_desc() {
        echo '<p>' . esc_html__( 'Sitenizin genel SEO ayarlarını yapılandırın.', 'wp-seo-master' ) . '</p>';
    }

    public function render_schema_section_desc() {
        echo '<p>' . esc_html__( 'Schema.org markup ayarlarını yapılandırın.', 'wp-seo-master' ) . '</p>';
    }

    public function render_social_section_desc() {
        echo '<p>' . esc_html__( 'Sosyal medya entegrasyon ayarlarını yapılandırın.', 'wp-seo-master' ) . '</p>';
    }

    public function render_sitemap_section_desc() {
        echo '<p>' . esc_html__( 'XML Sitemap ayarlarını yapılandırın.', 'wp-seo-master' ) . '</p>';
    }

    /**
     * Field render metodları
     */

    public function render_title_separator_field() {
        $value = $this->options->get( 'title_separator', '|' );
        $separators = array( '|', '-', '–', '»', '/', '·', '•' );
        ?>
        <select name="wpsm_settings[title_separator]">
            <?php foreach ( $separators as $sep ) : ?>
                <option value="<?php echo esc_attr( $sep ); ?>" <?php selected( $value, $sep ); ?>>
                    <?php echo esc_html( $sep ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Başlık etiketinde site adı ile sayfa başlığını ayıran karakter.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_home_title_field() {
        $value = $this->options->get( 'home_title', '' );
        ?>
        <input type="text" name="wpsm_settings[home_title]" value="<?php echo esc_attr( $value ); ?>" class="regular-text wpsm-char-input" data-max="60" />
        <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/60</span>
        <p class="description">
            <?php esc_html_e( 'Ana sayfa için SEO başlığı. Boş bırakılırsa site başlığı kullanılır.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_home_description_field() {
        $value = $this->options->get( 'home_description', '' );
        ?>
        <textarea name="wpsm_settings[home_description]" class="large-text wpsm-char-input" data-max="160" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
        <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/160</span>
        <p class="description">
            <?php esc_html_e( 'Ana sayfa için meta açıklaması. Boş bırakılırsa site açıklaması kullanılır.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_google_verification_field() {
        $value = $this->options->get( 'google_verification', '' );
        ?>
        <input type="text" name="wpsm_settings[google_verification]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="google-site-verification=..." />
        <p class="description">
            <?php esc_html_e( 'Google Search Console doğrulama kodu.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_bing_verification_field() {
        $value = $this->options->get( 'bing_verification', '' );
        ?>
        <input type="text" name="wpsm_settings[bing_verification]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php esc_html_e( 'Bing Webmaster Tools doğrulama kodu.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_default_schema_type_field() {
        $value = $this->options->get( 'default_schema_type', 'Article' );
        $types = array(
            'Article'     => __( 'Makale', 'wp-seo-master' ),
            'WebPage'     => __( 'Web Sayfası', 'wp-seo-master' ),
            'BlogPosting' => __( 'Blog Yazısı', 'wp-seo-master' ),
            'NewsArticle' => __( 'Haber', 'wp-seo-master' ),
            'None'        => __( 'Yok', 'wp-seo-master' ),
        );
        ?>
        <select name="wpsm_settings[default_schema_type]">
            <?php foreach ( $types as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Yazılar için varsayılan schema türü.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_enable_schema_field() {
        $value = $this->options->get( 'enable_schema', true );
        ?>
        <label>
            <input type="checkbox" name="wpsm_settings[enable_schema]" value="1" <?php checked( $value ); ?> />
            <?php esc_html_e( 'Schema.org markup\'u etkinleştir', 'wp-seo-master' ); ?>
        </label>
        <?php
    }

    public function render_facebook_app_id_field() {
        $value = $this->options->get( 'facebook_app_id', '' );
        ?>
        <input type="text" name="wpsm_settings[facebook_app_id]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description">
            <?php esc_html_e( 'Facebook Insights için App ID.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_twitter_site_field() {
        $value = $this->options->get( 'twitter_site', '' );
        ?>
        <input type="text" name="wpsm_settings[twitter_site]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="@kullaniciadi" />
        <p class="description">
            <?php esc_html_e( 'Twitter kullanıcı adınız (@ işareti olmadan).', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_default_og_image_field() {
        $value = $this->options->get( 'default_og_image', '' );
        ?>
        <div class="wpsm-media-upload-wrapper" data-field="default_og_image">
            <input type="hidden" name="wpsm_settings[default_og_image]" value="<?php echo esc_url( $value ); ?>" />
            <?php if ( ! empty( $value ) ) : ?>
                <div class="wpsm-media-preview">
                    <img src="<?php echo esc_url( $value ); ?>" alt="OG Image" />
                </div>
            <?php endif; ?>
            <button type="button" class="button wpsm-media-upload-btn">
                <?php esc_html_e( 'Görsel Seç', 'wp-seo-master' ); ?>
            </button>
            <?php if ( ! empty( $value ) ) : ?>
                <button type="button" class="button wpsm-media-remove-btn">
                    <?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?>
                </button>
            <?php endif; ?>
        </div>
        <p class="description">
            <?php esc_html_e( 'Sosyal paylaşımlarda kullanılacak varsayılan görsel (1200x630px önerilir).', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_twitter_card_type_field() {
        $value = $this->options->get( 'twitter_card_type', 'summary_large_image' );
        $types = array(
            'summary'             => __( 'Özet', 'wp-seo-master' ),
            'summary_large_image' => __( 'Büyük Görsel', 'wp-seo-master' ),
        );
        ?>
        <select name="wpsm_settings[twitter_card_type]">
            <?php foreach ( $types as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Twitter kartlarında kullanılacak görsel boyutu.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }

    public function render_enable_sitemap_field() {
        $value = $this->options->get( 'enable_sitemap', true );
        ?>
        <label>
            <input type="checkbox" name="wpsm_settings[enable_sitemap]" value="1" <?php checked( $value ); ?> />
            <?php esc_html_e( 'XML Sitemap\'i etkinleştir', 'wp-seo-master' ); ?>
        </label>
        <p class="description">
            <?php
            printf(
                /* translators: %s: sitemap URL */
                esc_html__( 'Sitemap URL: %s', 'wp-seo-master' ),
                '<code>' . esc_url( home_url( '/sitemap.xml' ) ) . '</code>'
            );
            ?>
        </p>
        <?php
    }

    public function render_sitemap_post_types_field() {
        $value = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type->name ) {
                continue;
            }
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="wpsm_settings[sitemap_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $value, true ) ); ?> />
                <?php echo esc_html( $post_type->label ); ?>
            </label>
            <?php
        }
        ?>
        <p class="description">
            <?php esc_html_e( 'Sitemap\'e dahil edilecek post türlerini seçin.', 'wp-seo-master' ); ?>
        </p>
        <?php
    }
}
