<?php
/**
 * Breadcrumb Schema Sınıfı
 *
 * BreadcrumbList JSON-LD şemasını oluşturur.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Breadcrumb_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * BreadcrumbList şemasını döndür
     *
     * @return array
     */
    public function get_schema() {
        $items = $this->get_breadcrumb_items();

        if ( empty( $items ) ) {
            return array();
        }

        $schema = array(
            '@type'           => 'BreadcrumbList',
            '@id'             => $this->get_current_url() . '#breadcrumb',
            'itemListElement' => array(),
        );

        $position = 1;
        foreach ( $items as $item ) {
            $list_item = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $this->sanitize_string( $item['name'] ),
            );

            if ( ! empty( $item['url'] ) ) {
                $list_item['item'] = esc_url( $item['url'] );
            }

            $schema['itemListElement'][] = $list_item;
            $position++;
        }

        /**
         * Breadcrumb şemasını filtrele
         *
         * @param array $schema Şema verisi
         * @param array $items  Breadcrumb öğeleri
         */
        return apply_filters( 'wpsm_breadcrumb_schema', $schema, $items );
    }

    /**
     * Breadcrumb öğelerini döndür
     *
     * @return array
     */
    private function get_breadcrumb_items() {
        $items = array();

        // Ana sayfa (her zaman ilk)
        $home_text = $this->options->get( 'breadcrumb_home_text', __( 'Ana Sayfa', 'wp-seo-master' ) );
        $items[] = array(
            'name' => $home_text,
            'url'  => home_url( '/' ),
        );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post = get_post( $post_id );

            if ( ! $post ) {
                return $items;
            }

            // Post type arşivi
            $post_type = $post->post_type;
            $post_type_obj = get_post_type_object( $post_type );

            if ( $post_type_obj && $post_type !== 'post' && $post_type !== 'page' ) {
                $archive_link = get_post_type_archive_link( $post_type );
                if ( $archive_link ) {
                    $items[] = array(
                        'name' => $post_type_obj->labels->name,
                        'url'  => $archive_link,
                    );
                }
            }

            // Kategori hiyerarşisi (post için)
            if ( 'post' === $post_type ) {
                $categories = get_the_category( $post_id );
                if ( ! empty( $categories ) ) {
                    $main_category = $categories[0];
                    $category_chain = $this->get_category_hierarchy( $main_category );

                    foreach ( $category_chain as $category ) {
                        $items[] = array(
                            'name' => $category->name,
                            'url'  => get_category_link( $category->term_id ),
                        );
                    }
                }
            }

            // Sayfa ebeveyn hiyerarşisi (page için)
            if ( 'page' === $post_type ) {
                $parent_chain = $this->get_page_hierarchy( $post_id );
                foreach ( $parent_chain as $parent_id ) {
                    $items[] = array(
                        'name' => get_the_title( $parent_id ),
                        'url'  => get_permalink( $parent_id ),
                    );
                }
            }

            // Mevcut sayfa (son öğe, URL yok)
            $breadcrumb_title = get_post_meta( $post_id, '_wpsm_breadcrumb_title', true );
            $items[] = array(
                'name' => ! empty( $breadcrumb_title ) ? $breadcrumb_title : get_the_title( $post_id ),
                'url'  => '',
            );
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            // Kategori arşivi
            if ( is_category() ) {
                $category = get_queried_object();
                $category_chain = $this->get_category_hierarchy( $category );

                foreach ( $category_chain as $cat ) {
                    $items[] = array(
                        'name' => $cat->name,
                        'url'  => get_category_link( $cat->term_id ),
                    );
                }
            }

            // Etiket arşivi
            if ( is_tag() ) {
                $tag = get_queried_object();
                $items[] = array(
                    'name' => $tag->name,
                    'url'  => '',
                );
            }

            // Taksonomi arşivi
            if ( is_tax() ) {
                $term = get_queried_object();
                $items[] = array(
                    'name' => $term->name,
                    'url'  => '',
                );
            }

            // Yazar arşivi
            if ( is_author() ) {
                $author = get_queried_object();
                $items[] = array(
                    'name' => $author->display_name,
                    'url'  => '',
                );
            }

            // Tarih arşivi
            if ( is_date() ) {
                if ( is_year() ) {
                    $items[] = array(
                        'name' => get_the_date( 'Y' ),
                        'url'  => '',
                    );
                } elseif ( is_month() ) {
                    $items[] = array(
                        'name' => get_the_date( 'Y' ),
                        'url'  => get_year_link( get_query_var( 'year' ) ),
                    );
                    $items[] = array(
                        'name' => get_the_date( 'F' ),
                        'url'  => '',
                    );
                } elseif ( is_day() ) {
                    $items[] = array(
                        'name' => get_the_date( 'Y' ),
                        'url'  => get_year_link( get_query_var( 'year' ) ),
                    );
                    $items[] = array(
                        'name' => get_the_date( 'F' ),
                        'url'  => get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) ),
                    );
                    $items[] = array(
                        'name' => get_the_date( 'j' ),
                        'url'  => '',
                    );
                }
            }

            // Post type arşivi
            if ( is_post_type_archive() ) {
                $post_type = get_query_var( 'post_type' );
                if ( is_array( $post_type ) ) {
                    $post_type = reset( $post_type );
                }
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj ) {
                    $items[] = array(
                        'name' => $post_type_obj->labels->name,
                        'url'  => '',
                    );
                }
            }
        }

        // Arama sayfası
        if ( is_search() ) {
            $search_query = get_search_query();
            /* translators: %s: arama sorgusu */
            $items[] = array(
                'name' => sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query ),
                'url'  => '',
            );
        }

        // 404 sayfası
        if ( is_404() ) {
            $items[] = array(
                'name' => __( 'Sayfa Bulunamadı', 'wp-seo-master' ),
                'url'  => '',
            );
        }

        return $items;
    }

    /**
     * Kategori hiyerarşisini döndür
     *
     * @param object $category Kategori objesi
     * @return array
     */
    private function get_category_hierarchy( $category ) {
        $chain = array();

        if ( ! $category ) {
            return $chain;
        }

        // Üst kategorileri al
        $parent_id = $category->parent;
        while ( $parent_id ) {
            $parent = get_category( $parent_id );
            if ( $parent && ! is_wp_error( $parent ) ) {
                array_unshift( $chain, $parent );
                $parent_id = $parent->parent;
            } else {
                break;
            }
        }

        // Mevcut kategoriyi ekle
        $chain[] = $category;

        return $chain;
    }

    /**
     * Sayfa ebeveyn hiyerarşisini döndür
     *
     * @param int $page_id Sayfa ID
     * @return array
     */
    private function get_page_hierarchy( $page_id ) {
        $chain = array();

        $page = get_post( $page_id );
        if ( ! $page ) {
            return $chain;
        }

        // Üst sayfaları al
        $parent_id = $page->post_parent;
        while ( $parent_id ) {
            array_unshift( $chain, $parent_id );
            $parent = get_post( $parent_id );
            if ( $parent ) {
                $parent_id = $parent->post_parent;
            } else {
                break;
            }
        }

        return $chain;
    }

    /**
     * Mevcut URL'i döndür
     *
     * @return string
     */
    private function get_current_url() {
        if ( is_singular() ) {
            return get_permalink();
        }

        if ( is_front_page() ) {
            return home_url( '/' );
        }

        if ( is_archive() ) {
            if ( is_category() ) {
                return get_category_link( get_queried_object_id() );
            }
            if ( is_tag() ) {
                return get_tag_link( get_queried_object_id() );
            }
            if ( is_tax() ) {
                return get_term_link( get_queried_object() );
            }
            if ( is_author() ) {
                return get_author_posts_url( get_queried_object_id() );
            }
            if ( is_post_type_archive() ) {
                return get_post_type_archive_link( get_query_var( 'post_type' ) );
            }
        }

        if ( is_search() ) {
            return get_search_link();
        }

        return home_url( $_SERVER['REQUEST_URI'] );
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
