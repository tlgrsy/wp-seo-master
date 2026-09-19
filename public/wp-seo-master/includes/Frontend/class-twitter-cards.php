<?php
/**
 * Twitter Cards Sınıfı
 *
 * Twitter/X için meta etiketleri oluşturur.
 *
 * @package WPSM\Frontend
 * @since 1.0.0
 */

namespace WPSM\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Twitter_Cards {

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
     * Twitter Cards etiketlerini çıktıla (wp_head priority 6)
     */
    public function output_twitter_cards() {
        if ( $this->other_seo_active ) {
            return;
        }

        $enabled = $this->options->get( 'enable_twitter', true );
        if ( ! $enabled ) {
            return;
        }

        // twitter:card
        $card_type = $this->options->get( 'twitter_card_type', 'summary_large_image' );
        echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\n";

        // twitter:site
        $twitter_site = $this->options->get( 'twitter_site', '' );
        if ( ! empty( $twitter_site ) ) {
            // @ işareti ekle (yoksa)
            if ( strpos( $twitter_site, '@' ) !== 0 ) {
                $twitter_site = '@' . $twitter_site;
            }
            echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '" />' . "\n";
        }

        // Twitter verilerini topla
        $twitter_data = $this->get_twitter_data();

        // twitter:title
        if ( ! empty( $twitter_data['title'] ) ) {
            echo '<meta name="twitter:title" content="' . esc_attr( $twitter_data['title'] ) . '" />' . "\n";
        }

        // twitter:description
        if ( ! empty( $twitter_data['description'] ) ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $twitter_data['description'] ) . '" />' . "\n";
        }

        // twitter:image
        if ( ! empty( $twitter_data['image'] ) ) {
            echo '<meta name="twitter:image" content="' . esc_url( $twitter_data['image'] ) . '" />' . "\n";

            // twitter:image:alt
            if ( ! empty( $twitter_data['image_alt'] ) ) {
                echo '<meta name="twitter:image:alt" content="' . esc_attr( $twitter_data['image_alt'] ) . '" />' . "\n";
            }
        }

        // twitter:creator (post author)
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $creator = get_post_meta( $post_id, '_wpsm_twitter_creator', true );

            if ( empty( $creator ) ) {
                // Yazarın Twitter hesabını al
                $author_id = get_post_field( 'post_author', $post_id );
                $creator = get_the_author_meta( 'twitter', $author_id );
            }

            if ( ! empty( $creator ) ) {
                if ( strpos( $creator, '@' ) !== 0 ) {
                    $creator = '@' . $creator;
                }
                echo '<meta name="twitter:creator" content="' . esc_attr( $creator ) . '" />' . "\n";
            }
        }
    }

    /**
     * Twitter verilerini topla
     *
     * Öncelik: post meta > OG meta > otomatik
     *
     * @return array
     */
    private function get_twitter_data() {
        $data = array(
            'title'       => '',
            'description' => '',
            'image'       => '',
            'image_alt'   => '',
        );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Post meta'dan al
            $data['title'] = get_post_meta( $post_id, '_wpsm_twitter_title', true );
            $data['description'] = get_post_meta( $post_id, '_wpsm_twitter_description', true );

            // Boşsa OG meta'dan al
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_post_meta( $post_id, '_wpsm_og_title', true );
            }

            if ( empty( $data['description'] ) ) {
                $data['description'] = get_post_meta( $post_id, '_wpsm_og_description', true );
            }

            // Hala boşsa SEO meta'dan al
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

            // Twitter görseli
            $twitter_image = get_post_meta( $post_id, '_wpsm_twitter_image', true );

            if ( ! empty( $twitter_image ) ) {
                $data['image'] = $twitter_image;

                // Alt text
                $attachment_id = attachment_url_to_postid( $twitter_image );
                if ( $attachment_id ) {
                    $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            } else {
                // OG görselini kullan
                $og_image = get_post_meta( $post_id, '_wpsm_og_image', true );

                if ( ! empty( $og_image ) ) {
                    $data['image'] = $og_image;

                    $attachment_id = attachment_url_to_postid( $og_image );
                    if ( $attachment_id ) {
                        $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                    }
                } else {
                    // Featured image kullan
                    $thumbnail_id = get_post_thumbnail_id( $post_id );
                    if ( $thumbnail_id ) {
                        $image = wp_get_attachment_image_src( $thumbnail_id, 'large' );
                        if ( $image ) {
                            $data['image'] = $image[0];
                            $data['image_alt'] = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                        }
                    }
                }
            }
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

            // Varsayılan OG görseli kullan
            $default_image = $this->options->get( 'default_og_image', '' );
            if ( ! empty( $default_image ) ) {
                $data['image'] = $default_image;

                $attachment_id = attachment_url_to_postid( $default_image );
                if ( $attachment_id ) {
                    $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            }
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_archive_title();
            }

            if ( empty( $data['description'] ) ) {
                $data['description'] = wp_strip_all_tags( get_the_archive_description() );
            }
        }

        // Arama sayfası
        if ( is_search() ) {
            $search_query = get_search_query();
            $data['title'] = sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query );
        }

        return $data;
    }
}
