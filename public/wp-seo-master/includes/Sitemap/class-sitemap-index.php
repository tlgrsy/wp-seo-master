<?php
/**
 * Sitemap Index Sınıfı
 *
 * Ana sitemap index dosyasını oluşturur.
 * Tüm alt sitemap'leri listeler.
 *
 * @package WPSM\Sitemap
 * @since 1.0.0
 */

namespace WPSM\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Sitemap_Index {

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
     * Sitemap index XML'ini oluştur
     *
     * @return string XML içeriği
     */
    public function generate() {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Dahil edilecek post type'lar
        $post_types = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        foreach ( $post_types as $post_type ) {
            $xml .= $this->build_sitemap_node(
                home_url( '/' . $post_type . '-sitemap.xml' ),
                $this->get_last_modified( $post_type, 'post_type' )
            );
        }

        // Dahil edilecek taksonomiler
        $taxonomies = $this->options->get( 'sitemap_taxonomies', array( 'category' ) );
        foreach ( $taxonomies as $taxonomy ) {
            $xml .= $this->build_sitemap_node(
                home_url( '/' . $taxonomy . '-sitemap.xml' ),
                $this->get_last_modified( $taxonomy, 'taxonomy' )
            );
        }

        // Yazar sitemap (opsiyonel)
        if ( $this->options->get( 'enable_author_sitemap', false ) ) {
            $xml .= $this->build_sitemap_node(
                home_url( '/author-sitemap.xml' ),
                $this->get_author_last_modified()
            );
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Sitemap node oluştur
     *
     * @param string $loc     Sitemap URL
     * @param string $lastmod Son değişiklik tarihi
     * @return string XML
     */
    private function build_sitemap_node( $loc, $lastmod = '' ) {
        $xml  = "\t<sitemap>\n";
        $xml .= "\t\t<loc>" . esc_url( $loc ) . "</loc>\n";

        if ( ! empty( $lastmod ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
        }

        $xml .= "\t</sitemap>\n";

        return $xml;
    }

    /**
     * Son değişiklik tarihini döndür
     *
     * @param string $type  Post type veya taxonomy adı
     * @param string $scope 'post_type' veya 'taxonomy'
     * @return string ISO 8601 tarih
     */
    private function get_last_modified( $type, $scope ) {
        if ( 'post_type' === $scope ) {
            $latest = get_posts( array(
                'post_type'      => $type,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ) );

            if ( ! empty( $latest ) ) {
                $post = get_post( $latest[0] );
                return mysql2date( 'Y-m-d\TH:i:sP', $post->post_modified_gmt );
            }
        }

        if ( 'taxonomy' === $scope ) {
            $terms = get_terms( array(
                'taxonomy'   => $type,
                'hide_empty' => true,
                'number'     => 1,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                // Bu taksonomideki en son güncellenen post
                $latest = get_posts( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'orderby'        => 'modified',
                    'order'          => 'DESC',
                    'tax_query'      => array(
                        array(
                            'taxonomy' => $type,
                            'terms'    => $terms[0]->term_id,
                        ),
                    ),
                ) );

                if ( ! empty( $latest ) ) {
                    return mysql2date( 'Y-m-d\TH:i:sP', $latest[0]->post_modified_gmt );
                }
            }
        }

        return mysql2date( 'Y-m-d\TH:i:sP', current_time( 'mysql', true ) );
    }

    /**
     * Yazar sitemap son değişiklik tarihi
     *
     * @return string ISO 8601 tarih
     */
    private function get_author_last_modified() {
        $latest = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );

        if ( ! empty( $latest ) ) {
            return mysql2date( 'Y-m-d\TH:i:sP', $latest[0]->post_modified_gmt );
        }

        return mysql2date( 'Y-m-d\TH:i:sP', current_time( 'mysql', true ) );
    }
}
