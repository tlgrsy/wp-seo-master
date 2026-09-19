<?php
/**
 * HowTo Schema Sınıfı
 *
 * HowTo şemasını oluşturur.
 * Adımlar metabox'ta repeater olarak girilir.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
                '@type'  => 'MonetaryAmount',
                'currency' => 'USD',
                'value'  => sanitize_text_field( $schema_data['estimatedCost'] ),
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
            if ( empty( $step['name'] ) ) {
                continue;
            }

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
