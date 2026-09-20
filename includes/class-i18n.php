<?php
/**
 * Internationalization Sınıfı
 * 
 * Plugin'in dil dosyalarını yükler ve uluslararasılaştırmayı sağlar.
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
 * I18n Sınıfı
 * 
 * Dil dosyalarını yüklemek için kullanılır.
 *
 * @since 1.0.0
 */
class I18n {

	/**
	 * Sınıfı başlatır
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Plugin'in dil dosyasını yükler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-seo-master',
			false,
			dirname( plugin_basename( WPSM_FILE ) ) . '/languages/'
		);
	}
}
