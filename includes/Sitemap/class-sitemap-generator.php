<?php
/**
 * Sitemap Generator Sınıfı
 * 
 * XML sitemap dosyalarını oluşturur.
 *
 * @package WPSM\Sitemap
 * @since 1.0.0
 */

namespace WPSM\Sitemap;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Generator Sınıfı
 * 
 * XML sitemap dosyalarını oluşturur.
 *
 * @since 1.0.0
 */
class Sitemap_Generator {

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
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) && $this->options->get( 'enable_sitemap', true ) ) {
			add_action( 'init', array( $this, 'add_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ) );
		}
	}

	/**
	 * Rewrite kurallarını ekle
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rewrite_rules() {
		// Ana sitemap
		add_rewrite_rule(
			'^sitemap_index\.xml$',
			'index.php?wpsm_sitemap=index',
			'top'
		);

		// Post type sitemap'leri
		$post_types = $this->get_enabled_post_types();
		foreach ( $post_types as $post_type ) {
			add_rewrite_rule(
				'^' . $post_type . '-sitemap\.xml$',
				'index.php?wpsm_sitemap=' . $post_type,
				'top'
			);
		}

		// Taxonomy sitemap'leri
		$taxonomies = $this->get_enabled_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			add_rewrite_rule(
				'^' . $taxonomy . '-sitemap\.xml$',
				'index.php?wpsm_sitemap=' . $taxonomy,
				'top'
			);
		}
	}

	/**
	 * Query variable ekle
	 *
	 * @since 1.0.0
	 * @param array $vars Mevcut query variables
	 * @return array Yeni query variables
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wpsm_sitemap';
		return $vars;
	}

	/**
	 * Sitemap isteğini handle et
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_sitemap_request() {
		$sitemap_type = get_query_var( 'wpsm_sitemap' );

		if ( empty( $sitemap_type ) ) {
			return;
		}

		// Cache kontrolü
		$cache_key = 'wpsm_sitemap_' . $sitemap_type;
		$cache_duration = $this->options->get( 'sitemap_cache_hours', 12 ) * HOUR_IN_SECONDS;
		$cached_sitemap = get_transient( $cache_key );

		if ( $cached_sitemap ) {
			$this->output_sitemap( $cached_sitemap );
			return;
		}

		// Sitemap oluştur
		$sitemap_content = $this->generate_sitemap( $sitemap_type );

		if ( $sitemap_content ) {
			// Cache'e yaz
			set_transient( $cache_key, $sitemap_content, $cache_duration );
			
			// Output et
			$this->output_sitemap( $sitemap_content );
		} else {
			// Geçersiz sitemap tipi
			status_header( 404 );
			nocache_headers();
			die( 'Sitemap not found.' );
		}
	}

	/**
	 * Sitemap oluşturur
	 *
	 * @since 1.0.0
	 * @param string $type Sitemap tipi
	 * @return string|false Sitemap içeriği veya false
	 */
	private function generate_sitemap( $type ) {
		switch ( $type ) {
			case 'index':
				return $this->generate_index_sitemap();
			case 'post':
			case 'page':
				return $this->generate_post_sitemap( $type );
			default:
				// Taksonomi mi diye kontrol et
				$taxonomies = $this->get_enabled_taxonomies();
				if ( in_array( $type, $taxonomies ) ) {
					return $this->generate_taxonomy_sitemap( $type );
				}

				// Post type mı diye kontrol et
				$post_types = $this->get_enabled_post_types();
				if ( in_array( $type, $post_types ) ) {
					return $this->generate_post_sitemap( $type );
				}

				return false;
		}
	}

	/**
	 * Ana sitemap (sitemap_index.xml) oluşturur
	 *
	 * @since 1.0.0
	 * @return string Sitemap içeriği
	 */
	private function generate_index_sitemap() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Post type sitemap'leri
		$post_types = $this->get_enabled_post_types();
		foreach ( $post_types as $post_type ) {
			$xml .= '<sitemap>' . "\n";
			$xml .= '  <loc>' . esc_url( home_url( '/' . $post_type . '-sitemap.xml' ) ) . '</loc>' . "\n";
			$xml .= '  <lastmod>' . gmdate( 'c', current_time( 'timestamp' ) ) . '</lastmod>' . "\n";
			$xml .= '</sitemap>' . "\n";
		}

		// Taxonomy sitemap'leri
		$taxonomies = $this->get_enabled_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			$xml .= '<sitemap>' . "\n";
			$xml .= '  <loc>' . esc_url( home_url( '/' . $taxonomy . '-sitemap.xml' ) ) . '</loc>' . "\n";
			$xml .= '  <lastmod>' . gmdate( 'c', current_time( 'timestamp' ) ) . '</lastmod>' . "\n";
			$xml .= '</sitemap>' . "\n";
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}

	/**
	 * Post sitemap oluşturur
	 *
	 * @since 1.0.0
	 * @param string $post_type Post type
	 * @return string Sitemap içeriği
	 */
	private function generate_post_sitemap( $post_type ) {
		$query_args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => 1000, // Max 1000 per sitemap
			'orderby' => 'modified',
			'order' => 'DESC',
		);

		// WooCommerce product için özel sorgu
		if ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
			$query_args['meta_query'] = array(
				array(
					'key' => '_visibility',
					'value' => array( 'visible', 'catalog' ),
					'compare' => 'IN',
				),
			);
		}

		$posts = get_posts( $query_args );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $posts as $post ) {
			// Noindex kontrolü
			$robots_meta = get_post_meta( $post->ID, '_wpsm_robots', true );
			if ( is_array( $robots_meta ) && in_array( 'noindex', $robots_meta ) ) {
				continue;
			}

			$xml .= '<url>' . "\n";
			$xml .= '  <loc>' . esc_url( get_permalink( $post->ID ) ) . '</loc>' . "\n";
			$xml .= '  <lastmod>' . mysql2date( 'c', $post->post_modified_gmt, false ) . '</lastmod>' . "\n";
			$xml .= '  <changefreq>weekly</changefreq>' . "\n";
			$xml .= '  <priority>0.8</priority>' . "\n";
			$xml .= '</url>' . "\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Taxonomy sitemap oluşturur
	 *
	 * @since 1.0.0
	 * @param string $taxonomy Taksonomi
	 * @return string Sitemap içeriği
	 */
	private function generate_taxonomy_sitemap( $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => true,
			'number' => 1000, // Max 1000 per sitemap
		) );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $terms as $term ) {
			$xml .= '<url>' . "\n";
			$xml .= '  <loc>' . esc_url( get_term_link( $term ) ) . '</loc>' . "\n";
			$xml .= '  <lastmod>' . gmdate( 'c', current_time( 'timestamp' ) ) . '</lastmod>' . "\n";
			$xml .= '  <changefreq>weekly</changefreq>' . "\n";
			$xml .= '  <priority>0.6</priority>' . "\n";
			$xml .= '</url>' . "\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Sitemap output eder
	 *
	 * @since 1.0.0
	 * @param string $content Sitemap içeriği
	 * @return void
	 */
	private function output_sitemap( $content ) {
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		nocache_headers();

		echo $content;
		exit;
	}

	/**
	 * Enabled post typeları alır
	 *
	 * @since 1.0.0
	 * @return array Enabled post typelar
	 */
	private function get_enabled_post_types() {
		$enabled = array();
		$all_post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $all_post_types['attachment'] ); // Attachment hariç

		foreach ( $all_post_types as $post_type ) {
			$option_key = 'wpsm_sitemap_post_types_' . $post_type;
			$is_enabled = $this->options->get( $option_key, true );
			if ( $is_enabled ) {
				$enabled[] = $post_type;
			}
		}

		return $enabled;
	}

	/**
	 * Enabled taksonomileri alır
	 *
	 * @since 1.0.0
	 * @return array Enabled taksonomiler
	 */
	private function get_enabled_taxonomies() {
		$enabled = array();
		$all_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

		foreach ( $all_taxonomies as $taxonomy ) {
			$option_key = 'wpsm_sitemap_taxonomies_' . $taxonomy;
			$is_enabled = $this->options->get( $option_key, true );
			if ( $is_enabled ) {
				$enabled[] = $taxonomy;
			}
		}

		return $enabled;
	}
}
