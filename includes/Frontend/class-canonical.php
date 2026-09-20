<?php
/**
 * Canonical Sınıfı
 * 
 * Frontend'de canonical URL etiketlerini oluşturur.
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
 * Canonical Sınıfı
 * 
 * Canonical URL etiketlerini oluşturur.
 *
 * @since 1.0.0
 */
class Canonical {

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
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			add_action( 'wp_head', array( $this, 'output_canonical_tag' ), 2 );
		}
	}

	/**
	 * Canonical etiketini output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_canonical_tag() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		// Search ve 404 sayfalarında canonical output etme
		if ( is_search() || is_404() ) {
			return;
		}

		$canonical_url = $this->get_canonical_url();

		if ( ! empty( $canonical_url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '">' . "\n";
		}
	}

	/**
	 * Canonical URL'yi alır
	 *
	 * @since 1.0.0
	 * @return string Canonical URL
	 */
	public function get_canonical_url() {
		$canonical_url = '';

		if ( is_singular() ) {
			// Tekil içerik için custom canonical kontrolü
			$post = get_queried_object();
			$custom_canonical = get_post_meta( $post->ID, '_wpsm_canonical', true );
			
			if ( ! empty( $custom_canonical ) ) {
				$canonical_url = $custom_canonical;
			} else {
				$canonical_url = get_permalink( $post->ID );
			}
		} elseif ( is_home() ) {
			$canonical_url = home_url( '/' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$canonical_url = get_term_link( get_queried_object() );
		} elseif ( is_author() ) {
			$canonical_url = get_author_posts_url( get_queried_object_id() );
		} elseif ( is_date() ) {
			$canonical_url = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
		} elseif ( is_post_type_archive() ) {
			$canonical_url = get_post_type_archive_link( get_post_type() );
		} else {
			// Varsayılan olarak mevcut URL
			$canonical_url = $this->get_current_url();
		}

		return $canonical_url;
	}

	/**
	 * Mevcut sayfa URL'sini alır
	 *
	 * @since 1.0.0
	 * @return string Mevcut URL
	 */
	private function get_current_url() {
		$protocol = is_ssl() ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'] ?? '';
		$path = $_SERVER['REQUEST_URI'] ?? '';

		return esc_url_raw( $protocol . $host . $path );
	}
}
