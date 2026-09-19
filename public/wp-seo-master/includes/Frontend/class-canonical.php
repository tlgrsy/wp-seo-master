<?php
/**
 * Canonical Sınıfı
 *
 * Canonical URL çıktısını yönetir.
 * rel="canonical" ve opsiyonel rel="prev"/"next" etiketleri.
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
 * Class Class_Canonical
 *
 * Canonical URL'leri oluşturur ve wp_head'de çıktılar.
 */
class Class_Canonical {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Canonical URL çıktısı
     *
     * wp_head hook'unda çağrılır.
     * Öncelik: post meta _wpsm_canonical > get_permalink() > home_url()
     */
    public function output_canonical() {
        $canonical = $this->get_canonical_url();

        if ( ! empty( $canonical ) ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }

        // Pagination için prev/next (opsiyonel)
        $this->output_pagination_links();
    }

    /**
     * Canonical URL'ini döndür
     *
     * @return string Canonical URL
     */
    public function get_canonical_url() {
        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Post meta'dan özel canonical URL
            $custom_canonical = get_post_meta( $post_id, '_wpsm_canonical', true );

            if ( ! empty( $custom_canonical ) ) {
                return esc_url( $custom_canonical );
            }

            // Varsayılan permalink
            return get_permalink( $post_id );
        }

        // Ana sayfa
        if ( is_front_page() ) {
            return home_url( '/' );
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            return $this->get_archive_canonical();
        }

        // Arama sayfası
        if ( is_search() ) {
            return get_search_link();
        }

        // 404 sayfası
        if ( is_404() ) {
            return home_url( '/' );
        }

        // Varsayılan
        return home_url( $_SERVER['REQUEST_URI'] );
    }

    /**
     * Arşiv canonical URL'ini döndür
     *
     * @return string
     */
    private function get_archive_canonical() {
        // Kategori arşivi
        if ( is_category() ) {
            return get_category_link( get_queried_object_id() );
        }

        // Etiket arşivi
        if ( is_tag() ) {
            return get_tag_link( get_queried_object_id() );
        }

        // Taksonomi arşivi
        if ( is_tax() ) {
            return get_term_link( get_queried_object() );
        }

        // Yazar arşivi
        if ( is_author() ) {
            return get_author_posts_url( get_queried_object_id() );
        }

        // Tarih arşivi
        if ( is_date() ) {
            if ( is_day() ) {
                return get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
            } elseif ( is_month() ) {
                return get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
            } elseif ( is_year() ) {
                return get_year_link( get_query_var( 'year' ) );
            }
        }

        // Post type arşivi
        if ( is_post_type_archive() ) {
            return get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        // Varsayılan
        return home_url( $_SERVER['REQUEST_URI'] );
    }

    /**
     * Pagination için prev/next linklerini çıktıla
     *
     * WP 4.1+ kaldırıldı ama bazı temalar için opsiyonel.
     *
     * @return void
     */
    private function output_pagination_links() {
        global $wp_query;

        // Sayfalama var mı kontrol et
        $paged = get_query_var( 'paged' );

        if ( ! $paged || $paged < 2 ) {
            return;
        }

        // Toplam sayfa sayısı
        $max_pages = $wp_query->max_num_pages;

        // Önceki sayfa
        if ( $paged > 2 ) {
            $prev_url = get_pagenum_link( $paged - 1 );
            echo '<link rel="prev" href="' . esc_url( $prev_url ) . '" />' . "\n";
        }

        // Sonraki sayfa
        if ( $paged < $max_pages ) {
            $next_url = get_pagenum_link( $paged + 1 );
            echo '<link rel="next" href="' . esc_url( $next_url ) . '" />' . "\n";
        }
    }

    /**
     * Canonical URL'ini manuel olarak döndür
     *
     * Diğer modüller tarafından kullanılabilir.
     *
     * @param int $post_id Post ID (opsiyonel)
     * @return string
     */
    public function get_post_canonical( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( ! $post_id ) {
            return '';
        }

        $custom_canonical = get_post_meta( $post_id, '_wpsm_canonical', true );

        if ( ! empty( $custom_canonical ) ) {
            return esc_url( $custom_canonical );
        }

        return get_permalink( $post_id );
    }
}
