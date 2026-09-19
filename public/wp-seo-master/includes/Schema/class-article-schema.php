<?php
/**
 * Article Schema Sınıfı
 *
 * Article, BlogPosting, NewsArticle şemalarını oluşturur.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Article_Schema {

    private $options;
    private $schema_type;

    public function __construct( $options, $schema_type = 'Article' ) {
        $this->options     = $options;
        $this->schema_type = $schema_type;
    }

    /**
     * Article şemasını döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    public function get_schema( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post ) {
            return array();
        }

        $schema = array(
            '@type'            => $this->get_schema_type(),
            '@id'              => get_permalink( $post_id ) . '#article',
            'isPartOf'         => array( '@id' => get_permalink( $post_id ) . '#webpage' ),
            'mainEntityOfPage' => array( '@id' => get_permalink( $post_id ) . '#webpage' ),
            'headline'         => $this->sanitize_string( get_the_title( $post_id ) ),
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_the_modified_date( 'c', $post_id ),
        );

        // Description
        $description = $this->get_post_description( $post_id );
        if ( ! empty( $description ) ) {
            $schema['description'] = $description;
        }

        // Author (Person)
        $author = $this->get_author_schema( $post->post_author );
        if ( ! empty( $author ) ) {
            $schema['author'] = $author;
        }

        // Publisher (Organization)
        $schema['publisher'] = array(
            '@id' => home_url( '/#organization' ),
        );

        // Image
        $image = $this->get_article_image( $post_id );
        if ( ! empty( $image ) ) {
            $schema['image'] = $image;
            $schema['thumbnailUrl'] = $image['url'];
        }

        // Article section (kategori)
        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            $schema['articleSection'] = $this->sanitize_string( $categories[0]->name );
        }

        // Keywords (etiketler)
        $tags = get_the_tags( $post_id );
        if ( ! empty( $tags ) ) {
            $keywords = array();
            foreach ( $tags as $tag ) {
                $keywords[] = $this->sanitize_string( $tag->name );
            }
            $schema['keywords'] = implode( ', ', $keywords );
        }

        // Word count
        $word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
        $schema['wordCount'] = $word_count;

        // Comment count
        $schema['commentCount'] = intval( $post->comment_count );

        // Breadcrumb
        $schema['breadcrumb'] = array(
            '@id' => get_permalink( $post_id ) . '#breadcrumb',
        );

        /**
         * Article şemasını filtrele
         *
         * @param array $schema  Şema verisi
         * @param int   $post_id Post ID
         */
        return apply_filters( 'wpsm_article_schema', $schema, $post_id );
    }

    /**
     * Şema tipini döndür
     *
     * @return string
     */
    private function get_schema_type() {
        $allowed_types = array( 'Article', 'BlogPosting', 'NewsArticle' );

        if ( in_array( $this->schema_type, $allowed_types, true ) ) {
            return $this->schema_type;
        }

        return 'Article';
    }

    /**
     * Author (Person) şemasını döndür
     *
     * @param int $author_id Yazar ID
     * @return array
     */
    private function get_author_schema( $author_id ) {
        $author = get_userdata( $author_id );

        if ( ! $author ) {
            return array();
        }

        $schema = array(
            '@type' => 'Person',
            'name'  => $this->sanitize_string( $author->display_name ),
        );

        // Author URL
        $author_url = get_author_posts_url( $author_id );
        if ( ! empty( $author_url ) ) {
            $schema['url'] = esc_url( $author_url );
        }

        // Author description
        $description = get_the_author_meta( 'description', $author_id );
        if ( ! empty( $description ) ) {
            $schema['description'] = $this->sanitize_string( $description );
        }

        // Author image (avatar)
        $avatar_url = get_avatar_url( $author_id, array( 'size' => 200 ) );
        if ( ! empty( $avatar_url ) ) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url'   => esc_url( $avatar_url ),
            );
        }

        // Sosyal profiller (kullanıcı meta)
        $social_profiles = array();

        $twitter = get_the_author_meta( 'twitter', $author_id );
        if ( ! empty( $twitter ) ) {
            $twitter = ltrim( $twitter, '@' );
            $social_profiles[] = 'https://twitter.com/' . $twitter;
        }

        $facebook = get_the_author_meta( 'facebook', $author_id );
        if ( ! empty( $facebook ) ) {
            if ( strpos( $facebook, 'http' ) !== 0 ) {
                $facebook = 'https://facebook.com/' . $facebook;
            }
            $social_profiles[] = esc_url( $facebook );
        }

        $linkedin = get_the_author_meta( 'linkedin', $author_id );
        if ( ! empty( $linkedin ) ) {
            if ( strpos( $linkedin, 'http' ) !== 0 ) {
                $linkedin = 'https://linkedin.com/in/' . $linkedin;
            }
            $social_profiles[] = esc_url( $linkedin );
        }

        if ( ! empty( $social_profiles ) ) {
            $schema['sameAs'] = $social_profiles;
        }

        return $schema;
    }

    /**
     * Makale görselini döndür
     *
     * @param int $post_id Post ID
     * @return array
     */
    private function get_article_image( $post_id ) {
        // Öncelik 1: Özel OG görseli
        $og_image = get_post_meta( $post_id, '_wpsm_og_image', true );
        if ( ! empty( $og_image ) ) {
            $attachment_id = attachment_url_to_postid( $og_image );
            if ( $attachment_id ) {
                $meta = wp_get_attachment_metadata( $attachment_id );
                $alt  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

                $image = array(
                    '@type' => 'ImageObject',
                    'url'   => esc_url( $og_image ),
                );

                if ( $meta ) {
                    $image['width']  = $meta['width'];
                    $image['height'] = $meta['height'];
                }

                if ( ! empty( $alt ) ) {
                    $image['caption'] = $this->sanitize_string( $alt );
                }

                return $image;
            }
        }

        // Öncelik 2: Featured image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $image_src ) {
                $alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );

                $image = array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $image_src[0] ),
                    'width'  => $image_src[1],
                    'height' => $image_src[2],
                );

                if ( ! empty( $alt ) ) {
                    $image['caption'] = $this->sanitize_string( $alt );
                }

                return $image;
            }
        }

        // Öncelik 3: Varsayılan OG görseli
        $default_image = $this->options->get( 'default_og_image', '' );
        if ( ! empty( $default_image ) ) {
            $attachment_id = attachment_url_to_postid( $default_image );
            $image = array(
                '@type' => 'ImageObject',
                'url'   => esc_url( $default_image ),
            );

            if ( $attachment_id ) {
                $meta = wp_get_attachment_metadata( $attachment_id );
                if ( $meta ) {
                    $image['width']  = $meta['width'];
                    $image['height'] = $meta['height'];
                }
            }

            return $image;
        }

        return array();
    }

    /**
     * Post açıklamasını döndür
     *
     * @param int $post_id Post ID
     * @return string
     */
    private function get_post_description( $post_id ) {
        $description = get_post_meta( $post_id, '_wpsm_description', true );

        if ( empty( $description ) ) {
            $excerpt = get_the_excerpt( $post_id );
            if ( ! empty( $excerpt ) ) {
                $description = wp_strip_all_tags( $excerpt );
            }
        }

        if ( empty( $description ) ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
            }
        }

        return $this->sanitize_string( $description );
    }

    /**
     * String'i sanitize et
     *
     * @param string $string
     * @return string
     */
    private function sanitize_string( $string ) {
        if ( empty( $string ) ) {
            return '';
        }

        $string = wp_strip_all_tags( $string );
        $string = sanitize_text_field( $string );

        return $string;
    }
}
