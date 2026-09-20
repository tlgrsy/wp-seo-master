<?php
/**
 * FAQ Schema Sınıfı
 * 
 * FAQPage schema oluşturur.
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
 * FAQ Schema Sınıfı
 * 
 * FAQPage schema oluşturur.
 *
 * @since 1.0.0
 */
class FAQ_Schema {

	/**
	 * FAQ schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array FAQ schema
	 */
	public function get_faq_schema( $post ) {
		$faq_data = get_post_meta( $post->ID, '_wpsm_schema_data', true );
		$faqs = json_decode( $faq_data, true );

		if ( empty( $faqs ) || ! is_array( $faqs ) ) {
			return null;
		}

		$questions = array();
		foreach ( $faqs as $faq ) {
			if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
				continue;
			}

			$questions[] = array(
				'@type' => 'Question',
				'name' => wp_strip_all_tags( $faq['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => wp_strip_all_tags( $faq['answer'] ),
				),
			);
		}

		if ( empty( $questions ) ) {
			return null;
		}

		$faq_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => $questions,
		);

		return $faq_schema;
	}
}
