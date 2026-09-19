<?php
/**
 * SEO Metabox Template
 *
 * Tab yapısı: İçerik, Sosyal, Şema, Gelişmiş
 *
 * @package WPSM\Admin\Views
 * @since 1.0.0
 *
 * @var int    $post_id Post ID
 * @var array  $meta    Meta değerleri
 * @var object $post    WP_Post objesi
 */

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Meta değerlerini değişkenlere ata (kolay erişim)
$title              = isset( $meta['_wpsm_title'] ) ? $meta['_wpsm_title'] : '';
$description        = isset( $meta['_wpsm_description'] ) ? $meta['_wpsm_description'] : '';
$focus_keyword      = isset( $meta['_wpsm_focus_keyword'] ) ? $meta['_wpsm_focus_keyword'] : '';
$canonical          = isset( $meta['_wpsm_canonical'] ) ? $meta['_wpsm_canonical'] : '';
$og_title           = isset( $meta['_wpsm_og_title'] ) ? $meta['_wpsm_og_title'] : '';
$og_description     = isset( $meta['_wpsm_og_description'] ) ? $meta['_wpsm_og_description'] : '';
$og_image           = isset( $meta['_wpsm_og_image'] ) ? $meta['_wpsm_og_image'] : '';
$twitter_title      = isset( $meta['_wpsm_twitter_title'] ) ? $meta['_wpsm_twitter_title'] : '';
$twitter_description = isset( $meta['_wpsm_twitter_description'] ) ? $meta['_wpsm_twitter_description'] : '';
$twitter_image      = isset( $meta['_wpsm_twitter_image'] ) ? $meta['_wpsm_twitter_image'] : '';
$schema_type        = isset( $meta['_wpsm_schema_type'] ) ? $meta['_wpsm_schema_type'] : 'none';
$schema_data        = isset( $meta['_wpsm_schema_data'] ) ? $meta['_wpsm_schema_data'] : array();
$robots             = isset( $meta['_wpsm_robots'] ) ? $meta['_wpsm_robots'] : array();
$breadcrumb_title   = isset( $meta['_wpsm_breadcrumb_title'] ) ? $meta['_wpsm_breadcrumb_title'] : '';

// Schema data JSON string'e çevir (hidden field için)
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

    <!-- Tab Content -->
    <div class="wpsm-tabs-content">

        <!-- ============================================ -->
        <!-- SEKME 1: İÇERİK -->
        <!-- ============================================ -->
        <div class="wpsm-tab-panel active" id="wpsm-tab-content">
            <div class="wpsm-field-group">
                <label for="wpsm-title" class="wpsm-field-label">
                    <?php esc_html_e( 'SEO Başlığı', 'wp-seo-master' ); ?>
                    <span class="wpsm-char-counter" data-target="wpsm-title" data-max="60">
                        <span class="wpsm-char-count">0</span>/60
                    </span>
                </label>
                <input
                    type="text"
                    id="wpsm-title"
                    name="wpsm_title"
                    value="<?php echo esc_attr( $title ); ?>"
                    class="wpsm-input wpsm-char-input"
                    placeholder="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                    maxlength="200"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Arama motorlarında görünecek başlık. 60 karakter önerilir.', 'wp-seo-master' ); ?>
                </p>
                <!-- Önizleme -->
                <div class="wpsm-serp-preview">
                    <div class="wpsm-serp-title" id="wpsm-serp-title-preview">
                        <?php echo esc_html( ! empty( $title ) ? $title : get_the_title( $post_id ) ); ?>
                    </div>
                    <div class="wpsm-serp-url"><?php echo esc_url( get_permalink( $post_id ) ); ?></div>
                    <div class="wpsm-serp-desc" id="wpsm-serp-desc-preview">
                        <?php echo esc_html( ! empty( $description ) ? $description : __( 'Meta açıklamanız burada görünecek...', 'wp-seo-master' ) ); ?>
                    </div>
                </div>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-description" class="wpsm-field-label">
                    <?php esc_html_e( 'Meta Açıklaması', 'wp-seo-master' ); ?>
                    <span class="wpsm-char-counter" data-target="wpsm-description" data-max="160">
                        <span class="wpsm-char-count">0</span>/160
                    </span>
                </label>
                <textarea
                    id="wpsm-description"
                    name="wpsm_description"
                    class="wpsm-textarea wpsm-char-input"
                    rows="3"
                    placeholder="<?php esc_attr_e( 'Sayfanızın kısa açıklamasını yazın...', 'wp-seo-master' ); ?>"
                    maxlength="320"
                ><?php echo esc_textarea( $description ); ?></textarea>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Arama sonuçlarında görünecek açıklama. 160 karakter önerilir.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-focus-keyword" class="wpsm-field-label">
                    <?php esc_html_e( 'Odak Anahtar Kelime', 'wp-seo-master' ); ?>
                </label>
                <input
                    type="text"
                    id="wpsm-focus-keyword"
                    name="wpsm_focus_keyword"
                    value="<?php echo esc_attr( $focus_keyword ); ?>"
                    class="wpsm-input"
                    placeholder="<?php esc_attr_e( 'örn: wordpress seo eklentisi', 'wp-seo-master' ); ?>"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Bu yazı için hedef anahtar kelimenizi girin.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-canonical" class="wpsm-field-label">
                    <?php esc_html_e( 'Canonical URL', 'wp-seo-master' ); ?>
                </label>
                <input
                    type="url"
                    id="wpsm-canonical"
                    name="wpsm_canonical"
                    value="<?php echo esc_url( $canonical ); ?>"
                    class="wpsm-input"
                    placeholder="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Boş bırakılırsa otomatik permalink kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SEKME 2: SOSYAL -->
        <!-- ============================================ -->
        <div class="wpsm-tab-panel" id="wpsm-tab-social">
            <!-- Open Graph -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Open Graph (Facebook, LinkedIn)', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-og-title" class="wpsm-field-label">
                    <?php esc_html_e( 'OG Başlığı', 'wp-seo-master' ); ?>
                </label>
                <input
                    type="text"
                    id="wpsm-og-title"
                    name="wpsm_og_title"
                    value="<?php echo esc_attr( $og_title ); ?>"
                    class="wpsm-input"
                    placeholder="<?php echo esc_attr( ! empty( $title ) ? $title : get_the_title( $post_id ) ); ?>"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Boş bırakılırsa SEO başlığı kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-og-description" class="wpsm-field-label">
                    <?php esc_html_e( 'OG Açıklaması', 'wp-seo-master' ); ?>
                </label>
                <textarea
                    id="wpsm-og-description"
                    name="wpsm_og_description"
                    class="wpsm-textarea"
                    rows="2"
                    placeholder="<?php echo esc_attr( ! empty( $description ) ? $description : '' ); ?>"
                ><?php echo esc_textarea( $og_description ); ?></textarea>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Boş bırakılırsa meta açıklaması kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-og-image" class="wpsm-field-label">
                    <?php esc_html_e( 'OG Görseli', 'wp-seo-master' ); ?>
                </label>
                <div class="wpsm-media-upload-wrapper" data-field="wpsm-og-image">
                    <input
                        type="hidden"
                        id="wpsm-og-image"
                        name="wpsm_og_image"
                        value="<?php echo esc_url( $og_image ); ?>"
                    />
                    <div class="wpsm-media-preview" <?php echo empty( $og_image ) ? 'style="display:none;"' : ''; ?>>
                        <img src="<?php echo esc_url( $og_image ); ?>" alt="OG Image" />
                    </div>
                    <button type="button" class="button wpsm-media-upload-btn">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php esc_html_e( 'Görsel Seç', 'wp-seo-master' ); ?>
                    </button>
                    <button type="button" class="button wpsm-media-remove-btn" <?php echo empty( $og_image ) ? 'style="display:none;"' : ''; ?>>
                        <?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?>
                    </button>
                </div>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Önerilen boyut: 1200x630px. Boş bırakılırsa öne çıkan görsel kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <!-- Twitter Cards -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Twitter Cards', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-twitter-title" class="wpsm-field-label">
                    <?php esc_html_e( 'Twitter Başlığı', 'wp-seo-master' ); ?>
                </label>
                <input
                    type="text"
                    id="wpsm-twitter-title"
                    name="wpsm_twitter_title"
                    value="<?php echo esc_attr( $twitter_title ); ?>"
                    class="wpsm-input"
                    placeholder="<?php echo esc_attr( ! empty( $og_title ) ? $og_title : get_the_title( $post_id ) ); ?>"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Boş bırakılırsa OG başlığı kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-twitter-description" class="wpsm-field-label">
                    <?php esc_html_e( 'Twitter Açıklaması', 'wp-seo-master' ); ?>
                </label>
                <textarea
                    id="wpsm-twitter-description"
                    name="wpsm_twitter_description"
                    class="wpsm-textarea"
                    rows="2"
                    placeholder="<?php echo esc_attr( ! empty( $og_description ) ? $og_description : '' ); ?>"
                ><?php echo esc_textarea( $twitter_description ); ?></textarea>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Boş bırakılırsa OG açıklaması kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-twitter-image" class="wpsm-field-label">
                    <?php esc_html_e( 'Twitter Görseli', 'wp-seo-master' ); ?>
                </label>
                <div class="wpsm-media-upload-wrapper" data-field="wpsm-twitter-image">
                    <input
                        type="hidden"
                        id="wpsm-twitter-image"
                        name="wpsm_twitter_image"
                        value="<?php echo esc_url( $twitter_image ); ?>"
                    />
                    <div class="wpsm-media-preview" <?php echo empty( $twitter_image ) ? 'style="display:none;"' : ''; ?>>
                        <img src="<?php echo esc_url( $twitter_image ); ?>" alt="Twitter Image" />
                    </div>
                    <button type="button" class="button wpsm-media-upload-btn">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php esc_html_e( 'Görsel Seç', 'wp-seo-master' ); ?>
                    </button>
                    <button type="button" class="button wpsm-media-remove-btn" <?php echo empty( $twitter_image ) ? 'style="display:none;"' : ''; ?>>
                        <?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?>
                    </button>
                </div>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Önerilen boyut: 1200x675px. Boş bırakılırsa OG görseli kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- SEKME 3: ŞEMA -->
        <!-- ============================================ -->
        <div class="wpsm-tab-panel" id="wpsm-tab-schema">
            <div class="wpsm-field-group">
                <label for="wpsm-schema-type" class="wpsm-field-label">
                    <?php esc_html_e( 'Schema Türü', 'wp-seo-master' ); ?>
                </label>
                <select id="wpsm-schema-type" name="wpsm_schema_type" class="wpsm-select">
                    <option value="none" <?php selected( $schema_type, 'none' ); ?>>
                        <?php esc_html_e( '— Seçiniz —', 'wp-seo-master' ); ?>
                    </option>
                    <option value="article" <?php selected( $schema_type, 'article' ); ?>>
                        <?php esc_html_e( 'Article (Makale)', 'wp-seo-master' ); ?>
                    </option>
                    <option value="faq" <?php selected( $schema_type, 'faq' ); ?>>
                        <?php esc_html_e( 'FAQ (Sıkça Sorulan Sorular)', 'wp-seo-master' ); ?>
                    </option>
                    <option value="howto" <?php selected( $schema_type, 'howto' ); ?>>
                        <?php esc_html_e( 'HowTo (Nasıl Yapılır)', 'wp-seo-master' ); ?>
                    </option>
                    <option value="product" <?php selected( $schema_type, 'product' ); ?>>
                        <?php esc_html_e( 'Product (Ürün)', 'wp-seo-master' ); ?>
                    </option>
                    <option value="localbusiness" <?php selected( $schema_type, 'localbusiness' ); ?>>
                        <?php esc_html_e( 'LocalBusiness (Yerel İşletme)', 'wp-seo-master' ); ?>
                    </option>
                </select>
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Sayfanızın içeriğine uygun schema türünü seçin. Seçim yapınca ilgili alanlar yüklenecektir.', 'wp-seo-master' ); ?>
                </p>
            </div>

            <!-- Dinamik Schema Alanları -->
            <div id="wpsm-schema-fields" class="wpsm-schema-fields-wrapper">
                <?php if ( 'none' !== $schema_type && ! empty( $schema_type ) ) : ?>
                    <!-- AJAX ile yüklenecek alanlar buraya gelecek -->
                    <div class="wpsm-schema-loader" style="display:none;">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e( 'Alanlar yükleniyor...', 'wp-seo-master' ); ?>
                    </div>
                    <div class="wpsm-schema-dynamic-fields" data-schema-type="<?php echo esc_attr( $schema_type ); ?>">
                        <?php
                        // Mevcut schema tipine göre alanları göster
                        $this->render_schema_fields( $schema_type, $schema_data );
                        ?>
                    </div>
                <?php else : ?>
                    <div class="wpsm-schema-empty">
                        <p><?php esc_html_e( 'Bir schema türü seçerek ilgili alanları görüntüleyin.', 'wp-seo-master' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hidden field for schema data JSON -->
            <input
                type="hidden"
                id="wpsm-schema-data-json"
                name="wpsm_schema_data"
                value="<?php echo esc_attr( $schema_data_json ); ?>"
            />
        </div>

        <!-- ============================================ -->
        <!-- SEKME 4: GELİŞMİŞ -->
        <!-- ============================================ -->
        <div class="wpsm-tab-panel" id="wpsm-tab-advanced">
            <!-- Robots Meta -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Robots Meta', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <p class="wpsm-field-desc" style="margin-bottom: 12px;">
                    <?php esc_html_e( 'Arama motorlarına gönderilecek direktifleri seçin. İşaretlenmezse varsayılan ayarlar kullanılır.', 'wp-seo-master' ); ?>
                </p>

                <div class="wpsm-checkbox-group">
                    <label class="wpsm-checkbox-label">
                        <input
                            type="checkbox"
                            name="wpsm_robots[]"
                            value="noindex"
                            <?php checked( in_array( 'noindex', $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong>noindex</strong> —
                            <?php esc_html_e( 'Sayfayı arama motorlarına indeksletme', 'wp-seo-master' ); ?>
                        </span>
                    </label>

                    <label class="wpsm-checkbox-label">
                        <input
                            type="checkbox"
                            name="wpsm_robots[]"
                            value="nofollow"
                            <?php checked( in_array( 'nofollow', $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong>nofollow</strong> —
                            <?php esc_html_e( 'Sayfadaki bağlantıları takip ettirme', 'wp-seo-master' ); ?>
                        </span>
                    </label>

                    <label class="wpsm-checkbox-label">
                        <input
                            type="checkbox"
                            name="wpsm_robots[]"
                            value="noarchive"
                            <?php checked( in_array( 'noarchive', $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong>noarchive</strong> —
                            <?php esc_html_e( 'Önbelleğe alınmış versiyonu gösterme', 'wp-seo-master' ); ?>
                        </span>
                    </label>

                    <label class="wpsm-checkbox-label">
                        <input
                            type="checkbox"
                            name="wpsm_robots[]"
                            value="nosnippet"
                            <?php checked( in_array( 'nosnippet', $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong>nosnippet</strong> —
                            <?php esc_html_e( 'Snippet gösterme', 'wp-seo-master' ); ?>
                        </span>
                    </label>

                    <label class="wpsm-checkbox-label">
                        <input
                            type="checkbox"
                            name="wpsm_robots[]"
                            value="noimageindex"
                            <?php checked( in_array( 'noimageindex', $robots, true ) ); ?>
                        />
                        <span class="wpsm-checkbox-text">
                            <strong>noimageindex</strong> —
                            <?php esc_html_e( 'Görselleri indeksletme', 'wp-seo-master' ); ?>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Breadcrumb Başlığı -->
            <div class="wpsm-section-header">
                <h4><?php esc_html_e( 'Breadcrumb', 'wp-seo-master' ); ?></h4>
            </div>

            <div class="wpsm-field-group">
                <label for="wpsm-breadcrumb-title" class="wpsm-field-label">
                    <?php esc_html_e( 'Breadcrumb Başlığı', 'wp-seo-master' ); ?>
                </label>
                <input
                    type="text"
                    id="wpsm-breadcrumb-title"
                    name="wpsm_breadcrumb_title"
                    value="<?php echo esc_attr( $breadcrumb_title ); ?>"
                    class="wpsm-input"
                    placeholder="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"
                />
                <p class="wpsm-field-desc">
                    <?php esc_html_e( 'Breadcrumb navigasyonunda görünecek metin. Boş bırakılırsa yazı başlığı kullanılır.', 'wp-seo-master' ); ?>
                </p>
            </div>
        </div>

    </div><!-- .wpsm-tabs-content -->
</div><!-- .wpsm-metabox-wrapper -->

<?php
/**
 * Schema alanlarını render eden metod (Class_Metabox içinde tanımlı olmalı)
 * Bu kısım class-metabox.php dosyasına taşınmalıdır.
 * Template dosyasında doğrudan çağrılabilir.
 */
