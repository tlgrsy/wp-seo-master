<?php
/**
 * LocalBusiness Schema Sınıfı
 *
 * LocalBusiness şemasını oluşturur.
 * İşletme bilgileri metabox'ta girilir.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

        if ( empty( $schema_data ) || ! is_array( $schema_data ) ) {
            return array();
        }

        $schema = array(
            '@type' => 'LocalBusiness',
            '@id'   => get_permalink( $post_id ) . '#localbusiness',
            'name'  => $this->get_name( $post_id, $schema_data ),
            'url'   => get_permalink( $post_id ),
        );

        // image
        $image = $this->get_business_image( $post_id );
        if ( ! empty( $image ) ) {
            $schema['image'] = $image;
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

    /**
     * İşletme adını döndür
     *
     * @param int   $post_id     Post ID
     * @param array $schema_data Schema verisi
     * @return string
     */
    private function get_name( $post_id, $schema_data ) {
        if ( ! empty( $schema_data['name'] ) ) {
            return $this->sanitize( $schema_data['name'] );
        }

        return $this->sanitize( get_the_title( $post_id ) );
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
     * İşletme görselini döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_business_image( $post_id ) {
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
     * Adres döndür (PostalAddress)
     *
     * @param array $schema_data Schema verisi
     * @return array
     */
    private function get_address( $schema_data ) {
        $address = array(
            '@type' => 'PostalAddress',
        );

        $has_address = false;

        // streetAddress
        if ( ! empty( $schema_data['streetAddress'] ) ) {
            $address['streetAddress'] = $this->sanitize( $schema_data['streetAddress'] );
            $has_address = true;
        }

        // addressLocality (şehir)
        if ( ! empty( $schema_data['addressLocality'] ) ) {
            $address['addressLocality'] = $this->sanitize( $schema_data['addressLocality'] );
            $has_address = true;
        }

        // addressRegion (bölge/eyalet)
        if ( ! empty( $schema_data['addressRegion'] ) ) {
            $address['addressRegion'] = $this->sanitize( $schema_data['addressRegion'] );
            $has_address = true;
        }

        // postalCode
        if ( ! empty( $schema_data['postalCode'] ) ) {
            $address['postalCode'] = $this->sanitize( $schema_data['postalCode'] );
            $has_address = true;
        }

        // addressCountry
        if ( ! empty( $schema_data['addressCountry'] ) ) {
            $address['addressCountry'] = $this->sanitize( $schema_data['addressCountry'] );
            $has_address = true;
        }

        if ( ! $has_address ) {
            return array();
        }

        return $address;
    }

    /**
     * Geo koordinatları döndür (GeoCoordinates)
     *
     * @param array $schema_data Schema verisi
     * @return array
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
     *
     * @param array $schema_data Schema verisi
     * @return array
     */
    private function get_opening_hours( $schema_data ) {
        if ( empty( $schema_data['openingHours'] ) || ! is_array( $schema_data['openingHours'] ) ) {
            return array();
        }

        $hours = array();

        foreach ( $schema_data['openingHours'] as $hour ) {
            if ( empty( $hour['day'] ) || empty( $hour['opens'] ) || empty( $hour['closes'] ) ) {
                continue;
            }

            // Gün formatını kontrol et (Mo, Tu, We, Th, Fr, Sa, Su)
            $day_map = array(
                'monday'    => 'Mo',
                'tuesday'   => 'Tu',
                'wednesday' => 'We',
                'thursday'  => 'Th',
                'friday'    => 'Fr',
                'saturday'  => 'Sa',
                'sunday'    => 'Su',
            );

            $day = strtolower( $hour['day'] );
            if ( isset( $day_map[ $day ] ) ) {
                $day = $day_map[ $day ];
            } elseif ( ! in_array( $day, array( 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' ), true ) ) {
                continue;
            }

            // Saat formatını kontrol et (HH:MM)
            $opens = sanitize_text_field( $hour['opens'] );
            $closes = sanitize_text_field( $hour['closes'] );

            if ( ! preg_match( '/^\d{2}:\d{2}$/', $opens ) || ! preg_match( '/^\d{2}:\d{2}$/', $closes ) ) {
                continue;
            }

            $hours[] = sprintf( '%s %s-%s', $day, $opens, $closes );
        }

        return $hours;
    }

    /**
     * Sosyal profilleri döndür (sameAs)
     *
     * @param array $schema_data Schema verisi
     * @return array
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
