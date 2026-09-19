<?php
/**
 * Meta Tags Sınıfı
 *
 * Frontend'de meta etiketlerini oluşturur ve wp_head hook'unda çıktılar.
 * Title, description, canonical, robots meta etiketleri.
 *
 * @package WPSM\Frontend
 * @since 1.0.0
 */

namespace WPSM\Frontend;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Class_Meta_Tags
 *
 * wp_head hook'unda meta etiketlerini oluşturur.
 * Priority 1 ile çalışır (diğer eklentilerden önce).
 */
class Class_Meta_Tags {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Diğer SEO eklentileri aktif mi?
     *
     * @var bool
     */
    private $other_seo_active = false;

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;

        // Diğer SEO eklentilerini kontrol et
        $this->check_other_seo_plugins();
    }

    /**
     * Diğer SEO eklentilerinin aktif olup olmadığını kontrol et
     *
     * Yoast SEO, All in One SEO, Rank Math gibi eklentiler aktifse
     * bu eklentinin meta tag çıktılarını devre dışı bırakır.
     */
    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION',      // Yoast SEO
            'AIOSEO_VERSION',     // All in One SEO
            'RANK_MATH_VERSION',  // Rank Math
            'SEOPRESS_VERSION',   // SEOPress
        );

        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;

                // Admin'de uyarı göster
                if ( is_admin() && current_user_can( 'manage_options' ) ) {
                    add_action( 'admin_notices', array( $this, 'other_seo_notice' ) );
                }

                break;
            }
        }
    }

    /**
     * Diğer SEO eklentisi uyarısı
     */
    public function other_seo_notice() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %s: eklenti adı */
                    esc_html__( 'WP SEO Master: Başka bir SEO eklentisi aktif görünüyor. Çakışmayı önlemek için WP SEO Master meta etiketleri devre dışı bırakıldı. Sadece bir SEO eklentisi kullanmanız önerilir.', 'wp-seo-master' )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Meta etiketlerini çıktıla
     *
     * wp_head hook'unda çağrılır (priority 1).
     * Diğer SEO eklentileri aktifse çıktı vermez.
     */
    public function output_meta_tags() {
        // Diğer SEO eklentileri aktifse çık
        if ( $this->other_seo_active ) {
            return;
        }

        // Title tag (pre_get_document_title filter ile)
        // Bu metod sadece description ve robots meta için kullanılır

        // Meta Description
        $this->output_description();

        // Robots Meta
        $this->output_robots();

        // Webmaster Verification Kodları
        $this->output_verification_codes();
    }

    /**
     * Title tag'i override et
     *
     * pre_get_document_title filter'ı ile çalışır (WP 4.4+).
     * Öncelik: post meta _wpsm_title > şablon > site başlığı
     *
     * @param string $title Varsayılan başlık
     * @param string $sep   Ayırıcı
     * @param string $seplocation Ayırıcı konumu (right/left)
     * @return string Düzenlenmiş başlık
     */
    public function filter_document_title( $title, $sep, $seplocation ) {
        // Diğer SEO eklentileri aktifse çık
        if ( $this->other_seo_active ) {
            return $title;
        }

        // Options'tan ayırıcı al
        $sep = $this->options->get( 'title_separator', '|' );

        // Singular sayfa (post, page, CPT)
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $custom_title = get_post_meta( $post_id, '_wpsm_title', true );

            if ( ! empty( $custom_title ) ) {
                // Şablon değişkenlerini değiştir
                return $this->replace_template_variables( $custom_title, $sep );
            }

            // Özel başlık yoksa varsayılan başlığı kullan
            return $title;
        }

        // Ana sayfa
        if ( is_front_page() ) {
            $home_title = $this->options->get( 'home_title', '' );

            if ( ! empty( $home_title ) ) {
                return $this->replace_template_variables( $home_title, $sep );
            }
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            return $this->get_archive_title( $sep );
        }

        // Arama sayfası
        if ( is_search() ) {
            $search_query = get_search_query();
            /* translators: %s: arama sorgusu */
            return sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query ) . ' ' . $sep . ' ' . get_bloginfo( 'name' );
        }

        // 404 sayfası
        if ( is_404() ) {
            return __( 'Sayfa Bulunamadı', 'wp-seo-master' ) . ' ' . $sep . ' ' . get_bloginfo( 'name' );
        }

        // Varsayılan
        return $title;
    }

    /**
     * Şablon değişkenlerini değiştir
     *
     * Desteklenen değişkenler:
     * %%title%%, %%sitename%%, %%sep%%, %%page%%, %%category%%, %%tag%%, %%search_query%%
     *
     * @param string $template Şablon
     * @param string $sep      Ayırıcı
     * @return string Değiştirilmiş şablon
     */
    private function replace_template_variables( $template, $sep = '' ) {
        // Ayırıcı
        if ( empty( $sep ) ) {
            $sep = $this->options->get( 'title_separator', '|' );
        }

        $replacements = array(
            '%%title%%'        => $this->get_page_title(),
            '%%sitename%%'     => get_bloginfo( 'name' ),
            '%%sep%%'          => $sep,
            '%%page%%'         => $this->get_page_number(),
            '%%category%%'     => $this->get_category_name(),
            '%%tag%%'          => $this->get_tag_name(),
            '%%search_query%%' => get_search_query(),
            '%%date%%'         => $this->get_date_archive_title(),
            '%%author%%'       => $this->get_author_name(),
        );

        /**
         * Şablon değişkenlerini filtrele
         *
         * @param array $replacements Değişkenler
         */
        $replacements = apply_filters( 'wpsm_title_template_variables', $replacements );

        $result = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

        return trim( $result );
    }

    /**
     * Sayfa başlığını döndür
     *
     * @return string
     */
    private function get_page_title() {
        if ( is_singular() ) {
            return get_the_title();
        }

        if ( is_front_page() ) {
            return get_bloginfo( 'name' );
        }

        if ( is_archive() ) {
            return get_the_archive_title();
        }

        if ( is_search() ) {
            /* translators: %s: arama sorgusu */
            return sprintf( __( 'Arama: %s', 'wp-seo-master' ), get_search_query() );
        }

        if ( is_404() ) {
            return __( 'Sayfa Bulunamadı', 'wp-seo-master' );
        }

        return '';
    }

    /**
     * Sayfa numarasını döndür
     *
     * @return string
     */
    private function get_page_number() {
        $page = get_query_var( 'paged' );

        if ( $page && $page > 1 ) {
            /* translators: %s: sayfa numarası */
            return sprintf( __( 'Sayfa %s', 'wp-seo-master' ), $page );
        }

        return '';
    }

    /**
     * Kategori adını döndür
     *
     * @return string
     */
    private function get_category_name() {
        if ( is_category() ) {
            return single_cat_title( '', false );
        }

        if ( is_singular() ) {
            $categories = get_the_category();
            if ( ! empty( $categories ) ) {
                return $categories[0]->name;
            }
        }

        return '';
    }

    /**
     * Etiket adını döndür
     *
     * @return string
     */
    private function get_tag_name() {
        if ( is_tag() ) {
            return single_tag_title( '', false );
        }

        if ( is_singular() ) {
            $tags = get_the_tags();
            if ( ! empty( $tags ) ) {
                return $tags[0]->name;
            }
        }

        return '';
    }

    /**
     * Tarih arşivi başlığını döndür
     *
     * @return string
     */
    private function get_date_archive_title() {
        if ( is_date() ) {
            return get_the_date();
        }
        return '';
    }

    /**
     * Yazar adını döndür
     *
     * @return string
     */
    private function get_author_name() {
        if ( is_author() ) {
            return get_the_author();
        }

        if ( is_singular() ) {
            return get_the_author();
        }

        return '';
    }

    /**
     * Arşiv başlığını döndür
     *
     * @param string $sep Ayırıcı
     * @return string
     */
    private function get_archive_title( $sep ) {
        $title = get_the_archive_title();
        $site_name = get_bloginfo( 'name' );

        return $title . ' ' . $sep . ' ' . $site_name;
    }

    /**
     * Meta description çıktısı
     *
     * Öncelik: post meta _wpsm_description > excerpt > otomatik ilk 160 karakter
     */
    private function output_description() {
        $description = '';

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $description = get_post_meta( $post_id, '_wpsm_description', true );

            // Özel açıklama yoksa excerpt kullan
            if ( empty( $description ) ) {
                $description = get_the_excerpt( $post_id );
            }

            // Excerpt yoksa içerikten otomatik oluştur
            if ( empty( $description ) ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' );
                }
            }
        }

        // Ana sayfa
        if ( is_front_page() ) {
            $description = $this->options->get( 'home_description', '' );

            if ( empty( $description ) ) {
                $description = get_bloginfo( 'description' );
            }
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            $description = get_the_archive_description();
            $description = wp_strip_all_tags( $description );
        }

        // Açıklama varsa çıktı ver
        if ( ! empty( $description ) ) {
            // 160 karakter ile sınırla
            if ( strlen( $description ) > 160 ) {
                $description = substr( $description, 0, 157 ) . '...';
            }

            echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
        }
    }

    /**
     * Robots meta çıktısı
     *
     * Arama sonuçları, 404, tarih arşivleri için otomatik noindex.
     * post meta _wpsm_robots ile override edilebilir.
     */
    private function output_robots() {
        $robots = array();

        // Otomatik noindex durumları
        if ( is_search() || is_404() ) {
            $robots[] = 'noindex';
        }

        // Tarih arşivleri (yıl/ay arşivleri)
        if ( is_date() ) {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        }

        // Singular sayfa - post meta kontrolü
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post_robots = get_post_meta( $post_id, '_wpsm_robots', true );

            if ( ! empty( $post_robots ) && is_array( $post_robots ) ) {
                $robots = $post_robots;
            }
        }

        // Global noindex ayarları
        $global_noindex = $this->options->get( 'global_noindex', array() );

        if ( is_category() && in_array( 'category', $global_noindex, true ) ) {
            if ( ! in_array( 'noindex', $robots, true ) ) {
                $robots[] = 'noindex';
            }
        }

        if ( is_tag() && in_array( 'tag', $global_noindex, true ) ) {
            if ( ! in_array( 'noindex', $robots, true ) ) {
                $robots[] = 'noindex';
            }
        }

        // Robots dizisi boşsa çık
        if ( empty( $robots ) ) {
            return;
        }

        // Robots meta etiketini çıktı ver
        $robots_string = implode( ', ', $robots );
        echo '<meta name="robots" content="' . esc_attr( $robots_string ) . '" />' . "\n";
    }

    /**
     * Webmaster doğrulama kodlarını çıktı ver
     */
    private function output_verification_codes() {
        // Sadece ana sayfada göster
        if ( ! is_front_page() ) {
            return;
        }

        // Google
        $google = $this->options->get( 'google_verification', '' );
        if ( ! empty( $google ) ) {
            echo '<meta name="google-site-verification" content="' . esc_attr( $google ) . '" />' . "\n";
        }

        // Bing
        $bing = $this->options->get( 'bing_verification', '' );
        if ( ! empty( $bing ) ) {
            echo '<meta name="msvalidate.01" content="' . esc_attr( $bing ) . '" />' . "\n";
        }

        // Yandex
        $yandex = $this->options->get( 'yandex_verification', '' );
        if ( ! empty( $yandex ) ) {
            echo '<meta name="yandex-verification" content="' . esc_attr( $yandex ) . '" />' . "\n";
        }

        // Pinterest
        $pinterest = $this->options->get( 'pinterest_verification', '' );
        if ( ! empty( $pinterest ) ) {
            echo '<meta name="p:domain_verify" content="' . esc_attr( $pinterest ) . '" />' . "\n";
        }
    }
}
