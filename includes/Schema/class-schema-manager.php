<?php
/**
 * Schema Manager Sınıfı
 * 
 * Frontend'de Schema.org JSON-LD etiketlerini oluşturur.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Manager Sınıfı
 * 
 * Schema.org JSON-LD etiketlerini yönetir.
 *
 * @since 1.0.0
 */
class Schema_Manager {

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
		if ( apply_filters( 'wpsm_enable_frontend_output', true ) && $this->options->get( 'enable_schema', true ) ) {
			add_action( 'wp_head', array( $this, 'output_schema_json' ), 10 );
		}
	}

	/**
	 * Schema JSON-LD etiketlerini output eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_schema_json() {
		if ( ! apply_filters( 'wpsm_enable_frontend_output', true ) ) {
			return;
		}

		if ( ! $this->options->get( 'enable_schema', true ) ) {
			return;
		}

		$schema_data = array();

		// Website schema (tüm sayfalarda)
		$website_schema = $this->get_website_schema();
		if ( $website_schema ) {
			$schema_data[] = $website_schema;
		}

		// Organization schema (tüm sayfalarda)
		$organization_schema = $this->get_organization_schema();
		if ( $organization_schema ) {
			$schema_data[] = $organization_schema;
		}

		// Singular içerikler için özel schema
		if ( is_singular() ) {
			$singular_schema = $this->get_singular_schema();
			if ( $singular_schema ) {
				$schema_data[] = $singular_schema;
			}
		}

		// Breadcrumb schema (eğer varsa)
		if ( $this->options->get( 'enable_breadcrumbs', true ) ) {
			$breadcrumb_schema = $this->get_breadcrumb_schema();
			if ( $breadcrumb_schema ) {
				$schema_data[] = $breadcrumb_schema;
			}
		}

		if ( ! empty( $schema_data ) ) {
			echo '<script type="application/ld+json">' . "\n";
			echo wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			echo "\n" . '</script>' . "\n";
		}
	}

	/**
	 * Website schema oluşturur
	 *
	 * @since 1.0.0
	 * @return array Website schema
	 */
	public function get_website_schema() {
		$website_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'@id' => home_url( '/' ) . '#website',
			'url' => home_url( '/' ),
			'name' => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'potentialAction' => array(
				'@type' => 'SearchAction',
				'target' => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);

		// Search action ayarı kontrolü
		if ( ! $this->options->get( 'enable_search_action', true ) ) {
			unset( $website_schema['potentialAction'] );
		}

		return $website_schema;
	}

	/**
	 * Organization schema oluşturur
	 *
	 * @since 1.0.0
	 * @return array Organization schema
	 */
	public function get_organization_schema() {
		$org_name = $this->options->get( 'org_name', get_bloginfo( 'name' ) );
		$org_logo = $this->options->get( 'org_logo' );

		if ( empty( $org_name ) ) {
			return null;
		}

		$organization_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'@id' => home_url( '/' ) . '#organization',
			'name' => $org_name,
			'url' => home_url( '/' ),
		);

		if ( ! empty( $org_logo ) ) {
			$organization_schema['logo'] = array(
				'@type' => 'ImageObject',
				'url' => $org_logo,
				'width' => 200,
				'height' => 200,
			);
		}

		// Sosyal medya bağlantıları
		$social_links = array();
		$facebook = $this->options->get( 'org_facebook' );
		$twitter = $this->options->get( 'org_twitter' );
		$instagram = $this->options->get( 'org_instagram' );

		if ( ! empty( $facebook ) ) {
			$social_links[] = $facebook;
		}

		if ( ! empty( $twitter ) ) {
			$social_links[] = 'https://twitter.com/' . ltrim( $twitter, '@' );
		}

		if ( ! empty( $instagram ) ) {
			$social_links[] = 'https://instagram.com/' . ltrim( $instagram, '@' );
		}

		if ( ! empty( $social_links ) ) {
			$organization_schema['sameAs'] = $social_links;
		}

		return $organization_schema;
	}

	/**
	 * Singular içerik için schema oluşturur
	 *
	 * @since 1.0.0
	 * @return array Singular schema
	 */
	public function get_singular_schema() {
		$post = get_queried_object();

		// Post meta ile belirlenen schema tipi
		$custom_type = get_post_meta( $post->ID, '_wpsm_schema_type', true );
		$default_type = $this->options->get( 'default_schema_type', 'Article' );
		$type = ! empty( $custom_type ) ? $custom_type : $default_type;

		$schema = null;

		switch ( $type ) {
			case 'Article':
			case 'BlogPosting':
			case 'NewsArticle':
				$article = new Article_Schema();
				$schema = $article->get_article_schema( $post, $type );
				break;
			case 'Product':
				$product = new Product_Schema();
				$schema = $product->get_product_schema( $post );
				break;
			case 'LocalBusiness':
				$local_business = new LocalBusiness_Schema();
				$schema = $local_business->get_local_business_schema( $post );
				break;
			case 'FAQPage':
				$faq = new FAQ_Schema();
				$schema = $faq->get_faq_schema( $post );
				break;
			case 'HowTo':
				$howto = new HowTo_Schema();
				$schema = $howto->get_howto_schema( $post );
				break;
		}

		return $schema;
	}

	/**
	 * Breadcrumb schema oluşturur
	 *
	 * @since 1.0.0
	 * @return array Breadcrumb schema
	 */
	public function get_breadcrumb_schema() {
		if ( ! function_exists( 'wpsm_breadcrumbs' ) ) {
			return null;
		}

		// Breadcrumb array'ini al
		$breadcrumbs = ( new \WPSM\Frontend\Breadcrumbs() )->get_breadcrumbs();

		if ( empty( $breadcrumbs ) ) {
			return null;
		}

		$list_elements = array();
		$position = 1;

		foreach ( $breadcrumbs as $index => $breadcrumb ) {
			$item = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => $breadcrumb['text'],
			);

			if ( ! empty( $breadcrumb['url'] ) && $index < count( $breadcrumbs ) - 1 ) {
				$item['item'] = $breadcrumb['url'];
			}

			$list_elements[] = $item;
			$position++;
		}

		$breadcrumb_schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $list_elements,
		);

		return $breadcrumb_schema;
	}
}
