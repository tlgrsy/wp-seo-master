<?php
/**
 * LocalBusiness Schema Sınıfı
 * 
 * LocalBusiness schema oluşturur.
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
 * LocalBusiness Schema Sınıfı
 * 
 * LocalBusiness schema oluşturur.
 *
 * @since 1.0.0
 */
class LocalBusiness_Schema {

	/**
	 * LocalBusiness schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array LocalBusiness schema
	 */
	public function get_local_business_schema( $post ) {
		$business_name = get_post_meta( $post->ID, '_wpsm_local_name', true ) ?: $post->post_title;
		$phone = get_post_meta( $post->ID, '_wpsm_local_phone', true );
		$address = get_post_meta( $post->ID, '_wpsm_local_address', true );
		$opening_hours = get_post_meta( $post->ID, '_wpsm_local_opening_hours', true );

		if ( empty( $business_name ) ) {
			return null;
		}

		$local_business_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'LocalBusiness',
			'name' => $business_name,
			'description' => wp_strip_all_tags( $post->post_content ),
			'url' => get_permalink( $post->ID ),
		);

		// Phone
		if ( ! empty( $phone ) ) {
			$local_business_schema['telephone'] = $phone;
		}

		// Address
		if ( ! empty( $address ) ) {
			$local_business_schema['address'] = array(
				'@type' => 'PostalAddress',
				'streetAddress' => $address,
				'addressCountry' => 'TR', // Varsayılan
			);
		}

		// Opening hours
		if ( ! empty( $opening_hours ) ) {
			$local_business_schema['openingHours'] = $opening_hours;
		}

		// Image
		if ( has_post_thumbnail( $post->ID ) ) {
			$thumb_id = get_post_thumbnail_id( $post->ID );
			$image = wp_get_attachment_image_src( $thumb_id, 'full' );

			if ( $image ) {
				$local_business_schema['image'] = array(
					'@type' => 'ImageObject',
					'url' => $image[0],
					'width' => $image[1],
					'height' => $image[2],
				);
			}
		}

		// Price range
		$price_range = get_post_meta( $post->ID, '_wpsm_local_price_range', true );
		if ( ! empty( $price_range ) ) {
			$local_business_schema['priceRange'] = $price_range;
		}

		// Geo coordinates
		$lat = get_post_meta( $post->ID, '_wpsm_local_lat', true );
		$lng = get_post_meta( $post->ID, '_wpsm_local_lng', true );

		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$local_business_schema['geo'] = array(
				'@type' => 'GeoCoordinates',
				'latitude' => $lat,
				'longitude' => $lng,
			);
		}

		return $local_business_schema;
	}
}
