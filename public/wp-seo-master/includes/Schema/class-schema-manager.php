<?php
/**
 * Schema Manager Sınıfı
 *
 * JSON-LD şema çıktılarını yönetir.
 * wp_head priority 10'da çalışır.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Schema_Manager {

    private $options;
    private $other_seo_active = false;

    public function __construct( $options ) {
        $this->options = $options;
        $this->check_other_seo_plugins();
    }

    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION',
            'AIOSEO_VERSION',
            'RANK_MATH_VERSION',
            'SEOPRESS_VERSION',
        );

        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;
                break;
            }
        }
    }

    /**
     * JSON-LD çıktısı ver (wp_head priority 10)
     */
    public function output_schema() {
        if ( $this->other_seo_active ) {
            return;
        }

        $enabled = $this->options->get( 'enable_schema', true );
        if ( ! $enabled ) {
            return;
        }

        $schema_data = $this->build_schema_graph();

        if ( empty( $schema_data ) ) {
            return;
        }

        // JSON-LD çıktısı
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }

    /**
     * Schema @graph yapısını oluştur
     *
     * @return array
     */
    private function build_schema_graph() {
        $graph = array();

        // WebSite şeması (her sayfada)
        $graph[] = $this->get_website_schema();

        // Organization şeması (her sayfada)
        $org_schema = $this->get_organization_schema();
        if ( ! empty( $org_schema ) ) {
            $graph[] = $org_schema;
        }

        // BreadcrumbList şeması (breadcrumb aktifse)
        $breadcrumb_enabled = $this->options->get( 'enable_breadcrumbs', true );
        if ( $breadcrumb_enabled ) {
            $breadcrumb_schema = $this->get_breadcrumb_schema();
            if ( ! empty( $breadcrumb_schema ) ) {
                $graph[] = $breadcrumb_schema;
            }
        }

        // Singular sayfa için özel şema
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $schema_type = get_post_meta( $post_id, '_wpsm_schema_type', true );

            // Şema tipi yoksa varsayılan kullan
            if ( empty( $schema_type ) || 'none' === $schema_type ) {
                $schema_type = $this->get_default_schema_type();
            }

            // Şema sınıfını çağır
            $schema_class = $this->get_schema_class( $schema_type );
            if ( $schema_class ) {
                $schema_data = $schema_class->get_schema( $post_id );
                if ( ! empty( $schema_data ) ) {
                    $graph[] = $schema_data;
                }
            }
        }

        /**
         * Schema graph'ını filtrele
         *
         * @param array $graph Schema graph dizisi
         */
        $graph = apply_filters( 'wpsm_schema_graph', $graph );

        if ( empty( $graph ) ) {
            return array();
        }

        // @graph yapısı
        return array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );
    }

    /**
     * WebSite şeması (SearchAction ile)
     *
     * @return array
     */
    private function get_website_schema() {
        $schema = array(
            '@type'       => 'WebSite',
            '@id'         => home_url( '/#website' ),
            'url'         => home_url( '/' ),
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
        );

        // SearchAction ekle
        $search_action = array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url( '/?s={search_term_string}' ),
            ),
            'query-input' => 'required name=search_term_string',
        );

        $schema['potentialAction'] = $search_action;

        // Publisher (Organization)
        $schema['publisher'] = array(
            '@id' => home_url( '/#organization' ),
        );

        return $schema;
    }

    /**
     * Organization şeması (logo, sosyal profiller)
     *
     * @return array
     */
    private function get_organization_schema() {
        $schema = array(
            '@type' => 'Organization',
            '@id'   => home_url( '/#organization' ),
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        );

        // Logo
        $logo_url = $this->get_site_logo_url();
        if ( ! empty( $logo_url ) ) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $logo_url,
            );

            // Logo boyutları
            $logo_id = get_theme_mod( 'custom_logo' );
            if ( $logo_id ) {
                $logo_meta = wp_get_attachment_metadata( $logo_id );
                if ( $logo_meta ) {
                    $schema['logo']['width']  = $logo_meta['width'];
                    $schema['logo']['height'] = $logo_meta['height'];
                }
            }
        }

        // Sosyal profiller
        $social_profiles = $this->get_social_profiles();
        if ( ! empty( $social_profiles ) ) {
            $schema['sameAs'] = $social_profiles;
        }

        return $schema;
    }

    /**
     * Breadcrumb şeması
     *
     * @return array
     */
    private function get_breadcrumb_schema() {
        $breadcrumb_class = new Class_Breadcrumb_Schema( $this->options );
        return $breadcrumb_class->get_schema();
    }

    /**
     * Varsayılan şema tipini döndür
     *
     * @return string
     */
    private function get_default_schema_type() {
        $default_type = $this->options->get( 'default_schema_type', 'Article' );

        // Post type'a göre varsayılan
        if ( is_page() ) {
            return 'WebPage';
        }

        if ( is_single() ) {
            return $default_type;
        }

        return 'WebPage';
    }

    /**
     * Şema sınıfını döndür
     *
     * @param string $type Şema tipi
     * @return object|null
     */
    private function get_schema_class( $type ) {
        switch ( strtolower( $type ) ) {
            case 'article':
            case 'blogposting':
            case 'newsarticle':
                return new Class_Article_Schema( $this->options, $type );

            case 'faq':
                return new Class_FAQ_Schema( $this->options );

            case 'howto':
                return new Class_HowTo_Schema( $this->options );

            case 'product':
                return new Class_Product_Schema( $this->options );

            case 'localbusiness':
                return new Class_Localbusiness_Schema( $this->options );

            case 'webpage':
                // WebPage için basit şema
                return $this;

            default:
                return null;
        }
    }

    /**
     * WebPage şeması döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post ) {
            return array();
        }

        $schema = array(
            '@type'       => 'WebPage',
            '@id'         => get_permalink( $post_id ) . '#webpage',
            'url'         => get_permalink( $post_id ),
            'name'        => get_the_title( $post_id ),
            'description' => $this->get_post_description( $post_id ),
        );

        // Publisher
        $schema['publisher'] = array(
            '@id' => home_url( '/#organization' ),
        );

        // Breadcrumb
        $schema['breadcrumb'] = array(
            '@id' => get_permalink( $post_id ) . '#breadcrumb',
        );

        return $schema;
    }

    /**
     * Site logosu URL'ini döndür
     *
     * @return string
     */
    private function get_site_logo_url() {
        // Custom logo
        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
            if ( $logo_url ) {
                return $logo_url;
            }
        }

        // Options'tan
        $logo_url = $this->options->get( 'organization_logo', '' );
        if ( ! empty( $logo_url ) ) {
            return $logo_url;
        }

        return '';
    }

    /**
     * Sosyal profilleri döndür
     *
     * @return array
     */
    private function get_social_profiles() {
        $profiles = array();

        // Options'tan al
        $social_keys = array(
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_linkedin',
            'social_youtube',
            'social_pinterest',
        );

        foreach ( $social_keys as $key ) {
            $url = $this->options->get( $key, '' );
            if ( ! empty( $url ) ) {
                $profiles[] = esc_url( $url );
            }
        }

        return $profiles;
    }

    /**
     * Post açıklamasını döndür
     *
     * @param int $post_id Post ID
     * @return string
     */
    private function get_post_description( $post_id ) {
        $description = get_post_meta( $post_id, '_wpsm_description', true );

        if ( empty( $description ) ) {
            $excerpt = get_the_excerpt( $post_id );
            if ( ! empty( $excerpt ) ) {
                $description = wp_trim_words( $excerpt, 25, '...' );
            }
        }

        return $description;
    }
}
