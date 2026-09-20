<?php
/**
 * Uninstall Script
 * 
 * Plugin silindiğinde çalışır ve tüm verileri temizler.
 *
 * @package WPSM
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Gerekli global değişkenler
global $wpdb;

// Ayarları sil
delete_option( 'wpsm_settings' );

// Post meta'ları sil
$wpdb->query( 
	"DELETE FROM {$wpdb->postmeta} 
	WHERE meta_key LIKE '_wpsm_%'"
);

// User meta'ları sil (eğer varsa)
$wpdb->query( 
	"DELETE FROM {$wpdb->usermeta} 
	WHERE meta_key LIKE '_wpsm_%'"
);

// Transient'leri sil
$wpdb->query( 
	"DELETE FROM {$wpdb->options} 
	WHERE option_name LIKE '_transient_wpsm_%' 
	OR option_name LIKE '_transient_timeout_wpsm_%'"
);

// Multisite desteği
if ( is_multisite() ) {
	// Tüm siteleri döngüye al
	$sites = get_sites();
	
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		
		// Aynı işlemleri bu site için yap
		delete_option( 'wpsm_settings' );
		
		$wpdb->query( 
			"DELETE FROM {$wpdb->postmeta} 
			WHERE meta_key LIKE '_wpsm_%'"
		);
		
		$wpdb->query( 
			"DELETE FROM {$wpdb->usermeta} 
			WHERE meta_key LIKE '_wpsm_%'"
		);
		
		$wpdb->query( 
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_wpsm_%' 
			OR option_name LIKE '_transient_timeout_wpsm_%'"
		);
		
		restore_current_blog();
	}
}
