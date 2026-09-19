<?php
/**
 * Sitemap Generator Sınıfı
 *
 * XML sitemap dosyalarını oluşturur.
 * /sitemap.xml ve alt sitemap'ler için endpoint'ler.
 *
 * @package WPSM\Sitemap
 * @since 1.0.0
 */

namespace WPSM\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Sitemap_Generator {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * URL başına maksimum sayı
     *
     * @var int
     */
    const MAX_URLS = 1000;

    /**
     * Cache süresi (12 saat)
     *
     * @var int
     */
    const CACHE_DURATION = 12 * HOUR_IN_SECONDS;

    /**
     * Sitemap tipleri
     *
     * @var array
     */
    private $sitemap_types = array(
        'post'     => 'post-sitemap.xml',
        'page'     => 'page-sitemap.xml',
        'category' => 'category-sitemap.xml',
        'post_tag' => 'post_tag-sitemap.xml',
        'author'   => 'author-sitemap.xml',
    );

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Rewrite kurallarını kaydet
     *
     * init hook'unda çağrılır.
     */
    public function register_rewrite_rules() {
        $enabled = $this->options->get( 'enable_sitemap', true );
        if ( ! $enabled ) {
            return;
        }

        // Ana sitemap index
        add_rewrite_rule(
            'sitemap_index\.xml$',
            'index.php?wpsm_sitemap=index',
            'top'
        );

        // Alt sitemap'ler
        add_rewrite_rule(
            '([^/]+)-sitemap\.xml$',
            'index.php?wpsm_sitemap=1&wpsm_sitemap_type=$matches[1]',
            'top'
        );

        // Sitemap query var'ları
        add_rewrite_tag( '%wpsm_sitemap%', '([^&]+)' );
        add_rewrite_tag( '%wpsm_sitemap_type%', '([^&]+)' );
    }

    /**
     * Sitemap'i render et
     *
     * template_redirect hook'unda çağrılır.
     */
    public function render_sitemap() {
        $sitemap = get_query_var( 'wpsm_sitemap' );

        if ( empty( $sitemap ) ) {
            return;
        }

        $enabled = $this->options->get( 'enable_sitemap', true );
        if ( ! $enabled ) {
            return;
        }

        $sitemap_type = get_query_var( 'wpsm_sitemap_type' );

        // Ana index
        if ( 'index' === $sitemap ) {
            $this->render_sitemap_index();
            exit;
        }

        // Alt sitemap
        if ( ! empty( $sitemap_type ) ) {
            $this->render_sub_sitemap( $sitemap_type );
            exit;
        }
    }

    /**
     * Sitemap index'i render et
     */
    private function render_sitemap_index() {
        // Cache kontrolü
        $cache_key = 'wpsm_sitemap_index';
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            $this->output_xml( $cached );
            return;
        }

        $index = new Class_Sitemap_Index( $this->options );
        $xml = $index->generate();

        set_transient( $cache_key, $xml, self::CACHE_DURATION );

        $this->output_xml( $xml );
    }

    /**
     * Alt sitemap'i render et
     *
     * @param string $type Sitemap tipi (post, page, category, post_tag, author)
     */
    private function render_sub_sitemap( $type ) {
        // Cache kontrolü
        $cache_key = 'wpsm_sitemap_' . $type;
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            $this->output_xml( $cached );
            return;
        }

        $xml = $this->generate_sitemap( $type );

        if ( ! empty( $xml ) ) {
            set_transient( $cache_key, $xml, self::CACHE_DURATION );
            $this->output_xml( $xml );
        } else {
            status_header( 404 );
            echo 'Sitemap not found';
            exit;
        }
    }

    /**
     * Sitemap XML'ini oluştur
     *
     * @param string $type Sitemap tipi
     * @return string XML içeriği
     */
    public function generate_sitemap( $type ) {
        // Dahil edilecek post type'ları kontrol et
        $post_types = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        $taxonomies = $this->options->get( 'sitemap_taxonomies', array( 'category' ) );

        switch ( $type ) {
            case 'post':
                return $this->generate_post_sitemap( 'post' );

            case 'page':
                return $this->generate_post_sitemap( 'page' );

            case 'category':
                return $this->generate_taxonomy_sitemap( 'category' );

            case 'post_tag':
                return $this->generate_taxonomy_sitemap( 'post_tag' );

            case 'author':
                return $this->generate_author_sitemap();

            default:
                // Custom post type
                if ( in_array( $type, $post_types, true ) ) {
                    return $this->generate_post_sitemap( $type );
                }
                // Custom taxonomy
                if ( in_array( $type, $taxonomies, true ) ) {
                    return $this->generate_taxonomy_sitemap( $type );
                }
                return '';
        }
    }

    /**
     * Post sitemap oluştur
     *
     * @param string $post_type Post type
     * @return string XML
     */
    private function generate_post_sitemap( $post_type ) {
        $posts_per_page = $this->options->get( 'sitemap_posts_per_page', self::MAX_URLS );

        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => min( $posts_per_page, self::MAX_URLS ),
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_wpsm_robots',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_wpsm_robots',
                    'value'   => 'noindex',
                    'compare' => 'NOT LIKE',
                ),
            ),
        );

        $posts = get_posts( $args );

        if ( empty( $posts ) ) {
            return '';
        }

        $xml = $this->get_xml_header();

        foreach ( $posts as $post ) {
            // noindex kontrolü
            $robots = get_post_meta( $post->ID, '_wpsm_robots', true );
            if ( is_array( $robots ) && in_array( 'noindex', $robots, true ) ) {
                continue;
            }

            $url = array(
                'loc'        => get_permalink( $post->ID ),
                'lastmod'    => mysql2date( 'Y-m-d\TH:i:sP', $post->post_modified_gmt ),
                'changefreq' => $this->get_changefreq( $post ),
                'priority'   => $this->get_priority( $post, $post_type ),
            );

            // Görsel sitemap
            $images = $this->get_post_images( $post );
            if ( ! empty( $images ) ) {
                $url['images'] = $images;
            }

            $xml .= $this->build_url_node( $url );
        }

        $xml .= $this->get_xml_footer();

        return $xml;
    }

    /**
     * Taksonomi sitemap oluştur
     *
     * @param string $taxonomy Taksonomi adı
     * @return string XML
     */
    private function generate_taxonomy_sitemap( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'number'     => self::MAX_URLS,
        ) );

        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }

        $xml = $this->get_xml_header();

        foreach ( $terms as $term ) {
            $url = array(
                'loc'        => get_term_link( $term ),
                'lastmod'    => $this->get_term_lastmod( $term, $taxonomy ),
                'changefreq' => 'weekly',
                'priority'   => '0.5',
            );

            $xml .= $this->build_url_node( $url );
        }

        $xml .= $this->get_xml_footer();

        return $xml;
    }

    /**
     * Yazar sitemap oluştur
     *
     * @return string XML
     */
    private function generate_author_sitemap() {
        $authors = get_users( array(
            'has_published_posts' => true,
            'number'              => self::MAX_URLS,
        ) );

        if ( empty( $authors ) ) {
            return '';
        }

        $xml = $this->get_xml_header();

        foreach ( $authors as $author ) {
            $url = array(
                'loc'        => get_author_posts_url( $author->ID ),
                'lastmod'    => $this->get_author_lastmod( $author->ID ),
                'changefreq' => 'weekly',
                'priority'   => '0.3',
            );

            $xml .= $this->build_url_node( $url );
        }

        $xml .= $this->get_xml_footer();

        return $xml;
    }

    /**
     * XML header döndür
     *
     * @return string
     */
    private function get_xml_header() {
        $header  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $header .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $header .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        $header .= '>' . "\n";

        return $header;
    }

    /**
     * XML footer döndür
     *
     * @return string
     */
    private function get_xml_footer() {
        return '</urlset>';
    }

    /**
     * URL node oluştur
     *
     * @param array $url URL verisi
     * @return string XML
     */
    private function build_url_node( $url ) {
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url( $url['loc'] ) . "</loc>\n";

        if ( ! empty( $url['lastmod'] ) ) {
            $xml .= "\t\t<lastmod>" . esc_html( $url['lastmod'] ) . "</lastmod>\n";
        }

        if ( ! empty( $url['changefreq'] ) ) {
            $xml .= "\t\t<changefreq>" . esc_html( $url['changefreq'] ) . "</changefreq>\n";
        }

        if ( ! empty( $url['priority'] ) ) {
            $xml .= "\t\t<priority>" . esc_html( $url['priority'] ) . "</priority>\n";
        }

        // Görsel sitemap
        if ( ! empty( $url['images'] ) ) {
            foreach ( $url['images'] as $image ) {
                $xml .= "\t\t<image:image>\n";
                $xml .= "\t\t\t<image:loc>" . esc_url( $image['loc'] ) . "</image:loc>\n";
                if ( ! empty( $image['caption'] ) ) {
                    $xml .= "\t\t\t<image:caption>" . esc_html( $image['caption'] ) . "</image:caption>\n";
                }
                if ( ! empty( $image['title'] ) ) {
                    $xml .= "\t\t\t<image:title>" . esc_html( $image['title'] ) . "</image:title>\n";
                }
                $xml .= "\t\t</image:image>\n";
            }
        }

        $xml .= "\t</url>\n";

        return $xml;
    }

    /**
     * Post görsellerini döndür
     *
     * @param \WP_Post $post Post objesi
     * @return array
     */
    private function get_post_images( $post ) {
        $images = array();

        // Featured image
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        if ( $thumbnail_id ) {
            $src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $src ) {
                $alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                $images[] = array(
                    'loc'     => $src[0],
                    'caption' => $alt,
                    'title'   => get_the_title( $thumbnail_id ),
                );
            }
        }

        // İçerikteki görseller (max 5)
        if ( count( $images ) < 5 ) {
            preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $matches );

            if ( ! empty( $matches[1] ) ) {
                $content_images = array_slice( $matches[1], 0, 5 - count( $images ) );
                foreach ( $content_images as $img_url ) {
                    $images[] = array(
                        'loc'     => $img_url,
                        'caption' => '',
                        'title'   => '',
                    );
                }
            }
        }

        return $images;
    }

    /**
     * Değişim sıklığını döndür
     *
     * @param \WP_Post $post Post objesi
     * @return string
     */
    private function get_changefreq( $post ) {
        $days_old = ( time() - strtotime( $post->post_date_gmt ) ) / DAY_IN_SECONDS;

        if ( $days_old < 1 ) {
            return 'hourly';
        } elseif ( $days_old < 7 ) {
            return 'daily';
        } elseif ( $days_old < 30 ) {
            return 'weekly';
        } elseif ( $days_old < 365 ) {
            return 'monthly';
        }

        return 'yearly';
    }

    /**
     * Öncelik değerini döndür
     *
     * @param \WP_Post $post      Post objesi
     * @param string   $post_type Post type
     * @return string
     */
    private function get_priority( $post, $post_type ) {
        // Ana sayfa
        if ( (int) get_option( 'page_on_front' ) === $post->ID ) {
            return '1.0';
        }

        // Sayfa
        if ( 'page' === $post_type ) {
            return '0.6';
        }

        // Sticky post
        if ( is_sticky( $post->ID ) ) {
            return '0.9';
        }

        // Varsayılan post
        return '0.7';
    }

    /**
     * Taksonomi son güncelleme tarihini döndür
     *
     * @param object $term     Term objesi
     * @param string $taxonomy Taksonomi adı
     * @return string
     */
    private function get_term_lastmod( $term, $taxonomy ) {
        $latest_post = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'terms'    => $term->term_id,
                ),
            ),
        ) );

        if ( ! empty( $latest_post ) ) {
            return mysql2date( 'Y-m-d\TH:i:sP', $latest_post[0]->post_modified_gmt );
        }

        return mysql2date( 'Y-m-d\TH:i:sP', current_time( 'mysql', true ) );
    }

    /**
     * Yazar son güncelleme tarihini döndür
     *
     * @param int $author_id Yazar ID
     * @return string
     */
    private function get_author_lastmod( $author_id ) {
        $latest_post = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'author'         => $author_id,
        ) );

        if ( ! empty( $latest_post ) ) {
            return mysql2date( 'Y-m-d\TH:i:sP', $latest_post[0]->post_modified_gmt );
        }

        return mysql2date( 'Y-m-d\TH:i:sP', current_time( 'mysql', true ) );
    }

    /**
     * XML çıktısı ver
     *
     * @param string $xml XML içeriği
     */
    private function output_xml( $xml ) {
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex, follow' );

        echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Sitemap cache'ini temizle
     *
     * @param string $type Sitemap tipi (boşsa tüm cache)
     */
    public function clear_cache( $type = '' ) {
        if ( empty( $type ) ) {
            // Tüm cache'leri temizle
            delete_transient( 'wpsm_sitemap_index' );
            foreach ( array_keys( $this->sitemap_types ) as $sitemap_type ) {
                delete_transient( 'wpsm_sitemap_' . $sitemap_type );
            }
        } else {
            delete_transient( 'wpsm_sitemap_' . $type );
            delete_transient( 'wpsm_sitemap_index' );
        }

        /**
         * Sitemap cache temizlendi action
         *
         * @param string $type Sitemap tipi
         */
        do_action( 'wpsm_sitemap_cache_cleared', $type );
    }

    /**
     * Sitemap URL'lerini döndür
     *
     * @return array
     */
    public function get_sitemap_urls() {
        $urls = array();

        // Ana index
        $urls['index'] = home_url( '/sitemap_index.xml' );

        // Alt sitemap'ler
        $post_types = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        $taxonomies = $this->options->get( 'sitemap_taxonomies', array( 'category' ) );

        foreach ( $post_types as $post_type ) {
            $urls[ $post_type ] = home_url( '/' . $post_type . '-sitemap.xml' );
        }

        foreach ( $taxonomies as $taxonomy ) {
            $urls[ $taxonomy ] = home_url( '/' . $taxonomy . '-sitemap.xml' );
        }

        // Author sitemap (opsiyonel)
        if ( $this->options->get( 'enable_author_sitemap', false ) ) {
            $urls['author'] = home_url( '/author-sitemap.xml' );
        }

        return $urls;
    }

    /**
     * Post/taxonomy değiştiğinde cache'i temizle
     *
     * @param int $post_id Post ID
     */
    public function clear_cache_on_save( $post_id ) {
        $post_type = get_post_type( $post_id );
        $this->clear_cache( $post_type );

        // Taksonomi cache'lerini de temizle
        $taxonomies = get_object_taxonomies( $post_type );
        foreach ( $taxonomies as $taxonomy ) {
            $this->clear_cache( $taxonomy );
        }
    }
}
