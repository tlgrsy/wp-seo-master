<?php
/**
 * Breadcrumbs Sınıfı
 * 
 * Frontend'de breadcrumb navigasyonu oluşturur.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM\Frontend;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumbs Sınıfı
 * 
 * Breadcrumb navigasyonu oluşturur.
 *
 * @since 1.0.0
 */
class Breadcrumbs {

	/**
	 * Options sınıfı örneği
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private $options;

	/**
	 * Sınıfı başlatır
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		$this->options = Options::get_instance();
		
		// Çakışma kontrolü
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) && $this->options->get( 'enable_breadcrumbs', true ) ) {
			// Shortcode kaydet
			add_shortcode( 'wpsm_breadcrumb', array( $this, 'breadcrumb_shortcode' ) );
			
			// Global helper function
			if ( ! function_exists( 'wpsm_breadcrumbs' ) ) {
				/**
				 * Breadcrumb output eder
				 *
				 * @since 1.0.0
				 * @param array $args Argümanlar
				 * @return string|void
				 */
				function wpsm_breadcrumbs( $args = array() ) {
					$instance = new \WPSM\Frontend\Breadcrumbs();
					return $instance->output_breadcrumbs( $args );
				}
			}
		}
	}

	/**
	 * Breadcrumb output eder
	 *
	 * @since 1.0.0
	 * @param array $args Argümanlar
	 * @return string Breadcrumb HTML
	 */
	public function output_breadcrumbs( $args = array() ) {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return '';
		}

		if ( ! $this->options->get( 'enable_breadcrumbs', true ) ) {
			return '';
		}

		$defaults = array(
			'separator'     => '&raquo;',
			'home_text'     => __( 'Ana Sayfa', 'wp-seo-master' ),
			'show_on_home'  => true,
			'before'        => '<nav aria-label="Breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">',
			'after'         => '</nav>',
			'wrap_before'   => '<ol>',
			'wrap_after'    => '</ol>',
			'item_before'   => '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">',
			'item_after'    => '</li>',
			'link_before'   => '<span itemprop="name">',
			'link_after'    => '</span>',
		);

		$args = wp_parse_args( $args, $defaults );

		$breadcrumbs = $this->get_breadcrumbs();

		if ( empty( $breadcrumbs ) ) {
			return '';
		}

		$output = $args['before'];
		$output .= $args['wrap_before'];

		$item_count = 0;
		foreach ( $breadcrumbs as $breadcrumb ) {
			$item_count++;
			$output .= $args['item_before'];

			if ( ! empty( $breadcrumb['url'] ) && $item_count < count( $breadcrumbs ) ) {
				$output .= '<a href="' . esc_url( $breadcrumb['url'] ) . '" itemprop="item">';
				$output .= $args['link_before'];
				$output .= esc_html( $breadcrumb['text'] );
				$output .= $args['link_after'];
				$output .= '</a>';
			} else {
				$output .= $args['link_before'];
				$output .= esc_html( $breadcrumb['text'] );
				$output .= $args['link_after'];
			}

			$output .= '<meta itemprop="position" content="' . esc_attr( $item_count ) . '" />';
			$output .= $args['item_after'];

			if ( $item_count < count( $breadcrumbs ) ) {
				$output .= '<span class="breadcrumb-separator">' . esc_html( $args['separator'] ) . '</span>';
			}
		}

		$output .= $args['wrap_after'];
		$output .= $args['after'];

		return $output;
	}

	/**
	 * Breadcrumb array'ini alır
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb array
	 */
	public function get_breadcrumbs() {
		$breadcrumbs = array();

		// Anasayfa
		if ( is_home() || is_front_page() ) {
			if ( $this->options->get( 'show_on_home', true ) ) {
				$breadcrumbs[] = array(
					'text' => $this->options->get( 'home_text', __( 'Ana Sayfa', 'wp-seo-master' ) ),
					'url'  => home_url( '/' ),
				);
			}
		} else {
			// Anasayfa linki
			$breadcrumbs[] = array(
				'text' => $this->options->get( 'home_text', __( 'Ana Sayfa', 'wp-seo-master' ) ),
				'url'  => home_url( '/' ),
			);

			if ( is_category() ) {
				// Kategori breadcrumb'ı
				$cat = get_queried_object();
				$cat_parents = array_reverse( get_ancestors( $cat->term_id, 'category' ) );
				
				foreach ( $cat_parents as $parent_id ) {
					$parent = get_term( $parent_id, 'category' );
					if ( $parent ) {
						$breadcrumbs[] = array(
							'text' => $parent->name,
							'url'  => get_term_link( $parent ),
						);
					}
				}
				
				$breadcrumbs[] = array(
					'text' => $cat->name,
					'url'  => get_term_link( $cat ),
				);
			} elseif ( is_tag() ) {
				// Etiket breadcrumb'ı
				$tag = get_queried_object();
				$breadcrumbs[] = array(
					'text' => $tag->name,
					'url'  => get_term_link( $tag ),
				);
			} elseif ( is_tax() ) {
				// Taksonomi breadcrumb'ı
				$term = get_queried_object();
				$term_parents = array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) );
				
				foreach ( $term_parents as $parent_id ) {
					$parent = get_term( $parent_id, $term->taxonomy );
					if ( $parent ) {
						$breadcrumbs[] = array(
							'text' => $parent->name,
							'url'  => get_term_link( $parent ),
						);
					}
				}
				
				$breadcrumbs[] = array(
					'text' => $term->name,
					'url'  => get_term_link( $term ),
				);
			} elseif ( is_author() ) {
				// Yazar breadcrumb'ı
				$author = get_queried_object();
				$breadcrumbs[] = array(
					'text' => $author->display_name,
					'url'  => get_author_posts_url( $author->ID ),
				);
			} elseif ( is_date() ) {
				// Tarih archive breadcrumb'ı
				$breadcrumbs[] = array(
					'text' => get_the_archive_title(),
					'url'  => '',
				);
			} elseif ( is_search() ) {
				// Arama sonuçları breadcrumb'ı
				$breadcrumbs[] = array(
					'text' => sprintf( __( 'Arama: %s', 'wp-seo-master' ), get_search_query() ),
					'url'  => '',
				);
			} elseif ( is_404() ) {
				// 404 sayfası breadcrumb'ı
				$breadcrumbs[] = array(
					'text' => __( 'Sayfa Bulunamadı', 'wp-seo-master' ),
					'url'  => '',
				);
			} elseif ( is_singular() ) {
				// Tekil içerik breadcrumb'ı
				$post = get_queried_object();
				
				// Custom breadcrumb başlığı varsa kullan
				$custom_title = get_post_meta( $post->ID, '_wpsm_breadcrumb_title', true );
				$breadcrumb_title = ! empty( $custom_title ) ? $custom_title : $post->post_title;
				
				if ( 'page' === $post->post_type && $post->post_parent ) {
					// Sayfa hiyerarşisi
					$parents = array_reverse( get_post_ancestors( $post->ID ) );
					foreach ( $parents as $parent_id ) {
						$parent = get_post( $parent_id );
						if ( $parent ) {
							$breadcrumbs[] = array(
								'text' => $parent->post_title,
								'url'  => get_permalink( $parent->ID ),
							);
						}
					}
				} elseif ( 'post' === $post->post_type ) {
					// Post kategorileri
					$cats = get_the_category( $post->ID );
					if ( $cats && ! is_wp_error( $cats ) ) {
						$cat = $cats[0]; // En az seviyedeki kategori
						$cat_parents = array_reverse( get_ancestors( $cat->term_id, 'category' ) );
						
						foreach ( $cat_parents as $parent_id ) {
							$parent = get_term( $parent_id, 'category' );
							if ( $parent ) {
								$breadcrumbs[] = array(
									'text' => $parent->name,
									'url'  => get_term_link( $parent ),
								);
							}
						}
						
						$breadcrumbs[] = array(
							'text' => $cat->name,
							'url'  => get_term_link( $cat ),
						);
					}
				}
				
				$breadcrumbs[] = array(
					'text' => $breadcrumb_title,
					'url'  => get_permalink( $post->ID ),
				);
			} elseif ( is_post_type_archive() ) {
				// Post type archive breadcrumb'ı
				$post_type = get_post_type();
				$post_type_obj = get_post_type_object( $post_type );
				
				$breadcrumbs[] = array(
					'text' => $post_type_obj->labels->name,
					'url'  => get_post_type_archive_link( $post_type ),
				);
			}
		}

		return apply_filters( 'wpsm_breadcrumbs', $breadcrumbs );
	}

	/**
	 * Breadcrumb shortcode callback
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes
	 * @return string Breadcrumb HTML
	 */
	public function breadcrumb_shortcode( $atts ) {
		$args = shortcode_atts( array(
			'separator' => '&raquo;',
			'home_text' => __( 'Ana Sayfa', 'wp-seo-master' ),
		), $atts );

		return $this->output_breadcrumbs( $args );
	}
}
