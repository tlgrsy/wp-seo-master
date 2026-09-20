<?php
/**
 * Meta Tags Sınıfı
 * 
 * Frontend'de SEO meta etiketlerini oluşturur.
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
 * Meta Tags Sınıfı
 * 
 * Title, description ve diğer meta etiketlerini oluşturur.
 *
 * @since 1.0.0
 */
class Meta_Tags {

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
			add_filter( 'pre_get_document_title', array( $this, 'get_title' ), 99 );
			add_action( 'wp_head', array( $this, 'output_meta_description' ), 1 );
			add_action( 'wp_head', array( $this, 'output_gsc_verification' ), 1 );
			add_action( 'wp_head', array( $this, 'output_bing_verification' ), 1 );
		}
	}

	/**
	 * Sayfa başlığını oluşturur
	 *
	 * @since 1.0.0
	 * @param string $title Mevcut başlık
	 * @return string Yeni başlık
	 */
	public function get_title( $title ) {
		if ( is_admin() ) {
			return $title;
		}

		$new_title = '';

		if ( is_home() || is_front_page() ) {
			// Anasayfa
			$home_title = $this->options->get( 'home_title' );
			if ( ! empty( $home_title ) ) {
				$new_title = $home_title;
			} else {
				$new_title = get_bloginfo( 'name' );
			}
		} elseif ( is_singular() ) {
			// Tekil içerik
			$post = get_queried_object();
			$custom_title = get_post_meta( $post->ID, '_wpsm_title', true );
			
			if ( ! empty( $custom_title ) ) {
				$new_title = $custom_title;
			} else {
				$new_title = $this->build_title_template( $post->post_title );
			}
		} elseif ( is_category() ) {
			$category = get_queried_object();
			$new_title = $this->build_title_template( single_cat_title( '', false ), 'category' );
		} elseif ( is_tag() ) {
			$tag = get_queried_object();
			$new_title = $this->build_title_template( single_tag_title( '', false ), 'tag' );
		} elseif ( is_author() ) {
			$author = get_queried_object();
			$new_title = $this->build_title_template( $author->display_name, 'author' );
		} elseif ( is_search() ) {
			$search_query = get_search_query();
			$new_title = $this->build_title_template( sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query ), 'search' );
		} elseif ( is_archive() ) {
			$new_title = $this->build_title_template( get_the_archive_title(), 'archive' );
		} else {
			// Varsayılan
			$new_title = $this->build_title_template( $title );
		}

		return $new_title;
	}

	/**
	 * Başlık şablonunu oluşturur
	 *
	 * @since 1.0.0
	 * @param string $content İçerik
	 * @param string $context Bağlam (home, singular, category vs.)
	 * @return string Oluşturulan başlık
	 */
	private function build_title_template( $content, $context = 'default' ) {
		$separator = $this->options->get( 'title_separator', '|' );
		$site_name = get_bloginfo( 'name' );
		$page_num = '';

		// Sayfa numarası varsa ekle
		if ( $paged = get_query_var( 'paged' ) ) {
			$page_num = ' ' . $separator . ' ' . sprintf( __( 'Sayfa %d', 'wp-seo-master' ), $paged );
		}

		// Şablon: %%title%% %%sep%% %%sitename%% %%page%%
		$title = $content . ' ' . $separator . ' ' . $site_name . $page_num;

		// Değişkenleri değiştir
		$title = str_replace( '%%title%%', $content, $title );
		$title = str_replace( '%%sitename%%', $site_name, $title );
		$title = str_replace( '%%sep%%', $separator, $title );
		$title = str_replace( '%%page%%', $page_num, $title );
		$title = str_replace( '%%currentyear%%', gmdate( 'Y' ), $title );

		if ( 'category' === $context ) {
			$title = str_replace( '%%category%%', $content, $title );
		} elseif ( 'tag' === $context ) {
			$title = str_replace( '%%tag%%', $content, $title );
		} elseif ( 'search' === $context ) {
			$title = str_replace( '%%search_query%%', $content, $title );
		}

		return trim( $title );
	}

	/**
	 * Meta description etiketini output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_meta_description() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		$description = '';

		if ( is_home() || is_front_page() ) {
			$home_desc = $this->options->get( 'home_description' );
			if ( ! empty( $home_desc ) ) {
				$description = $home_desc;
			} else {
				$description = get_bloginfo( 'description' );
			}
		} elseif ( is_singular() ) {
			$post = get_queried_object();
			$custom_desc = get_post_meta( $post->ID, '_wpsm_description', true );
			
			if ( ! empty( $custom_desc ) ) {
				$description = $custom_desc;
			} else {
				//_excerpt veya otomatik açıklama oluştur
				$content = $post->post_content;
				if ( empty( $content ) ) {
					$content = $post->post_excerpt;
				}
				
				if ( empty( $content ) ) {
					$content = $post->post_title;
				}
				
				$description = wp_trim_words( $content, 25, '...' );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$description = term_description();
			if ( empty( $description ) ) {
				$description = $term->name;
			}
		}

		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $description ) ) . '">' . "\n";
		}
	}

	/**
	 * Google Site Verification kodunu output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_gsc_verification() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		$code = $this->options->get( 'gsc_verification' );
		if ( ! empty( $code ) ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $code ) . '">' . "\n";
		}
	}

	/**
	 * Bing Site Verification kodunu output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_bing_verification() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		$code = $this->options->get( 'bing_verification' );
		if ( ! empty( $code ) ) {
			echo '<meta name="msvalidate.01" content="' . esc_attr( $code ) . '">' . "\n";
		}
	}
}
