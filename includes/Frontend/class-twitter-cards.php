<?php
/**
 * Twitter Cards Sınıfı
 * 
 * Frontend'de Twitter Cards meta etiketlerini oluşturur.
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
 * Twitter Cards Sınıfı
 * 
 * Twitter Cards meta etiketlerini oluşturur.
 *
 * @since 1.0.0
 */
class Twitter_Cards {

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
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) && $this->options->get( 'enable_twitter', true ) ) {
			add_action( 'wp_head', array( $this, 'output_twitter_cards_tags' ), 6 );
		}
	}

	/**
	 * Twitter Cards etiketlerini output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_twitter_cards_tags() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		if ( ! $this->options->get( 'enable_twitter', true ) ) {
			return;
		}

		$twitter_tags = array();

		// card type
		$card_type = $this->options->get( 'twitter_card_type', 'summary' );
		$twitter_tags['twitter:card'] = $card_type;

		// site
		$twitter_site = $this->options->get( 'twitter_site' );
		if ( ! empty( $twitter_site ) ) {
			$twitter_tags['twitter:site'] = '@' . ltrim( $twitter_site, '@' );
		}

		// creator (for articles)
		if ( is_singular() ) {
			$post = get_queried_object();
			$author_twitter = get_the_author_meta( 'twitter', $post->post_author );
			if ( ! empty( $author_twitter ) ) {
				$twitter_tags['twitter:creator'] = '@' . ltrim( $author_twitter, '@' );
			}
		}

		// title
		$twitter_tags['twitter:title'] = $this->get_twitter_title();

		// description
		$twitter_tags['twitter:description'] = $this->get_twitter_description();

		// image
		$image_data = $this->get_twitter_image();
		if ( ! empty( $image_data['url'] ) ) {
			$twitter_tags['twitter:image'] = $image_data['url'];
			if ( ! empty( $image_data['alt'] ) ) {
				$twitter_tags['twitter:image:alt'] = $image_data['alt'];
			}
		}

		// Output etiketleri
		foreach ( $twitter_tags as $name => $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $item ) {
					echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $item ) . '">' . "\n";
				}
			} else {
				echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $content ) . '">' . "\n";
			}
		}
	}

	/**
	 * Twitter başlığını alır
	 *
	 * @since 1.0.0
	 * @return string Twitter başlığı
	 */
	private function get_twitter_title() {
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_title = get_post_meta( $post->ID, '_wpsm_twitter_title', true );
			
			if ( ! empty( $custom_title ) ) {
				return $custom_title;
			}
		}
		
		// Varsayılan başlık
		return wp_get_document_title();
	}

	/**
	 * Twitter açıklamasını alır
	 *
	 * @since 1.0.0
	 * @return string Twitter açıklaması
	 */
	private function get_twitter_description() {
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_desc = get_post_meta( $post->ID, '_wpsm_twitter_description', true );
			
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
	 * Twitter görselini alır
	 *
	 * @since 1.0.0
	 * @return array Görsel bilgileri (url, alt)
	 */
	private function get_twitter_image() {
		$image_data = array();
		
		if ( is_singular() ) {
			$post = get_queried_object();
			$custom_image = get_post_meta( $post->ID, '_wpsm_twitter_image', true );
			
			if ( ! empty( $custom_image ) ) {
				$image_data['url'] = $custom_image;
				$image_data['alt'] = get_the_title( $post->ID );
			} else {
				// Featured image kontrolü
				if ( has_post_thumbnail( $post->ID ) ) {
					$thumb_id = get_post_thumbnail_id( $post->ID );
					$image = wp_get_attachment_image_src( $thumb_id, 'large' );
					
					if ( $image ) {
						$image_data['url'] = $image[0];
						$image_data['alt'] = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
					}
				}
			}
		}
		
		// Hala yoksa varsayılanı kullan
		if ( empty( $image_data['url'] ) ) {
			$default_image = $this->options->get( 'default_og_image' ); // Aynı resmi kullan
			if ( ! empty( $default_image ) ) {
				$image_data['url'] = $default_image;
			}
		}
		
		return $image_data;
	}
}
