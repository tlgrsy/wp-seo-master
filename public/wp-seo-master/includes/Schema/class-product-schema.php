<?php
/**
 * Product Schema Sınıfı
 *
 * Product şemasını oluşturur.
 * WooCommerce varsa otomatik, yoksa manuel giriş.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

    /**
     * WooCommerce ürünü mü kontrol et
     *
     * @param int $post_id Post ID
     * @return bool
     */
    private function is_woocommerce_product( $post_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return false;
        }

        $product = wc_get_product( $post_id );
        return $product !== false;
    }

    /**
     * WooCommerce şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_woocommerce_schema( $post_id ) {
        $product = wc_get_product( $post_id );

        if ( ! $product ) {
            return array();
        }

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
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_manual_schema( $post_id ) {
        $schema_data = get_post_meta( $post_id, '_wpsm_schema_data', true );

        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) {
            return array();
        }

        $schema = array(
            '@type'       => 'Product',
            '@id'         => get_permalink( $post_id ) . '#product',
            'name'        => $this->sanitize( get_the_title( $post_id ) ),
            'description' => $this->get_description( $post_id, $schema_data ),
            'url'         => get_permalink( $post_id ),
        );

        // image
        $image = $this->get_product_image( $post_id );
        if ( ! empty( $image ) ) {
            $schema['image'] = $image;
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
        $schema['offers'] = $this->get_manual_offers( $schema_data );

        return apply_filters( 'wpsm_product_schema', $schema, $post_id );
    }

    /**
     * WooCommerce offers döndür
     *
     * @param \WC_Product $product WooCommerce ürünü
     * @return array
     */
    private function get_woocommerce_offers( $product ) {
        $price = $product->get_price();
        $regular_price = $product->get_regular_price();

        $offer = array(
            '@type'         => 'Offer',
            'url'           => get_permalink( $product->get_id() ),
            'priceCurrency' => get_woocommerce_currency(),
            'price'         => $price ? number_format( (float) $price, 2, '.', '' ) : '0.00',
            'availability'  => $this->get_availability( $product ),
        );

        // priceValidUntil
        $sale_price_dates_to = $product->get_date_on_sale_to();
        if ( $sale_price_dates_to ) {
            $offer['priceValidUntil'] = $sale_price_dates_to->date( 'Y-m-d' );
        }

        return $offer;
    }

    /**
     * Manuel offers döndür
     *
     * @param array $schema_data Schema verisi
     * @return array
     */
    private function get_manual_offers( $schema_data ) {
        $offer = array(
            '@type'         => 'Offer',
            'priceCurrency' => ! empty( $schema_data['priceCurrency'] ) ? $this->sanitize( $schema_data['priceCurrency'] ) : 'USD',
            'price'         => ! empty( $schema_data['price'] ) ? number_format( (float) $schema_data['price'], 2, '.', '' ) : '0.00',
            'availability'  => ! empty( $schema_data['availability'] ) ? $this->sanitize( $schema_data['availability'] ) : 'https://schema.org/InStock',
        );

        // priceValidUntil
        if ( ! empty( $schema_data['priceValidUntil'] ) ) {
            $offer['priceValidUntil'] = sanitize_text_field( $schema_data['priceValidUntil'] );
        }

        return $offer;
    }

    /**
     * Stok durumunu döndür
     *
     * @param \WC_Product $product WooCommerce ürünü
     * @return string
     */
    private function get_availability( $product ) {
        if ( $product->is_in_stock() ) {
            if ( $product->managing_stock() && $product->backorders_allowed() ) {
                return 'https://schema.org/BackOrder';
            }
            if ( $product->is_on_backorder() ) {
                return 'https://schema.org/BackOrder';
            }
            return 'https://schema.org/InStock';
        }

        return 'https://schema.org/OutOfStock';
    }

    /**
     * Ürün markasını döndür
     *
     * @param \WC_Product $product WooCommerce ürünü
     * @return string
     */
    private function get_product_brand( $product ) {
        // WooCommerce Brands eklentisi varsa
        $brands = wp_get_post_terms( $product->get_id(), 'product_brand' );
        if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) {
            return $brands[0]->name;
        }

        // Custom field
        $brand = get_post_meta( $product->get_id(), '_wpsm_brand', true );
        if ( ! empty( $brand ) ) {
            return $brand;
        }

        return '';
    }

    /**
     * Ürün puanını döndür
     *
     * @param \WC_Product $product WooCommerce ürünü
     * @return array|null
     */
    private function get_product_rating( $product ) {
        $rating_count = $product->get_rating_count();
        $average = $product->get_average_rating();

        if ( $rating_count < 1 || empty( $average ) ) {
            return null;
        }

        return array(
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format( (float) $average, 1, '.', '' ),
            'reviewCount' => intval( $rating_count ),
            'bestRating'  => '5',
            'worstRating' => '1',
        );
    }

    /**
     * Ürün yorumlarını döndür
     *
     * @param \WC_Product $product WooCommerce ürünü
     * @return array
     */
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
                'author'       => array(
                    '@type' => 'Person',
                    'name'  => $this->sanitize( $comment->comment_author ),
                ),
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

    /**
     * Ürün görselini döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_product_image( $post_id ) {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $image_src ) {
                return array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $image_src[0] ),
                    'width'  => $image_src[1],
                    'height' => $image_src[2],
                );
            }
        }

        return array();
    }

    /**
     * Açıklama döndür
     *
     * @param int   $post_id     Post ID
     * @param array $schema_data Schema verisi
     * @return string
     */
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

    /**
     * String'i sanitize et
     *
     * @param string $string
     * @return string
     */
    private function sanitize( $string ) {
        if ( empty( $string ) ) {
            return '';
        }
        return sanitize_text_field( wp_strip_all_tags( $string ) );
    }
}
