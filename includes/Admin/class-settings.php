<?php
/**
 * Settings Sınıfı
 * 
 * Admin ayarlarını yönetir, form gönderimlerini işler.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM\Admin;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Sınıfı
 * 
 * Admin ayarlarını yönetir ve form gönderimlerini işler.
 *
 * @since 1.0.0
 */
class Settings {

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
		
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_wpsm_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_wpsm_regenerate_sitemap', array( $this, 'handle_regenerate_sitemap' ) );
	}

	/**
	 * Ayarları register et
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wpsm_settings_group',
			'wpsm_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Ayarları sanitize et
	 *
	 * @since 1.0.0
	 * @param array $input Gelen ayarlar
	 * @return array Sanitize edilmiş ayarlar
	 */
	public function sanitize_settings( $input ) {
		return $this->options->sanitize( $input );
	}

	/**
	 * Ayarları kaydet işlemini handle et
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_save_settings() {
		// Capability kontrolü
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bu işlemi yapmak için yetkiniz yok.', 'wp-seo-master' ) );
		}

		// Nonce kontrolü
		if ( ! isset( $_POST['wpsm_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpsm_settings_nonce'] ) ), 'wpsm_save_settings' ) ) {
			wp_die( esc_html__( 'Geçersiz nonce.', 'wp-seo-master' ) );
		}

		// Gelen ayarları al
		$input = array();
		if ( isset( $_POST['wpsm_settings'] ) ) {
			$input = map_deep( wp_unslash( $_POST['wpsm_settings'] ), 'sanitize_text_field' );
		}

		// Ayarları sanitize et ve kaydet
		$sanitized = $this->sanitize_settings( $input );
		$result = $this->options->update( $sanitized );

		// Redirect
		$redirect_url = add_query_arg(
			array(
				'page'    => 'wpsm-general',
				'message' => $result ? 'updated' : 'error',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Sitemap yeniden oluşturma işlemini handle et
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_regenerate_sitemap() {
		// Capability kontrolü
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bu işlemi yapmak için yetkiniz yok.', 'wp-seo-master' ) );
		}

		// Nonce kontrolü
		if ( ! isset( $_POST['wpsm_sitemap_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpsm_sitemap_nonce'] ) ), 'wpsm_regenerate_sitemap' ) ) {
			wp_die( esc_html__( 'Geçersiz nonce.', 'wp-seo-master' ) );
		}

		// Sitemap cache'ini temizle
		$this->clear_sitemap_cache();

		// Redirect
		$redirect_url = add_query_arg(
			array(
				'page'    => 'wpsm-sitemap',
				'message' => 'sitemap_cleared',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Sitemap cache'ini temizle
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function clear_sitemap_cache() {
		global $wpdb;
		
		$wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_wpsm_sitemap_%',
				'_transient_timeout_wpsm_sitemap_%'
			)
		);
	}
}
