<?php
/**
 * OpenGraph Sınıfı
 * 
 * Frontend'de OpenGraph meta etiketlerini oluşturur.
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
 * OpenGraph Sınıfı
 * 
 * OpenGraph meta etiketlerini oluşturur.
 *
 * @since 1.0.0
 */
class OpenGraph {

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
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) && $this->options->get( 'enable_opengraph', true ) ) {
			add_action( 'wp_head', array( $this, 'output_opengraph_tags' ), 5 );
		}
	}

	/**
	 * OpenGraph etiketlerini output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_opengraph_tags() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		if ( ! $this->options->get( 'enable_opengraph', true ) ) {
			return;
		}

		// Temel OpenGraph etiketleri
		$og_tags = array();

		// locale
		$og_tags['og:locale'] = get_locale();
		
		// type
		if ( is_home() || is_front_page() ) {
			$og_tags['og:type'] = 'website';
		} elseif ( is_singular() ) {
			$post = get_queried_object();
			$custom_type = get_post_meta( $post->ID, '_wpsm_schema_type', true );
			
			if ( in_array( $custom_type, array( 'Article', 'BlogPosting', 'NewsArticle' ) ) ) {
				$og_tags['og:type'] = 'article';
			} elseif ( $custom_type === 'Product' && class_exists( 'WooCommerce' ) ) {
				$og_tags['og:type'] = 'product';
			} else {
				$og_tags['og:type'] = 'article';
			}
		} else {
			$og_tags['og:type'] = 'website';
		}
		
		// title
		$og_tags['og:title'] = $this->get_og_title();
		
		// description
		$og_tags['og:description'] = $this->get_og_description();
		
		// url
		$og_tags['og:url'] = $this->get_og_url();
		
		// site_name
		$og_tags['og:site_name'] = get_bloginfo( 'name' );
		
		// image
		$image_data = $this->get_og_image();
		if ( ! empty( $image_data['url'] ) ) {
			$og_tags['og:image'] = $image_data['url'];
			if ( ! empty( $image_data['width'] ) ) {
				$og_tags['og:image:width'] = $image_data['width'];
			}
			if ( ! empty( $image_data['height'] ) ) {
				$og_tags['og:image:height'] = $image_data['height'];
			}
			if ( ! empty( $image_data['alt'] ) ) {
				$og_tags['og:image:alt'] = $image_data['alt'];
			}
		}

		// article specific tags
		if ( is_singular() && $og_tags['og:type'] === 'article' ) {
			$post = get_queried_object();
			
			$og_tags['article:published_time'] = get_the_date( 'c', $post );
			$og_tags['article:modified_time'] = get_the_modified_date( 'c', $post );
			
			$author = get_userdata( $post->post_author );
			if ( $author ) {
				$og_tags['article:author'] = $author->display_name;
			}
			
			$category = get_the_category( $post->ID );
			if ( ! empty( $category[0] ) ) {
				$og_tags['article:section'] = $category[0]->name;
			}
			
			$tags = get_the_tags( $post->ID );
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					$og_tags['article:tag'] = $tag->name;
				}
			}
		}

		// product specific tags (if WooCommerce)
		if ( class_exists( 'WooCommerce' ) && is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( $product ) {
				$og_tags['product:price:amount'] = $product->get_price();
				$og_tags['product:price:currency'] = get_woocommerce_currency();
				$og_tags['product:availability'] = $product->is_in_stock() ? 'instock' : 'outofstock';
			}
		}

		// Output etiketleri
		foreach ( $og_tags as $property => $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $item ) {
					echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $item ) . '">' . "\n";
				}
			} else {
				echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
			}
		}
	}

	/**
	 * OpenGraph başlığını alır
	 *
	 * @since 1.0.0
	 * @return string OpenGraph başlığı
	 */
	private function get_og_title() {
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_title = get_post_meta( $post->ID, '_wpsm_og_title', true );
			
			if ( ! empty( $custom_title ) ) {
				return $custom_title;
			}
		}
		
		// Varsayılan başlık
		return wp_get_document_title();
	}

	/**
	 * OpenGraph açıklamasını alır
	 *
	 * @since 1.0.0
	 * @return string OpenGraph açıklaması
	 */
	private function get_og_description() {
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_desc = get_post_meta( $post->ID, '_wpsm_og_description', true );
			
			if ( ! empty( $custom_desc ) ) {
				return $custom_desc;
			}
		}
		
		// Varsayılan açıklama
		$desc = get_bloginfo( 'description' );
		if ( empty( $desc ) && is_singular() ) {
			$post = get_queried_object();
			$desc = wp_trim_words( $post->post_content, 25, '...' );
		}
		
		return $desc;
	}

	/**
	 * OpenGraph URL'sini alır
	 *
	 * @since 1.0.0
	 * @return string OpenGraph URL
	 */
	private function get_og_url() {
		return get_permalink();
	}

	/**
	 * OpenGraph görselini alır
	 *
	 * @since 1.0.0
	 * @return array Görsel bilgileri (url, width, height, alt)
	 */
	private function get_og_image() {
		$image_data = array();
		
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_image = get_post_meta( $post->ID, '_wpsm_og_image', true );
			
			if ( ! empty( $custom_image ) ) {
				$image_data['url'] = $custom_image;
				$image_data['alt'] = get_the_title( $post->ID );
			} else {
				// Featured image kontrolü
				if ( has_post_thumbnail( $post->ID ) ) {
					$thumb_id = get_post_thumbnail_id( $post->ID );
					$image = wp_get_attachment_image_src( $thumb_id, 'full' );
					
					if ( $image ) {
						$image_data['url'] = $image[0];
						$image_data['width'] = $image[1];
						$image_data['height'] = $image[2];
						$image_data['alt'] = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
					}
				}
			}
		}
		
		// Hala yoksa varsayılanı kullan
		if ( empty( $image_data['url'] ) ) {
			$default_image = $this->options->get( 'default_og_image' );
			if ( ! empty( $default_image ) ) {
				$image_data['url'] = $default_image;
			}
		}
		
		return $image_data;
	}
}
