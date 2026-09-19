import { useState } from 'react'

const files = [
  {
    id: 'sitemap-generator',
    name: 'class-sitemap-generator.php',
    path: 'includes/Sitemap/class-sitemap-generator.php',
    description: 'XML Sitemap oluşturucu - rewrite rules, cache, görsel sitemap',
    language: 'php',
    code: `<?php
/**
 * Sitemap Generator Sınıfı
 *
 * XML sitemap dosyalarını oluşturur.
 * /sitemap.xml ve alt sitemap'ler için endpoint'ler.
 *
 * @package WPSM\\Sitemap
 * @since 1.0.0
 */

namespace WPSM\\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Sitemap_Generator {

    private $options;
    const MAX_URLS = 1000;
    const CACHE_DURATION = 12 * HOUR_IN_SECONDS;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Rewrite kurallarını kaydet (init hook)
     */
    public function register_rewrite_rules() {
        $enabled = $this->options->get( 'enable_sitemap', true );
        if ( ! $enabled ) return;

        // Ana sitemap index
        add_rewrite_rule(
            'sitemap_index\\.xml$',
            'index.php?wpsm_sitemap=index',
            'top'
        );

        // Alt sitemap'ler
        add_rewrite_rule(
            '([^/]+)-sitemap\\.xml$',
            'index.php?wpsm_sitemap=1&wpsm_sitemap_type=$matches[1]',
            'top'
        );

        // Query var'ları
        add_rewrite_tag( '%wpsm_sitemap%', '([^&]+)' );
        add_rewrite_tag( '%wpsm_sitemap_type%', '([^&]+)' );
    }

    /**
     * Sitemap'i render et (template_redirect hook)
     */
    public function render_sitemap() {
        $sitemap = get_query_var( 'wpsm_sitemap' );
        if ( empty( $sitemap ) ) return;

        $enabled = $this->options->get( 'enable_sitemap', true );
        if ( ! $enabled ) return;

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
     * Sitemap index render
     */
    private function render_sitemap_index() {
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
     * Alt sitemap render
     */
    private function render_sub_sitemap( $type ) {
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
     * Sitemap XML oluştur
     */
    public function generate_sitemap( $type ) {
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
                if ( in_array( $type, $post_types, true ) ) {
                    return $this->generate_post_sitemap( $type );
                }
                if ( in_array( $type, $taxonomies, true ) ) {
                    return $this->generate_taxonomy_sitemap( $type );
                }
                return '';
        }
    }

    /**
     * Post sitemap oluştur
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
                array( 'key' => '_wpsm_robots', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_wpsm_robots', 'value' => 'noindex', 'compare' => 'NOT LIKE' ),
            ),
        );

        $posts = get_posts( $args );
        if ( empty( $posts ) ) return '';

        $xml = $this->get_xml_header();

        foreach ( $posts as $post ) {
            // noindex kontrolü
            $robots = get_post_meta( $post->ID, '_wpsm_robots', true );
            if ( is_array( $robots ) && in_array( 'noindex', $robots, true ) ) {
                continue;
            }

            $url = array(
                'loc'        => get_permalink( $post->ID ),
                'lastmod'    => mysql2date( 'Y-m-d\\TH:i:sP', $post->post_modified_gmt ),
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
     */
    private function generate_taxonomy_sitemap( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'number'     => self::MAX_URLS,
        ) );

        if ( empty( $terms ) || is_wp_error( $terms ) ) return '';

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
     */
    private function generate_author_sitemap() {
        $authors = get_users( array(
            'has_published_posts' => true,
            'number'              => self::MAX_URLS,
        ) );

        if ( empty( $authors ) ) return '';

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

    private function get_xml_header() {
        $header  = '<?xml version="1.0" encoding="UTF-8"?>' . "\\n";
        $header .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $header .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        $header .= '>' . "\\n";
        return $header;
    }

    private function get_xml_footer() {
        return '</urlset>';
    }

    private function build_url_node( $url ) {
        $xml = "\\t<url>\\n";
        $xml .= "\\t\\t<loc>" . esc_url( $url['loc'] ) . "</loc>\\n";

        if ( ! empty( $url['lastmod'] ) ) {
            $xml .= "\\t\\t<lastmod>" . esc_html( $url['lastmod'] ) . "</lastmod>\\n";
        }
        if ( ! empty( $url['changefreq'] ) ) {
            $xml .= "\\t\\t<changefreq>" . esc_html( $url['changefreq'] ) . "</changefreq>\\n";
        }
        if ( ! empty( $url['priority'] ) ) {
            $xml .= "\\t\\t<priority>" . esc_html( $url['priority'] ) . "</priority>\\n";
        }

        // Görsel sitemap
        if ( ! empty( $url['images'] ) ) {
            foreach ( $url['images'] as $image ) {
                $xml .= "\\t\\t<image:image>\\n";
                $xml .= "\\t\\t\\t<image:loc>" . esc_url( $image['loc'] ) . "</image:loc>\\n";
                if ( ! empty( $image['caption'] ) ) {
                    $xml .= "\\t\\t\\t<image:caption>" . esc_html( $image['caption'] ) . "</image:caption>\\n";
                }
                if ( ! empty( $image['title'] ) ) {
                    $xml .= "\\t\\t\\t<image:title>" . esc_html( $image['title'] ) . "</image:title>\\n";
                }
                $xml .= "\\t\\t</image:image>\\n";
            }
        }

        $xml .= "\\t</url>\\n";
        return $xml;
    }

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
            preg_match_all( '/<img[^>]+src=["\\']([^"\\']+)["\\']/', $post->post_content, $matches );
            if ( ! empty( $matches[1] ) ) {
                $content_images = array_slice( $matches[1], 0, 5 - count( $images ) );
                foreach ( $content_images as $img_url ) {
                    $images[] = array( 'loc' => $img_url, 'caption' => '', 'title' => '' );
                }
            }
        }

        return $images;
    }

    private function get_changefreq( $post ) {
        $days_old = ( time() - strtotime( $post->post_date_gmt ) ) / DAY_IN_SECONDS;
        if ( $days_old < 1 ) return 'hourly';
        if ( $days_old < 7 ) return 'daily';
        if ( $days_old < 30 ) return 'weekly';
        if ( $days_old < 365 ) return 'monthly';
        return 'yearly';
    }

    private function get_priority( $post, $post_type ) {
        if ( (int) get_option( 'page_on_front' ) === $post->ID ) return '1.0';
        if ( 'page' === $post_type ) return '0.6';
        if ( is_sticky( $post->ID ) ) return '0.9';
        return '0.7';
    }

    private function get_term_lastmod( $term, $taxonomy ) {
        $latest = get_posts( array(
            'post_type' => 'post', 'post_status' => 'publish',
            'posts_per_page' => 1, 'orderby' => 'modified', 'order' => 'DESC',
            'tax_query' => array( array( 'taxonomy' => $taxonomy, 'terms' => $term->term_id ) ),
        ) );
        if ( ! empty( $latest ) ) {
            return mysql2date( 'Y-m-d\\TH:i:sP', $latest[0]->post_modified_gmt );
        }
        return mysql2date( 'Y-m-d\\TH:i:sP', current_time( 'mysql', true ) );
    }

    private function get_author_lastmod( $author_id ) {
        $latest = get_posts( array(
            'post_type' => 'post', 'post_status' => 'publish',
            'posts_per_page' => 1, 'orderby' => 'modified', 'order' => 'DESC',
            'author' => $author_id,
        ) );
        if ( ! empty( $latest ) ) {
            return mysql2date( 'Y-m-d\\TH:i:sP', $latest[0]->post_modified_gmt );
        }
        return mysql2date( 'Y-m-d\\TH:i:sP', current_time( 'mysql', true ) );
    }

    private function output_xml( $xml ) {
        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex, follow' );
        echo $xml; // phpcs:ignore
        exit;
    }

    /**
     * Cache temizle
     */
    public function clear_cache( $type = '' ) {
        if ( empty( $type ) ) {
            delete_transient( 'wpsm_sitemap_index' );
            foreach ( array( 'post', 'page', 'category', 'post_tag', 'author' ) as $t ) {
                delete_transient( 'wpsm_sitemap_' . $t );
            }
        } else {
            delete_transient( 'wpsm_sitemap_' . $type );
            delete_transient( 'wpsm_sitemap_index' );
        }
        do_action( 'wpsm_sitemap_cache_cleared', $type );
    }

    /**
     * Post kaydında cache temizle
     */
    public function clear_cache_on_save( $post_id ) {
        $post_type = get_post_type( $post_id );
        $this->clear_cache( $post_type );
        $taxonomies = get_object_taxonomies( $post_type );
        foreach ( $taxonomies as $taxonomy ) {
            $this->clear_cache( $taxonomy );
        }
    }

    /**
     * Sitemap URL'lerini döndür
     */
    public function get_sitemap_urls() {
        $urls = array();
        $urls['index'] = home_url( '/sitemap_index.xml' );

        $post_types = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        $taxonomies = $this->options->get( 'sitemap_taxonomies', array( 'category' ) );

        foreach ( $post_types as $pt ) {
            $urls[ $pt ] = home_url( '/' . $pt . '-sitemap.xml' );
        }
        foreach ( $taxonomies as $tax ) {
            $urls[ $tax ] = home_url( '/' . $tax . '-sitemap.xml' );
        }
        if ( $this->options->get( 'enable_author_sitemap', false ) ) {
            $urls['author'] = home_url( '/author-sitemap.xml' );
        }

        return $urls;
    }
}`,
  },
  {
    id: 'sitemap-index',
    name: 'class-sitemap-index.php',
    path: 'includes/Sitemap/class-sitemap-index.php',
    description: 'Sitemap index - <sitemapindex> yapısı, alt sitemap listesi',
    language: 'php',
    code: `<?php
/**
 * Sitemap Index Sınıfı
 *
 * Ana sitemap index dosyasını oluşturur.
 * Tüm alt sitemap'leri listeler.
 *
 * @package WPSM\\Sitemap
 * @since 1.0.0
 */

namespace WPSM\\Sitemap;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Sitemap_Index {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Sitemap index XML oluştur
     *
     * @return string XML
     */
    public function generate() {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\\n";

        // Post type sitemap'leri
        $post_types = $this->options->get( 'sitemap_post_types', array( 'post', 'page' ) );
        foreach ( $post_types as $post_type ) {
            $xml .= $this->build_sitemap_node(
                home_url( '/' . $post_type . '-sitemap.xml' ),
                $this->get_last_modified( $post_type, 'post_type' )
            );
        }

        // Taksonomi sitemap'leri
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
     */
    private function build_sitemap_node( $loc, $lastmod = '' ) {
        $xml  = "\\t<sitemap>\\n";
        $xml .= "\\t\\t<loc>" . esc_url( $loc ) . "</loc>\\n";
        if ( ! empty( $lastmod ) ) {
            $xml .= "\\t\\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\\n";
        }
        $xml .= "\\t</sitemap>\\n";
        return $xml;
    }

    /**
     * Son değişiklik tarihini döndür
     */
    private function get_last_modified( $type, $scope ) {
        if ( 'post_type' === $scope ) {
            $latest = get_posts( array(
                'post_type' => $type, 'post_status' => 'publish',
                'posts_per_page' => 1, 'orderby' => 'modified',
                'order' => 'DESC', 'fields' => 'ids',
            ) );
            if ( ! empty( $latest ) ) {
                $post = get_post( $latest[0] );
                return mysql2date( 'Y-m-d\\TH:i:sP', $post->post_modified_gmt );
            }
        }

        if ( 'taxonomy' === $scope ) {
            $terms = get_terms( array(
                'taxonomy' => $type, 'hide_empty' => true,
                'number' => 1, 'orderby' => 'count', 'order' => 'DESC',
            ) );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $latest = get_posts( array(
                    'post_type' => 'post', 'post_status' => 'publish',
                    'posts_per_page' => 1, 'orderby' => 'modified', 'order' => 'DESC',
                    'tax_query' => array( array( 'taxonomy' => $type, 'terms' => $terms[0]->term_id ) ),
                ) );
                if ( ! empty( $latest ) ) {
                    return mysql2date( 'Y-m-d\\TH:i:sP', $latest[0]->post_modified_gmt );
                }
            }
        }

        return mysql2date( 'Y-m-d\\TH:i:sP', current_time( 'mysql', true ) );
    }

    private function get_author_last_modified() {
        $latest = get_posts( array(
            'post_type' => 'post', 'post_status' => 'publish',
            'posts_per_page' => 1, 'orderby' => 'modified', 'order' => 'DESC',
        ) );
        if ( ! empty( $latest ) ) {
            return mysql2date( 'Y-m-d\\TH:i:sP', $latest[0]->post_modified_gmt );
        }
        return mysql2date( 'Y-m-d\\TH:i:sP', current_time( 'mysql', true ) );
    }
}`,
  },
  {
    id: 'sitemap-view',
    name: 'settings-sitemap.php',
    path: 'includes/Admin/views/settings-sitemap.php',
    description: 'Sitemap admin sayfası - durum, URL listesi, cache temizleme',
    language: 'php',
    code: `<?php
/**
 * Sitemap Ayarları Sayfası
 *
 * Sitemap durumu, URL listesi, cache temizleme.
 *
 * @package WPSM\\Admin\\Views
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! isset( $options ) ) {
    $options = new \\WPSM\\Class_Options();
}

$sitemap_generator = new \\WPSM\\Sitemap\\Class_Sitemap_Generator( $options );

// Mevcut değerler
$enable_sitemap         = $options->get( 'enable_sitemap', true );
$sitemap_post_types     = $options->get( 'sitemap_post_types', array( 'post', 'page' ) );
$sitemap_taxonomies     = $options->get( 'sitemap_taxonomies', array( 'category' ) );
$sitemap_posts_per_page = $options->get( 'sitemap_posts_per_page', 1000 );
$enable_author_sitemap  = $options->get( 'enable_author_sitemap', false );

// Kaydetme işlemi
$message = '';
if ( isset( $_POST['wpsm_save_sitemap'] ) ) {
    if ( ! isset( $_POST['wpsm_nonce'] ) || ! wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Doğrulama başarısız.', 'wp-seo-master' ) . '</p></div>';
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) . '</p></div>';
    } else {
        $data = array();
        if ( isset( $_POST['wpsm_settings'] ) && is_array( $_POST['wpsm_settings'] ) ) {
            foreach ( $_POST['wpsm_settings'] as $key => $value ) {
                $key = sanitize_text_field( $key );
                switch ( $key ) {
                    case 'enable_sitemap':
                    case 'enable_author_sitemap':
                        $data[ $key ] = (bool) $value;
                        break;
                    case 'sitemap_posts_per_page':
                        $data[ $key ] = intval( $value );
                        break;
                    case 'sitemap_post_types':
                    case 'sitemap_taxonomies':
                        if ( is_array( $value ) ) {
                            $data[ $key ] = array_map( 'sanitize_text_field', $value );
                        }
                        break;
                }
            }
            // Checkbox'lar
            if ( ! isset( $_POST['wpsm_settings']['enable_sitemap'] ) ) {
                $data['enable_sitemap'] = false;
            }
            if ( ! isset( $_POST['wpsm_settings']['enable_author_sitemap'] ) ) {
                $data['enable_author_sitemap'] = false;
            }

            $options->update( $data );
            $sitemap_generator->clear_cache();
            flush_rewrite_rules();
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ayarlar kaydedildi.', 'wp-seo-master' ) . '</p></div>';
        }
    }
}

// Cache temizleme
if ( isset( $_POST['wpsm_clear_sitemap_cache'] ) ) {
    if ( isset( $_POST['wpsm_nonce'] ) && wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $sitemap_generator->clear_cache();
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache temizlendi.', 'wp-seo-master' ) . '</p></div>';
        }
    }
}

$sitemap_urls = $sitemap_generator->get_sitemap_urls();
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-list-view"></span>
        <?php esc_html_e( 'Sitemap Ayarları', 'wp-seo-master' ); ?>
    </h1>

    <?php echo $message; // phpcs:ignore ?>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=wp-seo-master-settings&tab=general" class="nav-tab">Genel</a>
        <a href="?page=wp-seo-master-settings&tab=schema" class="nav-tab">Şema</a>
        <a href="?page=wp-seo-master-settings&tab=social" class="nav-tab">Sosyal Medya</a>
        <a href="?page=wp-seo-master-settings&tab=sitemap" class="nav-tab nav-tab-active">Sitemap</a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_nonce' ); ?>

        <!-- Sitemap Durumu -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Sitemap Durumu', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Sitemap Etkin', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_sitemap]" value="1" <?php checked( $enable_sitemap ); ?> />
                                <?php esc_html_e( 'XML Sitemap\\'i etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sitemap URL', 'wp-seo-master' ); ?></th>
                        <td>
                            <code><?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?></code>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Dahil Edilecek İçerikler -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Dahil Edilecek İçerikler', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Post Türleri', 'wp-seo-master' ); ?></th>
                        <td>
                            <?php
                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                            foreach ( $post_types as $pt ) :
                                if ( 'attachment' === $pt->name ) continue;
                            ?>
                                <label style="display:block;margin-bottom:5px;">
                                    <input type="checkbox" name="wpsm_settings[sitemap_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $sitemap_post_types, true ) ); ?> />
                                    <?php echo esc_html( $pt->label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Taksonomiler', 'wp-seo-master' ); ?></th>
                        <td>
                            <?php
                            $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
                            foreach ( $taxonomies as $tax ) :
                                if ( 'post_format' === $tax->name ) continue;
                            ?>
                                <label style="display:block;margin-bottom:5px;">
                                    <input type="checkbox" name="wpsm_settings[sitemap_taxonomies][]" value="<?php echo esc_attr( $tax->name ); ?>" <?php checked( in_array( $tax->name, $sitemap_taxonomies, true ) ); ?> />
                                    <?php echo esc_html( $tax->label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Yazar Sitemap', 'wp-seo-master' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_author_sitemap]" value="1" <?php checked( $enable_author_sitemap ); ?> />
                                <?php esc_html_e( 'Yazar sayfalarını ekle', 'wp-seo-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Maksimum URL', 'wp-seo-master' ); ?></label></th>
                        <td>
                            <input type="number" name="wpsm_settings[sitemap_posts_per_page]" value="<?php echo esc_attr( $sitemap_posts_per_page ); ?>" class="small-text" min="1" max="50000" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Sitemap URL Listesi -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Sitemap URL Listesi', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Tip', 'wp-seo-master' ); ?></th>
                            <th><?php esc_html_e( 'URL', 'wp-seo-master' ); ?></th>
                            <th><?php esc_html_e( 'İşlem', 'wp-seo-master' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $sitemap_urls as $type => $url ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( ucfirst( $type ) ); ?></strong></td>
                                <td><code><?php echo esc_url( $url ); ?></code></td>
                                <td>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button button-small">Görüntüle</a>
                                    <a href="https://www.google.com/webmasters/tools/ping?sitemap=<?php echo esc_url( rawurlencode( $url ) ); ?>" target="_blank" class="button button-small">Google\\'a Gönder</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cache -->
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e( 'Cache Yönetimi', 'wp-seo-master' ); ?></span></h2>
            <div class="inside">
                <p><?php esc_html_e( 'Sitemap 12 saat cache\\'lenir.', 'wp-seo-master' ); ?></p>
                <button type="submit" name="wpsm_clear_sitemap_cache" value="1" class="button button-secondary">
                    <?php esc_html_e( 'Cache\\'i Temizle', 'wp-seo-master' ); ?>
                </button>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="wpsm_save_sitemap" class="button-primary" value="<?php esc_attr_e( 'Kaydet', 'wp-seo-master' ); ?>" />
        </p>
    </form>
</div>`,
  },
  {
    id: 'faq',
    name: 'class-faq-schema.php',
    path: 'includes/Schema/class-faq-schema.php',
    description: 'FAQPage şeması - Question[], acceptedAnswer: Answer',
    language: 'php',
    code: `<?php
/**
 * FAQ Schema Sınıfı
 *
 * FAQPage şemasını oluşturur.
 * Soru-cevap çiftleri metabox'ta repeater olarak girilir.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_FAQ_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * FAQ şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        $schema_data = get_post_meta( $post_id, '_wpsm_schema_data', true );

        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) {
            return array();
        }

        $questions = isset( $schema_data['questions'] ) ? $schema_data['questions'] : array();

        if ( empty( $questions ) ) {
            return array();
        }

        $main_entity = array();

        foreach ( $questions as $q ) {
            if ( empty( $q['question'] ) || empty( $q['answer'] ) ) {
                continue;
            }

            $main_entity[] = array(
                '@type'          => 'Question',
                'name'           => $this->sanitize( $q['question'] ),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => wp_kses_post( $q['answer'] ),
                ),
            );
        }

        if ( empty( $main_entity ) ) {
            return array();
        }

        $schema = array(
            '@type'      => 'FAQPage',
            '@id'        => get_permalink( $post_id ) . '#faq',
            'mainEntity' => $main_entity,
        );

        return apply_filters( 'wpsm_faq_schema', $schema, $post_id );
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
    }
}`,
  },
  {
    id: 'howto',
    name: 'class-howto-schema.php',
    path: 'includes/Schema/class-howto-schema.php',
    description: 'HowTo şeması - step[], supply, tool, totalTime',
    language: 'php',
    code: `<?php
/**
 * HowTo Schema Sınıfı
 *
 * HowTo şemasını oluşturur.
 * Adımlar metabox'ta repeater olarak girilir.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_HowTo_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * HowTo şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        $schema_data = get_post_meta( $post_id, '_wpsm_schema_data', true );

        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) {
            return array();
        }

        $steps = isset( $schema_data['steps'] ) ? $schema_data['steps'] : array();

        if ( empty( $steps ) ) {
            return array();
        }

        $schema = array(
            '@type'       => 'HowTo',
            '@id'         => get_permalink( $post_id ) . '#howto',
            'name'        => $this->sanitize( get_the_title( $post_id ) ),
            'description' => $this->get_description( $post_id, $schema_data ),
        );

        // totalTime (ISO 8601 duration)
        if ( ! empty( $schema_data['totalTime'] ) ) {
            $schema['totalTime'] = sanitize_text_field( $schema_data['totalTime'] );
        }

        // estimatedCost
        if ( ! empty( $schema_data['estimatedCost'] ) ) {
            $schema['estimatedCost'] = array(
                '@type'    => 'MonetaryAmount',
                'currency' => 'USD',
                'value'    => sanitize_text_field( $schema_data['estimatedCost'] ),
            );
        }

        // supply (malzemeler)
        if ( ! empty( $schema_data['supply'] ) && is_array( $schema_data['supply'] ) ) {
            $supply_list = array();
            foreach ( $schema_data['supply'] as $supply_item ) {
                if ( ! empty( $supply_item['name'] ) ) {
                    $supply_list[] = array(
                        '@type' => 'HowToSupply',
                        'name'  => $this->sanitize( $supply_item['name'] ),
                    );
                }
            }
            if ( ! empty( $supply_list ) ) {
                $schema['supply'] = $supply_list;
            }
        }

        // tool (aletler)
        if ( ! empty( $schema_data['tool'] ) && is_array( $schema_data['tool'] ) ) {
            $tool_list = array();
            foreach ( $schema_data['tool'] as $tool_item ) {
                if ( ! empty( $tool_item['name'] ) ) {
                    $tool_list[] = array(
                        '@type' => 'HowToTool',
                        'name'  => $this->sanitize( $tool_item['name'] ),
                    );
                }
            }
            if ( ! empty( $tool_list ) ) {
                $schema['tool'] = $tool_list;
            }
        }

        // step (adımlar)
        $step_list = array();
        foreach ( $steps as $step ) {
            if ( empty( $step['name'] ) ) continue;

            $step_schema = array(
                '@type' => 'HowToStep',
                'name'  => $this->sanitize( $step['name'] ),
                'text'  => wp_kses_post( $step['text'] ?? '' ),
            );

            // step image
            if ( ! empty( $step['image'] ) ) {
                $step_schema['image'] = esc_url( $step['image'] );
            }

            // step url
            if ( ! empty( $step['url'] ) ) {
                $step_schema['url'] = esc_url( $step['url'] );
            }

            $step_list[] = $step_schema;
        }

        if ( ! empty( $step_list ) ) {
            $schema['step'] = $step_list;
        }

        return apply_filters( 'wpsm_howto_schema', $schema, $post_id );
    }

    private function get_description( $post_id, $schema_data ) {
        if ( ! empty( $schema_data['description'] ) ) {
            return $this->sanitize( $schema_data['description'] );
        }
        $excerpt = get_the_excerpt( $post_id );
        if ( ! empty( $excerpt ) ) {
            return $this->sanitize( $excerpt );
        }
        $post = get_post( $post_id );
        if ( $post ) {
            return wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
        }
        return '';
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
    }
}`,
  },
  {
    id: 'product',
    name: 'class-product-schema.php',
    path: 'includes/Schema/class-product-schema.php',
    description: 'Product şeması - WooCommerce otomatik, manuel giriş, offers, rating',
    language: 'php',
    code: `<?php
/**
 * Product Schema Sınıfı
 *
 * Product şemasını oluşturur.
 * WooCommerce varsa otomatik, yoksa manuel giriş.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Product_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Product şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        // WooCommerce varsa otomatik verileri al
        if ( $this->is_woocommerce_product( $post_id ) ) {
            return $this->get_woocommerce_schema( $post_id );
        }

        // Manuel giriş
        return $this->get_manual_schema( $post_id );
    }

    private function is_woocommerce_product( $post_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) return false;
        $product = wc_get_product( $post_id );
        return $product !== false;
    }

    /**
     * WooCommerce şemasını döndür
     */
    private function get_woocommerce_schema( $post_id ) {
        $product = wc_get_product( $post_id );
        if ( ! $product ) return array();

        $schema = array(
            '@type'       => 'Product',
            '@id'         => get_permalink( $post_id ) . '#product',
            'name'        => $this->sanitize( $product->get_name() ),
            'description' => $this->sanitize( $product->get_description() ),
            'url'         => get_permalink( $post_id ),
        );

        // image
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $image_src = wp_get_attachment_image_src( $image_id, 'full' );
            if ( $image_src ) {
                $schema['image'] = array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $image_src[0] ),
                    'width'  => $image_src[1],
                    'height' => $image_src[2],
                );
            }
        }

        // sku
        $sku = $product->get_sku();
        if ( ! empty( $sku ) ) {
            $schema['sku'] = $this->sanitize( $sku );
        }

        // brand
        $brand = $this->get_product_brand( $product );
        if ( ! empty( $brand ) ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => $this->sanitize( $brand ),
            );
        }

        // offers
        $schema['offers'] = $this->get_woocommerce_offers( $product );

        // aggregateRating
        $rating = $this->get_product_rating( $product );
        if ( ! empty( $rating ) ) {
            $schema['aggregateRating'] = $rating;
        }

        // reviews
        $reviews = $this->get_product_reviews( $product );
        if ( ! empty( $reviews ) ) {
            $schema['review'] = $reviews;
        }

        return apply_filters( 'wpsm_product_schema', $schema, $post_id );
    }

    /**
     * Manuel Product şemasını döndür
     */
    private function get_manual_schema( $post_id ) {
        $schema_data = get_post_meta( $post_id, '_wpsm_schema_data', true );
        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) return array();

        $schema = array(
            '@type'       => 'Product',
            '@id'         => get_permalink( $post_id ) . '#product',
            'name'        => $this->sanitize( get_the_title( $post_id ) ),
            'description' => $this->get_description( $post_id, $schema_data ),
            'url'         => get_permalink( $post_id ),
        );

        // image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $image_src ) {
                $schema['image'] = array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $image_src[0] ),
                    'width'  => $image_src[1],
                    'height' => $image_src[2],
                );
            }
        }

        // sku
        if ( ! empty( $schema_data['sku'] ) ) {
            $schema['sku'] = $this->sanitize( $schema_data['sku'] );
        }

        // brand
        if ( ! empty( $schema_data['brand'] ) ) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name'  => $this->sanitize( $schema_data['brand'] ),
            );
        }

        // offers
        $schema['offers'] = array(
            '@type'         => 'Offer',
            'priceCurrency' => ! empty( $schema_data['priceCurrency'] ) ? $this->sanitize( $schema_data['priceCurrency'] ) : 'USD',
            'price'         => ! empty( $schema_data['price'] ) ? number_format( (float) $schema_data['price'], 2, '.', '' ) : '0.00',
            'availability'  => ! empty( $schema_data['availability'] ) ? $this->sanitize( $schema_data['availability'] ) : 'https://schema.org/InStock',
        );

        if ( ! empty( $schema_data['priceValidUntil'] ) ) {
            $schema['offers']['priceValidUntil'] = sanitize_text_field( $schema_data['priceValidUntil'] );
        }

        return apply_filters( 'wpsm_product_schema', $schema, $post_id );
    }

    private function get_woocommerce_offers( $product ) {
        $price = $product->get_price();
        $offer = array(
            '@type'         => 'Offer',
            'url'           => get_permalink( $product->get_id() ),
            'priceCurrency' => get_woocommerce_currency(),
            'price'         => $price ? number_format( (float) $price, 2, '.', '' ) : '0.00',
            'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        );

        $sale_price_dates_to = $product->get_date_on_sale_to();
        if ( $sale_price_dates_to ) {
            $offer['priceValidUntil'] = $sale_price_dates_to->date( 'Y-m-d' );
        }

        return $offer;
    }

    private function get_product_brand( $product ) {
        $brands = wp_get_post_terms( $product->get_id(), 'product_brand' );
        if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) {
            return $brands[0]->name;
        }
        return get_post_meta( $product->get_id(), '_wpsm_brand', true );
    }

    private function get_product_rating( $product ) {
        $rating_count = $product->get_rating_count();
        $average = $product->get_average_rating();
        if ( $rating_count < 1 || empty( $average ) ) return null;

        return array(
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format( (float) $average, 1, '.', '' ),
            'reviewCount' => intval( $rating_count ),
            'bestRating'  => '5',
            'worstRating' => '1',
        );
    }

    private function get_product_reviews( $product ) {
        $reviews = array();
        $comments = get_comments( array(
            'post_id' => $product->get_id(),
            'status'  => 'approve',
            'type'    => 'review',
            'number'  => 5,
        ) );

        foreach ( $comments as $comment ) {
            $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
            $reviews[] = array(
                '@type'        => 'Review',
                'author'       => array( '@type' => 'Person', 'name' => $this->sanitize( $comment->comment_author ) ),
                'datePublished' => get_comment_date( 'Y-m-d', $comment->comment_ID ),
                'reviewBody'    => $this->sanitize( $comment->comment_content ),
                'reviewRating'  => array(
                    '@type'       => 'Rating',
                    'ratingValue' => $rating ? intval( $rating ) : 5,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                ),
            );
        }
        return $reviews;
    }

    private function get_description( $post_id, $schema_data ) {
        if ( ! empty( $schema_data['description'] ) ) return $this->sanitize( $schema_data['description'] );
        $excerpt = get_the_excerpt( $post_id );
        if ( ! empty( $excerpt ) ) return $this->sanitize( $excerpt );
        $post = get_post( $post_id );
        if ( $post ) return wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
        return '';
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
    }
}`,
  },
  {
    id: 'localbusiness',
    name: 'class-localbusiness-schema.php',
    path: 'includes/Schema/class-localbusiness-schema.php',
    description: 'LocalBusiness şeması - address, geo, openingHours, telephone',
    language: 'php',
    code: `<?php
/**
 * LocalBusiness Schema Sınıfı
 *
 * LocalBusiness şemasını oluşturur.
 * İşletme bilgileri metabox'ta girilir.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Localbusiness_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * LocalBusiness şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        $schema_data = get_post_meta( $post_id, '_wpsm_schema_data', true );
        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) return array();

        $schema = array(
            '@type' => 'LocalBusiness',
            '@id'   => get_permalink( $post_id ) . '#localbusiness',
            'name'  => $this->get_name( $post_id, $schema_data ),
            'url'   => get_permalink( $post_id ),
        );

        // image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $image_src ) {
                $schema['image'] = array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $image_src[0] ),
                    'width'  => $image_src[1],
                    'height' => $image_src[2],
                );
            }
        }

        // description
        $description = $this->get_description( $post_id, $schema_data );
        if ( ! empty( $description ) ) {
            $schema['description'] = $description;
        }

        // telephone
        if ( ! empty( $schema_data['telephone'] ) ) {
            $schema['telephone'] = $this->sanitize( $schema_data['telephone'] );
        }

        // priceRange
        if ( ! empty( $schema_data['priceRange'] ) ) {
            $schema['priceRange'] = $this->sanitize( $schema_data['priceRange'] );
        }

        // address (PostalAddress)
        $address = $this->get_address( $schema_data );
        if ( ! empty( $address ) ) {
            $schema['address'] = $address;
        }

        // geo (GeoCoordinates)
        $geo = $this->get_geo( $schema_data );
        if ( ! empty( $geo ) ) {
            $schema['geo'] = $geo;
        }

        // openingHours
        $opening_hours = $this->get_opening_hours( $schema_data );
        if ( ! empty( $opening_hours ) ) {
            $schema['openingHours'] = $opening_hours;
        }

        // sameAs (sosyal profiller)
        $same_as = $this->get_same_as( $schema_data );
        if ( ! empty( $same_as ) ) {
            $schema['sameAs'] = $same_as;
        }

        return apply_filters( 'wpsm_localbusiness_schema', $schema, $post_id );
    }

    private function get_name( $post_id, $schema_data ) {
        if ( ! empty( $schema_data['name'] ) ) return $this->sanitize( $schema_data['name'] );
        return $this->sanitize( get_the_title( $post_id ) );
    }

    private function get_description( $post_id, $schema_data ) {
        if ( ! empty( $schema_data['description'] ) ) return $this->sanitize( $schema_data['description'] );
        $excerpt = get_the_excerpt( $post_id );
        if ( ! empty( $excerpt ) ) return $this->sanitize( $excerpt );
        $post = get_post( $post_id );
        if ( $post ) return wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
        return '';
    }

    /**
     * Adres döndür (PostalAddress)
     */
    private function get_address( $schema_data ) {
        $address = array( '@type' => 'PostalAddress' );
        $has_address = false;

        $fields = array(
            'streetAddress'   => 'streetAddress',
            'addressLocality' => 'addressLocality',
            'addressRegion'   => 'addressRegion',
            'postalCode'      => 'postalCode',
            'addressCountry'  => 'addressCountry',
        );

        foreach ( $fields as $schema_key => $data_key ) {
            if ( ! empty( $schema_data[ $data_key ] ) ) {
                $address[ $schema_key ] = $this->sanitize( $schema_data[ $data_key ] );
                $has_address = true;
            }
        }

        return $has_address ? $address : array();
    }

    /**
     * Geo koordinatları döndür (GeoCoordinates)
     */
    private function get_geo( $schema_data ) {
        if ( empty( $schema_data['latitude'] ) || empty( $schema_data['longitude'] ) ) {
            return array();
        }
        return array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => floatval( $schema_data['latitude'] ),
            'longitude' => floatval( $schema_data['longitude'] ),
        );
    }

    /**
     * Açılış saatlerini döndür
     * Format: "Mo 09:00-17:00"
     */
    private function get_opening_hours( $schema_data ) {
        if ( empty( $schema_data['openingHours'] ) || ! is_array( $schema_data['openingHours'] ) ) {
            return array();
        }

        $hours = array();
        $day_map = array(
            'monday' => 'Mo', 'tuesday' => 'Tu', 'wednesday' => 'We',
            'thursday' => 'Th', 'friday' => 'Fr', 'saturday' => 'Sa', 'sunday' => 'Su',
        );

        foreach ( $schema_data['openingHours'] as $hour ) {
            if ( empty( $hour['day'] ) || empty( $hour['opens'] ) || empty( $hour['closes'] ) ) {
                continue;
            }

            $day = strtolower( $hour['day'] );
            if ( isset( $day_map[ $day ] ) ) {
                $day = $day_map[ $day ];
            } elseif ( ! in_array( $day, array( 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' ), true ) ) {
                continue;
            }

            $opens = sanitize_text_field( $hour['opens'] );
            $closes = sanitize_text_field( $hour['closes'] );

            if ( ! preg_match( '/^\\d{2}:\\d{2}$/', $opens ) || ! preg_match( '/^\\d{2}:\\d{2}$/', $closes ) ) {
                continue;
            }

            $hours[] = sprintf( '%s %s-%s', $day, $opens, $closes );
        }

        return $hours;
    }

    /**
     * Sosyal profilleri döndür (sameAs)
     */
    private function get_same_as( $schema_data ) {
        $profiles = array();
        $social_keys = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest' );

        foreach ( $social_keys as $key ) {
            if ( ! empty( $schema_data[ $key ] ) ) {
                $profiles[] = esc_url( $schema_data[ $key ] );
            }
        }

        return $profiles;
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
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
            Ek Schema Tipleri
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            4 dosya: FAQ, HowTo, Product (WooCommerce desteği), LocalBusiness.
            Metabox'tan AJAX ile dinamik alan yükleme, repeater desteği.
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

        {/* Schema Types Overview */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* FAQ Schema */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-3 flex items-center">
              <span className="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center mr-2">❓</span>
              FAQPage
            </h3>
            <div className="bg-gray-900 rounded-lg p-3 font-mono text-xs overflow-x-auto">
              <div><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"FAQPage"</span></div>
              <div><span className="text-green-300">"mainEntity"</span>: [</div>
              <div className="ml-4"><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"Question"</span></div>
              <div className="ml-4"><span className="text-green-300">"name"</span>: <span className="text-blue-300">"Soru?"</span></div>
              <div className="ml-4"><span className="text-green-300">"acceptedAnswer"</span>: {'{'}</div>
              <div className="ml-8"><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"Answer"</span></div>
              <div className="ml-8"><span className="text-green-300">"text"</span>: <span className="text-blue-300">"Cevap..."</span></div>
              <div className="ml-4">{'}'}</div>
              <div>]</div>
            </div>
          </div>

          {/* HowTo Schema */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-3 flex items-center">
              <span className="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-2">📖</span>
              HowTo
            </h3>
            <div className="bg-gray-900 rounded-lg p-3 font-mono text-xs overflow-x-auto">
              <div><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"HowTo"</span></div>
              <div><span className="text-green-300">"totalTime"</span>: <span className="text-blue-300">"PT30M"</span></div>
              <div><span className="text-green-300">"supply"</span>: [<span className="text-blue-300">"Malzeme"</span>]</div>
              <div><span className="text-green-300">"tool"</span>: [<span className="text-blue-300">"Alet"</span>]</div>
              <div><span className="text-green-300">"step"</span>: [</div>
              <div className="ml-4"><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"HowToStep"</span></div>
              <div className="ml-4"><span className="text-green-300">"name"</span>: <span className="text-blue-300">"Adım adı"</span></div>
              <div className="ml-4"><span className="text-green-300">"text"</span>: <span className="text-blue-300">"Açıklama"</span></div>
              <div>]</div>
            </div>
          </div>

          {/* Product Schema */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-3 flex items-center">
              <span className="w-8 h-8 bg-orange-500/20 rounded-lg flex items-center justify-center mr-2">🛒</span>
              Product
            </h3>
            <div className="bg-gray-900 rounded-lg p-3 font-mono text-xs overflow-x-auto">
              <div><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"Product"</span></div>
              <div><span className="text-green-300">"sku"</span>: <span className="text-blue-300">"ABC123"</span></div>
              <div><span className="text-green-300">"brand"</span>: {'{'} <span className="text-green-300">"name"</span>: <span className="text-blue-300">"Marka"</span> {'}'}</div>
              <div><span className="text-green-300">"offers"</span>: {'{'}</div>
              <div className="ml-4"><span className="text-green-300">"price"</span>: <span className="text-blue-300">"99.99"</span></div>
              <div className="ml-4"><span className="text-green-300">"priceCurrency"</span>: <span className="text-blue-300">"TRY"</span></div>
              <div className="ml-4"><span className="text-green-300">"availability"</span>: <span className="text-blue-300">"InStock"</span></div>
              <div>{'}'}</div>
              <div><span className="text-green-300">"aggregateRating"</span>: {'{'} ... {'}'}</div>
            </div>
          </div>

          {/* LocalBusiness Schema */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-3 flex items-center">
              <span className="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center mr-2">🏪</span>
              LocalBusiness
            </h3>
            <div className="bg-gray-900 rounded-lg p-3 font-mono text-xs overflow-x-auto">
              <div><span className="text-green-300">"@type"</span>: <span className="text-blue-300">"LocalBusiness"</span></div>
              <div><span className="text-green-300">"telephone"</span>: <span className="text-blue-300">"+90..."</span></div>
              <div><span className="text-green-300">"address"</span>: {'{'}</div>
              <div className="ml-4"><span className="text-green-300">"streetAddress"</span>: <span className="text-blue-300">"Cadde No"</span></div>
              <div className="ml-4"><span className="text-green-300">"addressLocality"</span>: <span className="text-blue-300">"İstanbul"</span></div>
              <div>{'}'}</div>
              <div><span className="text-green-300">"geo"</span>: {'{'} <span className="text-green-300">"latitude"</span>, <span className="text-green-300">"longitude"</span> {'}'}</div>
              <div><span className="text-green-300">"openingHours"</span>: [<span className="text-blue-300">"Mo 09:00-17:00"</span>]</div>
            </div>
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: 'FAQ Repeater', desc: 'Soru-cevap çiftleri, dinamik ekleme' },
            { title: 'HowTo Steps', desc: 'Adımlar, malzemeler, aletler' },
            { title: 'WooCommerce', desc: 'Otomatik product schema' },
            { title: 'aggregateRating', desc: 'Otomatik puan ve yorumlar' },
            { title: 'PostalAddress', desc: 'Tam adres bilgileri' },
            { title: 'GeoCoordinates', desc: 'Enlem/boylam desteği' },
            { title: 'openingHours', desc: 'ISO formatında çalışma saatleri' },
            { title: 'AJAX Fields', desc: 'Tip seçilince dinamik alanlar' },
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
