<?php
/**
 * Content Analyzer Sınıfı
 * 
 * İçerik analizi yapar ve SEO önerileri sunar.
 *
 * @package WPSM\Analyzer
 * @since 1.0.0
 */

namespace WPSM\Analyzer;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Analyzer Sınıfı
 * 
 * İçerik analizi yapar ve SEO önerileri sunar.
 *
 * @since 1.0.0
 */
class Content_Analyzer {

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
	}

	/**
	 * İçerik analizi yapar
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID
	 * @param string $focus_keyword Odak kelimesi (opsiyonel, yoksa post meta'dan al)
	 * @return array Analiz sonuçları
	 */
	public function analyze( $post_id, $focus_keyword = null ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'score' => 0,
				'checks' => array(
					array(
						'check' => 'invalid_post',
						'passed' => false,
						'message' => __( 'Geçersiz post ID.', 'wp-seo-master' ),
					),
				),
			);
		}

		// Odak kelimesi yoksa post meta'dan al
		if ( ! $focus_keyword ) {
			$focus_keyword = get_post_meta( $post_id, '_wpsm_focus_keyword', true );
		}

		if ( ! $focus_keyword ) {
			return array(
				'score' => 0,
				'checks' => array(
					array(
						'check' => 'missing_focus_keyword',
						'passed' => false,
						'message' => __( 'Odak kelimesi belirlenmemiş.', 'wp-seo-master' ),
					),
				),
			);
		}

		// Kontrolleri yap
		$checks = array();

		// 1. Başlıkta odak kelimesi geçiyor mu?
		$checks[] = $this->check_title_contains_keyword( $post, $focus_keyword );

		// 2. Açıklamada odak kelimesi geçiyor mu?
		$checks[] = $this->check_description_contains_keyword( $post, $focus_keyword );

		// 3. URL'de odak kelimesi geçiyor mu?
		$checks[] = $this->check_slug_contains_keyword( $post, $focus_keyword );

		// 4. İçerik uzunluğu yeterli mi?
		$checks[] = $this->check_content_length( $post );

		// 5. Odak kelimesi yoğunluğu uygun mu?
		$checks[] = $this->check_keyword_density( $post, $focus_keyword );

		// 6. İçerikte H2/H3 başlıkları var mı?
		$checks[] = $this->check_heading_structure( $post );

		// 7. İçerikte görsel var mı?
		$checks[] = $this->check_has_images( $post );

		// 8. İçerikte dış bağlantı var mı?
		$checks[] = $this->check_has_external_links( $post );

		// 9. İçerikte iç bağlantı var mı?
		$checks[] = $this->check_has_internal_links( $post );

		// 10. Açıklama dolu mu?
		$checks[] = $this->check_has_description( $post );

		// Toplam skoru hesapla
		$total_checks = count( $checks );
		$passed_checks = 0;
		foreach ( $checks as $check ) {
			if ( $check['passed'] ) {
				$passed_checks++;
			}
		}

		$score = $total_checks > 0 ? round( ( $passed_checks / $total_checks ) * 100 ) : 0;

		return array(
			'score' => $score,
			'checks' => $checks,
		);
	}

	/**
	 * Başlıkta odak kelimesi geçiyor mu kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @param string   $keyword Odak kelimesi
	 * @return array Check sonucu
	 */
	private function check_title_contains_keyword( $post, $keyword ) {
		$title = $post->post_title;
		$contains = stripos( $title, $keyword ) !== false;

		return array(
			'check' => 'title_contains_keyword',
			'passed' => $contains,
			'message' => $contains 
				? __( 'Başlıkta odak kelimesi bulunuyor.', 'wp-seo-master' ) 
				: __( 'Başlıkta odak kelimesi bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * Açıklamada odak kelimesi geçiyor mu kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @param string   $keyword Odak kelimesi
	 * @return array Check sonucu
	 */
	private function check_description_contains_keyword( $post, $keyword ) {
		$description = get_post_meta( $post->ID, '_wpsm_description', true );
		if ( empty( $description ) ) {
			$description = wp_trim_words( $post->post_content, 25, '...' );
		}
		
		$contains = stripos( $description, $keyword ) !== false;

		return array(
			'check' => 'description_contains_keyword',
			'passed' => $contains,
			'message' => $contains 
				? __( 'Açıklamada odak kelimesi bulunuyor.', 'wp-seo-master' ) 
				: __( 'Açıklamada odak kelimesi bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * URL'de odak kelimesi geçiyor mu kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @param string   $keyword Odak kelimesi
	 * @return array Check sonucu
	 */
	private function check_slug_contains_keyword( $post, $keyword ) {
		$slug = $post->post_name;
		$words = explode( ' ', $keyword );
		$found = false;
		
		foreach ( $words as $word ) {
			if ( stripos( $slug, $word ) !== false ) {
				$found = true;
				break;
			}
		}

		return array(
			'check' => 'slug_contains_keyword',
			'passed' => $found,
			'message' => $found 
				? __( 'URL\'de odak kelimesi bulunuyor.', 'wp-seo-master' ) 
				: __( 'URL\'de odak kelimesi bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * İçerik uzunluğu kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_content_length( $post ) {
		$content = $post->post_content;
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		$min_length = 300;
		$has_enough_length = $word_count >= $min_length;

		return array(
			'check' => 'content_length',
			'passed' => $has_enough_length,
			'message' => $has_enough_length 
				? sprintf( __( 'İçerik yeterli uzunlukta (%d kelime).', 'wp-seo-master' ), $word_count )
				: sprintf( __( 'İçerik çok kısa (%d kelime), en az %d kelime olmalı.', 'wp-seo-master' ), $word_count, $min_length ),
		);
	}

	/**
	 * Odak kelimesi yoğunluğu kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @param string   $keyword Odak kelimesi
	 * @return array Check sonucu
	 */
	private function check_keyword_density( $post, $keyword ) {
		$content = $post->post_content;
		$content_text = wp_strip_all_tags( $content );
		$content_words = str_word_count( $content_text );
		$keyword_count = 0;

		// Kelime sayısını bul
		$pattern = '/\b' . preg_quote( $keyword, '/' ) . '\b/i';
		preg_match_all( $pattern, $content_text, $matches );
		$keyword_count = count( $matches[0] );

		$density = $content_words > 0 ? ( $keyword_count / $content_words ) * 100 : 0;
		$is_optimal = $density >= 0.5 && $density <= 2.5;

		return array(
			'check' => 'keyword_density',
			'passed' => $is_optimal,
			'message' => $is_optimal 
				? sprintf( __( 'Odak kelimesi yoğunluğu ideal (%.2f%%).', 'wp-seo-master' ), $density )
				: sprintf( __( 'Odak kelimesi yoğunluğu uygun değil (%.2f%%), ideal aralık: 0.5%% - 2.5%%.', 'wp-seo-master' ), $density ),
		);
	}

	/**
	 * Heading structure kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_heading_structure( $post ) {
		$content = $post->post_content;
		$has_headings = (bool) preg_match( '/<h[2-6].*?>/i', $content );

		return array(
			'check' => 'heading_structure',
			'passed' => $has_headings,
			'message' => $has_headings 
				? __( 'İçerikte heading (H2-H6) başlıkları bulunuyor.', 'wp-seo-master' )
				: __( 'İçerikte heading (H2-H6) başlıkları bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * Görsel kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_has_images( $post ) {
		$content = $post->post_content;
		$has_images = (bool) preg_match( '/<img.*?>/i', $content );

		return array(
			'check' => 'has_images',
			'passed' => $has_images,
			'message' => $has_images 
				? __( 'İçerikte görseller bulunuyor.', 'wp-seo-master' )
				: __( 'İçerikte görseller bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * Dış bağlantı kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_has_external_links( $post ) {
		$content = $post->post_content;
		$site_url = home_url();
		$pattern = '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i';
		
		preg_match_all( $pattern, $content, $matches );
		$external_links = 0;
		
		if ( isset( $matches[1] ) ) {
			foreach ( $matches[1] as $link ) {
				if ( ! str_starts_with( $link, $site_url ) && str_starts_with( $link, 'http' ) ) {
					$external_links++;
				}
			}
		}

		return array(
			'check' => 'has_external_links',
			'passed' => $external_links > 0,
			'message' => $external_links > 0 
				? sprintf( _n( 'İçerikte %d dış bağlantı bulunuyor.', 'İçerikte %d dış bağlantı bulunuyor.', $external_links, 'wp-seo-master' ), $external_links )
				: __( 'İçerikte dış bağlantı bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * İç bağlantı kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_has_internal_links( $post ) {
		$content = $post->post_content;
		$site_url = home_url();
		$pattern = '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i';
		
		preg_match_all( $pattern, $content, $matches );
		$internal_links = 0;
		
		if ( isset( $matches[1] ) ) {
			foreach ( $matches[1] as $link ) {
				if ( str_starts_with( $link, $site_url ) ) {
					$internal_links++;
				}
			}
		}

		return array(
			'check' => 'has_internal_links',
			'passed' => $internal_links > 0,
			'message' => $internal_links > 0 
				? sprintf( _n( 'İçerikte %d iç bağlantı bulunuyor.', 'İçerikte %d iç bağlantı bulunuyor.', $internal_links, 'wp-seo-master' ), $internal_links )
				: __( 'İçerikte iç bağlantı bulunmuyor.', 'wp-seo-master' ),
		);
	}

	/**
	 * Açıklama kontrolü
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @return array Check sonucu
	 */
	private function check_has_description( $post ) {
		$description = get_post_meta( $post->ID, '_wpsm_description', true );
		if ( empty( $description ) ) {
			$description = wp_trim_words( $post->post_content, 25, '...' );
		}
		
		$has_description = ! empty( $description );

		return array(
			'check' => 'has_description',
			'passed' => $has_description,
			'message' => $has_description 
				? __( 'Meta açıklaması tanımlanmış.', 'wp-seo-master' )
				: __( 'Meta açıklaması tanımlanmamış.', 'wp-seo-master' ),
		);
	}
}
