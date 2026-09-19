<?php
/**
 * Open Graph Sınıfı
 *
 * Facebook, LinkedIn ve diğer platformlar için Open Graph meta etiketleri.
 *
 * @package WPSM\Frontend
 * @since 1.0.0
 */

namespace WPSM\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Opengraph {

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
     * Open Graph etiketlerini çıktıla (wp_head priority 5)
     */
    public function output_opengraph() {
        if ( $this->other_seo_active ) {
            return;
        }

        $enabled = $this->options->get( 'enable_opengraph', true );
        if ( ! $enabled ) {
            return;
        }

        // Temel OG etiketleri
        $this->output_basic_tags();

        // Görsel
        $this->output_image_tags();

        // Article etiketleri (singular)
        if ( is_singular() ) {
            $this->output_article_tags();
        }

        // Product etiketleri (WooCommerce)
        if ( $this->is_woocommerce_product() ) {
            $this->output_product_tags();
        }
    }

    /**
     * Temel OG etiketleri
     */
    private function output_basic_tags() {
        $og_data = $this->get_og_data();

        // og:locale
        echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\n";

        // og:type
        $type = $this->get_og_type();
        echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";

        // og:title
        if ( ! empty( $og_data['title'] ) ) {
            echo '<meta property="og:title" content="' . esc_attr( $og_data['title'] ) . '" />' . "\n";
        }

        // og:description
        if ( ! empty( $og_data['description'] ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $og_data['description'] ) . '" />' . "\n";
        }

        // og:url
        if ( ! empty( $og_data['url'] ) ) {
            echo '<meta property="og:url" content="' . esc_url( $og_data['url'] ) . '" />' . "\n";
        }

        // og:site_name
        $site_name = get_bloginfo( 'name' );
        if ( ! empty( $site_name ) ) {
            echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
        }

        // Facebook App ID
        $fb_app_id = $this->options->get( 'facebook_app_id', '' );
        if ( ! empty( $fb_app_id ) ) {
            echo '<meta property="fb:app_id" content="' . esc_attr( $fb_app_id ) . '" />' . "\n";
        }
    }

    /**
     * OG görsel etiketleri
     */
    private function output_image_tags() {
        $image_data = $this->get_og_image();

        if ( empty( $image_data['url'] ) ) {
            return;
        }

        // og:image
        echo '<meta property="og:image" content="' . esc_url( $image_data['url'] ) . '" />' . "\n";

        // og:image:width
        if ( ! empty( $image_data['width'] ) ) {
            echo '<meta property="og:image:width" content="' . esc_attr( $image_data['width'] ) . '" />' . "\n";
        }

        // og:image:height
        if ( ! empty( $image_data['height'] ) ) {
            echo '<meta property="og:image:height" content="' . esc_attr( $image_data['height'] ) . '" />' . "\n";
        }

        // og:image:alt
        if ( ! empty( $image_data['alt'] ) ) {
            echo '<meta property="og:image:alt" content="' . esc_attr( $image_data['alt'] ) . '" />' . "\n";
        }
    }

    /**
     * Article etiketleri (singular sayfalar için)
     */
    private function output_article_tags() {
        $post_id = get_queried_object_id();
        $post = get_post( $post_id );

        if ( ! $post ) {
            return;
        }

        // article:published_time
        if ( ! empty( $post->post_date_gmt ) ) {
            $published_time = mysql2date( 'c', $post->post_date_gmt );
            echo '<meta property="article:published_time" content="' . esc_attr( $published_time ) . '" />' . "\n";
        }

        // article:modified_time
        if ( ! empty( $post->post_modified_gmt ) ) {
            $modified_time = mysql2date( 'c', $post->post_modified_gmt );
            echo '<meta property="article:modified_time" content="' . esc_attr( $modified_time ) . '" />' . "\n";
        }

        // article:author
        $author_name = get_the_author_meta( 'display_name', $post->post_author );
        if ( ! empty( $author_name ) ) {
            echo '<meta property="article:author" content="' . esc_attr( $author_name ) . '" />' . "\n";
        }

        // article:section (kategori)
        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '" />' . "\n";
        }

        // article:tag (etiketler)
        $tags = get_the_tags( $post_id );
        if ( ! empty( $tags ) ) {
            foreach ( $tags as $tag ) {
                echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '" />' . "\n";
            }
        }
    }

    /**
     * Product etiketleri (WooCommerce ürünleri için)
     */
    private function output_product_tags() {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return;
        }

        $post_id = get_queried_object_id();
        $product = wc_get_product( $post_id );

        if ( ! $product ) {
            return;
        }

        // product:price:amount
        $price = $product->get_price();
        if ( ! empty( $price ) ) {
            echo '<meta property="product:price:amount" content="' . esc_attr( $price ) . '" />' . "\n";
        }

        // product:price:currency
        $currency = get_woocommerce_currency();
        if ( ! empty( $currency ) ) {
            echo '<meta property="product:price:currency" content="' . esc_attr( $currency ) . '" />' . "\n";
        }

        // product:availability
        $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
        echo '<meta property="product:availability" content="' . esc_attr( $availability ) . '" />' . "\n";

        // product:retailer_item_id (SKU)
        $sku = $product->get_sku();
        if ( ! empty( $sku ) ) {
            echo '<meta property="product:retailer_item_id" content="' . esc_attr( $sku ) . '" />' . "\n";
        }
    }

    /**
     * OG verilerini topla
     *
     * Öncelik: post meta > global ayar > otomatik
     *
     * @return array
     */
    private function get_og_data() {
        $data = array(
            'title'       => '',
            'description' => '',
            'url'         => '',
        );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Post meta'dan al
            $data['title'] = get_post_meta( $post_id, '_wpsm_og_title', true );
            $data['description'] = get_post_meta( $post_id, '_wpsm_og_description', true );

            // Boşsa SEO meta'dan al
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_post_meta( $post_id, '_wpsm_title', true );
            }

            if ( empty( $data['description'] ) ) {
                $data['description'] = get_post_meta( $post_id, '_wpsm_description', true );
            }

            // Hala boşsa otomatik oluştur
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_title( $post_id );
            }

            if ( empty( $data['description'] ) ) {
                $excerpt = get_the_excerpt( $post_id );
                if ( ! empty( $excerpt ) ) {
                    $data['description'] = wp_trim_words( $excerpt, 25, '...' );
                }
            }

            $data['url'] = get_permalink( $post_id );
        }

        // Ana sayfa
        if ( is_front_page() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = $this->options->get( 'home_title', '' );
                if ( empty( $data['title'] ) ) {
                    $data['title'] = get_bloginfo( 'name' );
                }
            }

            if ( empty( $data['description'] ) ) {
                $data['description'] = $this->options->get( 'home_description', '' );
                if ( empty( $data['description'] ) ) {
                    $data['description'] = get_bloginfo( 'description' );
                }
            }

            $data['url'] = home_url( '/' );
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_archive_title();
            }

            if ( empty( $data['description'] ) ) {
                $data['description'] = wp_strip_all_tags( get_the_archive_description() );
            }

            $data['url'] = $this->get_archive_url();
        }

        // Arama sayfası
        if ( is_search() ) {
            $search_query = get_search_query();
            $data['title'] = sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query );
            $data['url'] = get_search_link();
        }

        return $data;
    }

    /**
     * OG görsel verilerini topla
     *
     * @return array
     */
    private function get_og_image() {
        $image_data = array(
            'url'    => '',
            'width'  => '',
            'height' => '',
            'alt'    => '',
        );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Post meta'dan özel OG görseli
            $custom_image = get_post_meta( $post_id, '_wpsm_og_image', true );

            if ( ! empty( $custom_image ) ) {
                $image_data['url'] = $custom_image;

                // Attachment ID'den boyutları al
                $attachment_id = attachment_url_to_postid( $custom_image );
                if ( $attachment_id ) {
                    $image_meta = wp_get_attachment_metadata( $attachment_id );
                    if ( $image_meta ) {
                        $image_data['width'] = $image_meta['width'];
                        $image_data['height'] = $image_meta['height'];
                    }

                    // Alt text
                    $image_data['alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            } else {
                // Featured image kullan
                $thumbnail_id = get_post_thumbnail_id( $post_id );
                if ( $thumbnail_id ) {
                    $image = wp_get_attachment_image_src( $thumbnail_id, 'large' );
                    if ( $image ) {
                        $image_data['url'] = $image[0];
                        $image_data['width'] = $image[1];
                        $image_data['height'] = $image[2];
                        $image_data['alt'] = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                    }
                }
            }
        }

        // Görsel yoksa varsayılan OG görseli kullan
        if ( empty( $image_data['url'] ) ) {
            $default_image = $this->options->get( 'default_og_image', '' );
            if ( ! empty( $default_image ) ) {
                $image_data['url'] = $default_image;

                $attachment_id = attachment_url_to_postid( $default_image );
                if ( $attachment_id ) {
                    $image_meta = wp_get_attachment_metadata( $attachment_id );
                    if ( $image_meta ) {
                        $image_data['width'] = $image_meta['width'];
                        $image_data['height'] = $image_meta['height'];
                    }
                    $image_data['alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            }
        }

        return $image_data;
    }

    /**
     * OG type döndür
     *
     * @return string
     */
    private function get_og_type() {
        if ( is_singular() ) {
            return 'article';
        }

        if ( $this->is_woocommerce_product() ) {
            return 'product';
        }

        return 'website';
    }

    /**
     * WooCommerce ürünü mü kontrol et
     *
     * @return bool
     */
    private function is_woocommerce_product() {
        if ( ! function_exists( 'is_product' ) ) {
            return false;
        }

        return is_product();
    }

    /**
     * Arşiv URL'ini döndür
     *
     * @return string
     */
    private function get_archive_url() {
        if ( is_category() ) {
            return get_category_link( get_queried_object_id() );
        }

        if ( is_tag() ) {
            return get_tag_link( get_queried_object_id() );
        }

        if ( is_tax() ) {
            return get_term_link( get_queried_object() );
        }

        if ( is_author() ) {
            return get_author_posts_url( get_queried_object_id() );
        }

        if ( is_post_type_archive() ) {
            return get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        return home_url( $_SERVER['REQUEST_URI'] );
    }
}
