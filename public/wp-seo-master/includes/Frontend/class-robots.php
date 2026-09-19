<?php
/**
 * Robots Sınıfı
 *
 * robots.txt dosyasını dinamik olarak oluşturur.
 * Sitemap URL'ini otomatik ekler.
 * Admin panelden override edilebilir.
 *
 * @package WPSM\Frontend
 * @since 1.0.0
 */

namespace WPSM\Frontend;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Class_Robots
 *
 * robots.txt dosyasını yönetir.
 * WordPress'in varsayılan robots.txt çıktısını filtreler.
 */
class Class_Robots {

    /**
     * Options instance
     *
     * @var \WPSM\Class_Options
     */
    private $options;

    /**
     * Kurucu metod
     *
     * @param \WPSM\Class_Options $options Options instance
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Robots meta etiketini filtrele
     *
     * wp_robots filter'ı ile çalışır (WP 5.7+).
     * Post meta _wpsm_robots ile override edilir.
     *
     * @param array $robots Mevcut robots direktifleri
     * @return array Düzenlenmiş robots direktifleri
     */
    public function modify_robots( $robots ) {
        // Singular sayfa - post meta kontrolü
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post_robots = get_post_meta( $post_id, '_wpsm_robots', true );

            if ( ! empty( $post_robots ) && is_array( $post_robots ) ) {
                // WordPress robots formatına çevir
                foreach ( $post_robots as $directive ) {
                    switch ( $directive ) {
                        case 'noindex':
                            $robots['noindex'] = true;
                            break;
                        case 'nofollow':
                            $robots['nofollow'] = true;
                            break;
                        case 'noarchive':
                            $robots['noarchive'] = true;
                            break;
                        case 'nosnippet':
                            $robots['nosnippet'] = true;
                            break;
                        case 'noimageindex':
                            $robots['noimageindex'] = true;
                            break;
                    }
                }
            }
        }

        // Arama sonuçları ve 404 için otomatik noindex
        if ( is_search() || is_404() ) {
            $robots['noindex'] = true;
        }

        // Tarih arşivleri
        if ( is_date() ) {
            $robots['noindex'] = true;
        }

        return $robots;
    }

    /**
     * Robots.txt içeriğini filtrele
     *
     * robots_txt filter'ı ile çalışır.
     * Sitemap URL'ini otomatik ekler.
     * Admin panelden override edilebilir.
     *
     * @param string $output   Varsayılan robots.txt içeriği
     * @param bool   $public   Site herkese açık mı?
     * @return string Düzenlenmiş robots.txt içeriği
     */
    public function filter_robots_txt( $output, $public ) {
        // Site özel ise
        if ( ! $public ) {
            return "User-agent: *\nDisallow: /";
        }

        // Admin panelden özel robots.txt tanımlanmış mı?
        $custom_robots = $this->options->get( 'robots_txt', '' );

        if ( ! empty( $custom_robots ) ) {
            // Özel robots.txt kullan
            $output = $custom_robots;
        } else {
            // Varsayılan WordPress robots.txt
            $output  = "User-agent: *\n";
            $output .= "Disallow: /wp-admin/\n";
            $output .= "Allow: /wp-admin/admin-ajax.php\n";
        }

        // Sitemap URL'ini ekle
        $sitemap_enabled = $this->options->get( 'enable_sitemap', true );

        if ( $sitemap_enabled ) {
            $sitemap_url = home_url( '/sitemap.xml' );
            $output .= "\n\nSitemap: " . esc_url( $sitemap_url );
        }

        /**
         * Robots.txt içeriğini filtrele
         *
         * @param string $output Robots.txt içeriği
         */
        return apply_filters( 'wpsm_robots_txt', $output );
    }

    /**
     * Robots.txt için rewrite kuralı ekle
     *
     * WordPress varsayılan robots.txt rewrite kuralını kullanır.
     * Eğer özel robots.txt tanımlanmışsa onu döndürür.
     */
    public function register_robots_rewrite() {
        // WordPress zaten robots.txt için rewrite kuralı ekliyor
        // Sadece filter ile içeriği değiştiriyoruz
        add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
    }
}
