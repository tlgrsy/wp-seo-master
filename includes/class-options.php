<?php
/**
 * Options Sınıfı
 * 
 * Plugin ayarlarını yönetir, cache mekanizması sağlar.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options Sınıfı
 * 
 * Plugin ayarlarını yönetir ve cache'ler.
 *
 * @since 1.0.0
 */
class Options {

	/**
	 * Singleton instance
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private static $instance = null;

	/**
	 * Cache
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Instance alır
	 *
	 * @since 1.0.0
	 * @return Options
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Cache'i yükle
		self::$cache = get_option( 'wpsm_settings', array() );
	}

	/**
	 * Ayar değerini alır
	 *
	 * @since 1.0.0
	 * @param string $key Ayar anahtarı
	 * @param mixed  $default Varsayılan değer
	 * @return mixed Ayar değeri
	 */
	public function get( $key, $default = null ) {
		if ( isset( self::$cache[ $key ] ) ) {
			return self::$cache[ $key ];
		}
		return $default;
	}

	/**
	 * Ayar değerini günceller
	 *
	 * @since 1.0.0
	 * @param string $key Ayar anahtarı
	 * @param mixed  $value Ayar değeri
	 * @return bool Başarılı mı?
	 */
	public function set( $key, $value ) {
		self::$cache[ $key ] = $value;
		return update_option( 'wpsm_settings', self::$cache );
	}

	/**
	 * Tüm ayarları alır
	 *
	 * @since 1.0.0
	 * @return array Tüm ayarlar
	 */
	public function all() {
		return self::$cache;
	}

	/**
	 * Ayarları toplu günceller
	 *
	 * @since 1.0.0
	 * @param array $array Yeni ayarlar
	 * @return bool Başarılı mı?
	 */
	public function update( $array ) {
		self::$cache = wp_parse_args( $array, self::$cache );
		return update_option( 'wpsm_settings', self::$cache );
	}

	/**
	 * Ayar değerini siler
	 *
	 * @since 1.0.0
	 * @param string $key Ayar anahtarı
	 * @return bool Başarılı mı?
	 */
	public function delete( $key ) {
		if ( isset( self::$cache[ $key ] ) ) {
			unset( self::$cache[ $key ] );
			return update_option( 'wpsm_settings', self::$cache );
		}
		return false;
	}

	/**
	 * Ayarları sanitize eder
	 *
	 * @since 1.0.0
	 * @param array $input Gelen ayarlar
	 * @return array Sanitize edilmiş ayarlar
	 */
	public function sanitize( $input ) {
		$sanitized = array();

		// String alanlar
		$string_fields = array(
			'title_separator',
			'home_title',
			'home_description',
			'org_name',
			'org_logo',
			'org_facebook',
			'org_twitter',
			'org_instagram',
			'twitter_site',
			'facebook_app_id',
			'default_og_image',
			'gsc_verification',
			'bing_verification',
			'robots_txt_custom',
			'default_schema_type',
			'twitter_card_type',
		);

		foreach ( $string_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				if ( in_array( $field, array( 'org_logo', 'default_og_image', 'org_facebook' ) ) ) {
					$sanitized[ $field ] = esc_url_raw( $input[ $field ] );
				} elseif ( in_array( $field, array( 'gsc_verification', 'bing_verification' ) ) ) {
					$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
				} else {
					$sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
				}
			}
		}

		// Boolean alanlar
		$bool_fields = array(
			'enable_sitemap',
			'enable_schema',
			'enable_opengraph',
			'enable_twitter',
			'enable_breadcrumbs',
			'enable_analyzer',
			'enable_search_action',
		);

		foreach ( $bool_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? true : false;
		}

		// Integer alanlar
		if ( isset( $input['sitemap_cache_hours'] ) ) {
			$sanitized['sitemap_cache_hours'] = absint( $input['sitemap_cache_hours'] );
			// Min 1, max 168 saat (1 hafta)
			$sanitized['sitemap_cache_hours'] = max( 1, min( 168, $sanitized['sitemap_cache_hours'] ) );
		}

		// Post type ve taxonomy checkbox'ları
		$all_post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $all_post_types['attachment'] );

		foreach ( $all_post_types as $post_type ) {
			$field = 'wpsm_sitemap_post_types_' . $post_type;
			$sanitized[ $field ] = isset( $input[ $field ] ) ? true : false;
		}

		$all_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
		foreach ( $all_taxonomies as $taxonomy ) {
			$field = 'wpsm_sitemap_taxonomies_' . $taxonomy;
			$sanitized[ $field ] = isset( $input[ $field ] ) ? true : false;
		}

		return $sanitized;
	}
}
