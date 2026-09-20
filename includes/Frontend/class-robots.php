<?php
/**
 * Robots Sınıfı
 * 
 * Frontend'de robots meta etiketlerini ve robots.txt dosyasını yönetir.
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
 * Robots Sınıfı
 * 
 * Robots meta etiketlerini ve robots.txt dosyasını yönetir.
 *
 * @since 1.0.0
 */
class Robots {

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
			// WP 5.7+ için wp_robots filtresi
			if ( function_exists( 'wp_robots' ) ) {
				add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
			} else {
				// Eski sürümler için
				add_action( 'wp_head', array( $this, 'output_robots_meta' ), 1 );
			}
			
			// robots.txt filtresi
			add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
		}
	}

	/**
	 * Robots meta etiketini filtreler (WP 5.7+)
	 *
	 * @since 1.0.0
	 * @param array $robots Robots array
	 * @return array Filtrelenmiş robots array
	 */
	public function filter_robots( $robots ) {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return $robots;
		}

		// Otomatik noindex kuralları
		if ( is_search() || is_404() || is_date() ) {
			$robots['noindex'] = true;
		}

		// Post meta ile override
		if ( is_singular() ) {
			$post = get_queried_object();
			$robots_meta = get_post_meta( $post->ID, '_wpsm_robots', true );
			
			if ( is_array( $robots_meta ) ) {
				foreach ( $robots_meta as $rule ) {
					switch ( $rule ) {
						case 'noindex':
							$robots['noindex'] = true;
							break;
						case 'nofollow':
							$robots['nofollow'] = true;
							break;
						case 'noarchive':
							$robots['noarchive'] = true;
							break;
					}
				}
			}
		}

		return $robots;
	}

	/**
	 * Robots meta etiketini output eder (eski WP sürümleri için)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_robots_meta() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		$rules = array();

		// Otomatik noindex kuralları
		if ( is_search() || is_404() || is_date() ) {
			$rules[] = 'noindex';
			$rules[] = 'nofollow';
		}

		// Post meta ile override
		if ( is_singular() ) {
			$post = get_queried_object();
			$robots_meta = get_post_meta( $post->ID, '_wpsm_robots', true );
			
			if ( is_array( $robots_meta ) ) {
				$rules = array_merge( $rules, $robots_meta );
			}
		}

		if ( ! empty( $rules ) ) {
			$content = implode( ', ', $rules );
			echo '<meta name="robots" content="' . esc_attr( $content ) . '">' . "\n";
		}
	}

	/**
	 * robots.txt dosyasını filtreler
	 *
	 * @since 1.0.0
	 * @param string $output Mevcut robots.txt çıktısı
	 * @param bool   $public Sitetipi (public/private)
	 * @return string Filtrelenmiş robots.txt çıktısı
	 */
	public function filter_robots_txt( $output, $public ) {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return $output;
		}

		if ( ! $public ) {
			return "User-agent: *\nDisallow: /\n";
		}

		// Mevcut output sonuna ekle
		$output .= "\n";

		// Sitemap ekle
		if ( $this->options->get( 'enable_sitemap', true ) ) {
			$output .= "Sitemap: " . esc_url( home_url( '/sitemap_index.xml' ) ) . "\n";
		}

		// Custom robots.txt kuralları ekle
		$custom_rules = $this->options->get( 'robots_txt_custom' );
		if ( ! empty( $custom_rules ) ) {
			$output .= $custom_rules . "\n";
		}

		return $output;
	}
}
