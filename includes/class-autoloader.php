<?php
/**
 * Autoloader Sınıfı
 * 
 * PSR-4 benzeri otomatik sınıf yükleme sistemi.
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
 * Autoloader Sınıfı
 * 
 * Namespace ve sınıf adından dosya yolunu oluşturur ve yükler.
 *
 * @since 1.0.0
 */
class Autoloader {

	/**
	 * Autoloader'ı register eder
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Sınıfı otomatik yükler
	 *
	 * @since 1.0.0
	 * @param string $class Sınıf adı (tam namespace ile)
	 * @return void
	 */
	public static function autoload( $class ) {
		// Sadece WPSM namespace'i ile ilgilen
		if ( strpos( $class, 'WPSM\\' ) !== 0 ) {
			return;
		}

		// Namespace'i kaldır
		$relative_class = substr( $class, strlen( 'WPSM\\' ) );

		// Namespace ayracını (/) yap
		$path_parts = explode( '\\', $relative_class );

		// Son eleman sınıf adı, diğerleri klasör yolu
		$class_name = array_pop( $path_parts );

		// Sınıf adını lowercase yap, _ yerine - koy, başına class- ekle
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Klasör yolunu oluştur
		$dir_path = '';
		foreach ( $path_parts as $part ) {
			$dir_path .= strtolower( str_replace( '_', '-', $part ) ) . '/';
		}

		// Tam dosya yolunu oluştur
		$file_path = WPSM_PATH . 'includes/' . $dir_path . $file_name;

		// Dosya varsa yükle
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}
