<?php
/**
 * Installer Sınıfı
 * 
 * Plugin aktivasyon ve deaktivasyon işlemlerini yönetir.
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
 * Installer Sınıfı
 * 
 * Plugin aktivasyon ve deaktivasyon işlemlerini yönetir.
 *
 * @since 1.0.0
 */
class Installer {

	/**
	 * Plugin aktivasyonu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		// Varsayılan ayarları yaz
		self::set_default_options();

		// Rewrite kurallarını flush et
		flush_rewrite_rules();

		// DB versiyon kontrolü
		self::maybe_upgrade();
	}

	/**
	 * Plugin deaktivasyonu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate() {
		// Sadece rewrite kurallarını flush et
		flush_rewrite_rules();
	}

	/**
	 * Varsayılan ayarları belirle
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_default_options() {
		$defaults = array(
			'title_separator'      => '|',
			'enable_sitemap'       => true,
			'enable_schema'        => true,
			'enable_opengraph'     => true,
			'enable_twitter'       => true,
			'enable_breadcrumbs'   => true,
			'enable_analyzer'      => true,
			'default_schema_type'  => 'Article',
			'twitter_site'         => '',
			'facebook_app_id'      => '',
			'default_og_image'     => '',
			'gsc_verification'     => '',
			'bing_verification'    => '',
			'robots_txt_custom'    => '',
			'sitemap_cache_hours'  => 12,
			'db_version'           => '1.0.0',
			'home_title'           => '',
			'home_description'     => '',
			'org_name'             => '',
			'org_logo'             => '',
			'org_facebook'         => '',
			'org_twitter'          => '',
			'org_instagram'        => '',
			'enable_search_action' => true,
			'twitter_card_type'    => 'summary',
		);

		// Mevcut ayarlarla birleştir
		$existing = get_option( 'wpsm_settings', array() );
		$options = wp_parse_args( $existing, $defaults );

		update_option( 'wpsm_settings', $options );
	}

	/**
	 * Versiyon yükseltme kontrolü
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_upgrade() {
		$db_version = get_option( 'wpsm_db_version', '0.0.0' );
		$current_version = WPSM_VERSION;

		if ( version_compare( $db_version, $current_version, '<' ) ) {
			// Gelecekteki migration'lar için altyapı
			// Şu an yapılacak bir şey yok
			
			update_option( 'wpsm_db_version', $current_version );
		}
	}
}
