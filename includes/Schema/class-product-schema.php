<?php
/**
 * Product Schema Sınıfı
 * 
 * Product schema oluşturur.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Schema Sınıfı
 * 
 * Product schema oluşturur.
 *
 * @since 1.0.0
 */
class Product_Schema {

	/**
	 * Product schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Product schema
	 */
	public function get_product_schema( $post ) {
		// WooCommerce kontrolü
		if ( class_exists( 'WooCommerce' ) && 'product' === $post->post_type ) {
			return $this->get_woocommerce_product_schema( $post );
		}

		// Normal post için ürün gibi schema
		$product_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => $post->post_title,
			'description' => wp_strip_all_tags( $post->post_content ),
			'url' => get_permalink( $post->ID ),
		);

		// SKU
		$sku = get_post_meta( $post->ID, '_wpsm_product_sku', true );
		if ( ! empty( $sku ) ) {
			$product_schema['sku'] = $sku;
		}

		// Brand
		$brand = get_post_meta( $post->ID, '_wpsm_product_brand', true );
		if ( ! empty( $brand ) ) {
			$product_schema['brand'] = array(
				'@type' => 'Brand',
				'name' => $brand,
			);
		}

		// Image
		if ( has_post_thumbnail( $post->ID ) ) {
			$thumb_id = get_post_thumbnail_id( $post->ID );
			$image = wp_get_attachment_image_src( $thumb_id, 'full' );

			if ( $image ) {
				$product_schema['image'] = array(
					'@type' => 'ImageObject',
					'url' => $image[0],
					'width' => $image[1],
					'height' => $image[2],
				);
			}
		}

		// Price info
		$price = get_post_meta( $post->ID, '_wpsm_product_price', true );
		$currency = get_post_meta( $post->ID, '_wpsm_product_currency', true ) ?: 'TRY';

		if ( ! empty( $price ) ) {
			$product_schema['offers'] = array(
				'@type' => 'Offer',
				'price' => $price,
				'priceCurrency' => $currency,
				'availability' => 'https://schema.org/InStock',
				'url' => get_permalink( $post->ID ),
			);
		}

		return $product_schema;
	}

	/**
	 * WooCommerce ürünü için schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Product schema
	 */
	private function get_woocommerce_product_schema( $post ) {
		$product = wc_get_product( $post->ID );

		if ( ! $product ) {
			return null;
		}

		$product_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
			'url' => $product->get_permalink(),
			'sku' => $product->get_sku(),
		);

		// Brand (manufacturer attribute)
		$manufacturer = $product->get_attribute( 'pa_manufacturer' );
		if ( ! empty( $manufacturer ) ) {
			$product_schema['brand'] = array(
				'@type' => 'Brand',
				'name' => $manufacturer,
			);
		}

		// Images
		$image_ids = array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() );
		$images = array();
		foreach ( $image_ids as $image_id ) {
			if ( $image_id ) {
				$image = wp_get_attachment_image_src( $image_id, 'full' );
				if ( $image ) {
					$images[] = $image[0];
				}
			}
		}
		if ( ! empty( $images ) ) {
			$product_schema['image'] = $images;
		}

		// Offers
		$offers = array(
			'@type' => 'Offer',
			'price' => $product->get_price(),
			'priceCurrency' => get_woocommerce_currency(),
			'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url' => $product->get_permalink(),
		);

		// Regular price vs sale price
		if ( $product->is_on_sale() ) {
			$offers['priceSpecification'] = array(
				'@type' => 'PriceSpecification',
				'price' => $product->get_regular_price(),
				'salePrice' => $product->get_sale_price(),
			);
		}

		$product_schema['offers'] = $offers;

		// Aggregate rating
		$rating_count = $product->get_rating_count();
		$average_rating = $product->get_average_rating();

		if ( $rating_count > 0 ) {
			$product_schema['aggregateRating'] = array(
				'@type' => 'AggregateRating',
				'ratingValue' => $average_rating,
				'reviewCount' => $rating_count,
			);
		}

		return $product_schema;
	}
}
