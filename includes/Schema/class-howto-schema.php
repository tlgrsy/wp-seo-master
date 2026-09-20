<?php
/**
 * HowTo Schema Sınıfı
 * 
 * HowTo schema oluşturur.
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
 * HowTo Schema Sınıfı
 * 
 * HowTo schema oluşturur.
 *
 * @since 1.0.0
 */
class HowTo_Schema {

	/**
	 * HowTo schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array HowTo schema
	 */
	public function get_howto_schema( $post ) {
		$howto_data = get_post_meta( $post->ID, '_wpsm_schema_data', true );
		$steps = json_decode( $howto_data, true );

		if ( empty( $steps ) || ! is_array( $steps ) ) {
			return null;
		}

		$howto_steps = array();
		foreach ( $steps as $step ) {
			if ( empty( $step['name'] ) || empty( $step['text'] ) ) {
				continue;
			}

			$howto_steps[] = array(
				'@type' => 'HowToStep',
				'name' => wp_strip_all_tags( $step['name'] ),
				'text' => wp_strip_all_tags( $step['text'] ),
			);
		}

		if ( empty( $howto_steps ) ) {
			return null;
		}

		$howto_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'HowTo',
			'name' => $post->post_title,
			'description' => wp_strip_all_tags( wp_trim_words( $post->post_content, 30, '...' ) ),
			'step' => $howto_steps,
		);

		// Estimated duration
		$total_time = count( $howto_steps ) * 5; // 5 minutes per step estimate
		$howto_schema['totalTime'] = 'PT' . $total_time . 'M'; // PT5M format

		// Estimated cost
		$cost = get_post_meta( $post->ID, '_wpsm_howto_cost', true );
		if ( ! empty( $cost ) ) {
			$howto_schema['estimatedCost'] = array(
				'@type' => 'MonetaryAmount',
				'currency' => 'TRY',
				'value' => $cost,
			);
		}

		return $howto_schema;
	}
}
