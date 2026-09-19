<?php
/**
 * FAQ Schema Sınıfı
 *
 * FAQPage şemasını oluşturur.
 * Soru-cevap çiftleri metabox'ta repeater olarak girilir.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
