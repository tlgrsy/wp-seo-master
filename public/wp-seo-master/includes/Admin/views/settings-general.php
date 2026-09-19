<?php
/**
 * Genel Ayarlar Sayfası View
 *
 * Title separator, home title/description, verification kodları, robots.txt
 *
 * @package WPSM\Admin\Views
 * @since 1.0.0
 *
 * @var \WPSM\Admin\Class_Settings $settings Settings instance
 * @var \WPSM\Class_Options        $options  Options instance
 */

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Options instance'ı al
if ( ! isset( $options ) ) {
    $options = new \WPSM\Class_Options();
}

// Mevcut değerler
$title_separator      = $options->get( 'title_separator', '|' );
$home_title           = $options->get( 'home_title', '' );
$home_description     = $options->get( 'home_description', '' );
$google_verification  = $options->get( 'google_verification', '' );
$bing_verification    = $options->get( 'bing_verification', '' );
$yandex_verification  = $options->get( 'yandex_verification', '' );
$pinterest_verification = $options->get( 'pinterest_verification', '' );
$robots_txt           = $options->get( 'robots_txt', '' );

// Kaydetme işlemi
$message = '';
if ( isset( $_POST['wpsm_save_general'] ) ) {
    // Nonce kontrolü
    if ( ! isset( $_POST['wpsm_nonce'] ) || ! wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Güvenlik doğrulaması başarısız.', 'wp-seo-master' ) . '</p></div>';
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) . '</p></div>';
    } else {
        // Verileri sanitize et ve kaydet
        $data = array();

        if ( isset( $_POST['wpsm_settings'] ) && is_array( $_POST['wpsm_settings'] ) ) {
            foreach ( $_POST['wpsm_settings'] as $key => $value ) {
                $key = sanitize_text_field( $key );
                
                switch ( $key ) {
                    case 'title_separator':
                        $allowed_seps = array( '|', '-', '–', '»', '/', '·', '•' );
                        $value = sanitize_text_field( $value );
                        $data[ $key ] = in_array( $value, $allowed_seps, true ) ? $value : '|';
                        break;
                    case 'home_title':
                        $data[ $key ] = sanitize_text_field( $value );
                        break;
                    case 'home_description':
                        $data[ $key ] = sanitize_textarea_field( $value );
                        break;
                    case 'google_verification':
                    case 'bing_verification':
                    case 'yandex_verification':
                    case 'pinterest_verification':
                        $data[ $key ] = sanitize_text_field( $value );
                        break;
                    case 'robots_txt':
                        $data[ $key ] = sanitize_textarea_field( $value );
                        break;
                    default:
                        $data[ $key ] = sanitize_text_field( $value );
                }
            }

            $options->update( $data );
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ayarlar başarıyla kaydedildi.', 'wp-seo-master' ) . '</p></div>';
            
            // Güncel değerleri yeniden al
            $title_separator      = $options->get( 'title_separator', '|' );
            $home_title           = $options->get( 'home_title', '' );
            $home_description     = $options->get( 'home_description', '' );
            $google_verification  = $options->get( 'google_verification', '' );
            $bing_verification    = $options->get( 'bing_verification', '' );
            $yandex_verification  = $options->get( 'yandex_verification', '' );
            $pinterest_verification = $options->get( 'pinterest_verification', '' );
            $robots_txt           = $options->get( 'robots_txt', '' );
        }
    }
}

// Varsayılan robots.txt
$default_robots_txt = "User-agent: *\nAllow: /\n\nSitemap: " . home_url( '/sitemap.xml' );
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-settings" style="vertical-align: middle; margin-right: 8px;"></span>
        <?php esc_html_e( 'Genel Ayarlar', 'wp-seo-master' ); ?>
        <span class="wpsm-version" style="font-size: 12px; color: #666; margin-left: 8px;">v<?php echo esc_html( WPSM_VERSION ); ?></span>
    </h1>

    <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper wpsm-nav-tab-wrapper">
        <?php
        $tabs = array(
            'general' => __( 'Genel', 'wp-seo-master' ),
            'schema'  => __( 'Şema', 'wp-seo-master' ),
            'social'  => __( 'Sosyal Medya', 'wp-seo-master' ),
            'sitemap' => __( 'Sitemap', 'wp-seo-master' ),
        );

        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

        foreach ( $tabs as $tab_key => $tab_label ) :
            $tab_url = add_query_arg( array(
                'page' => 'wp-seo-master-settings',
                'tab'  => $tab_key,
            ), admin_url( 'admin.php' ) );
            $active_class = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';
        ?>
            <a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>">
                <?php echo esc_html( $tab_label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="" class="wpsm-settings-form">
        <?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_nonce' ); ?>

        <!-- Title Separator -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Başlık Ayırıcı', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-title-separator"><?php esc_html_e( 'Ayırıcı Karakter', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <select name="wpsm_settings[title_separator]" id="wpsm-title-separator">
                                <?php
                                $separators = array(
                                    '|'  => '| (Pipe)',
                                    '-'  => '- (Tire)',
                                    '–'  => '– (Uzun Tire)',
                                    '»'  => '» (Sağ Ok)',
                                    '/'  => '/ (Slash)',
                                    '·'  => '· (Orta Nokta)',
                                    '•'  => '• (Bullet)',
                                );
                                foreach ( $separators as $sep_value => $sep_label ) :
                                ?>
                                    <option value="<?php echo esc_attr( $sep_value ); ?>" <?php selected( $title_separator, $sep_value ); ?>>
                                        <?php echo esc_html( $sep_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Sayfa başlığında site adı ile sayfa başlığını ayıran karakter.', 'wp-seo-master' ); ?>
                            </p>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: örnek başlık */
                                    esc_html__( 'Örnek: %s', 'wp-seo-master' ),
                                    '<strong>' . esc_html( get_bloginfo( 'name' ) ) . ' ' . esc_html( $title_separator ) . ' ' . esc_html__( 'Sayfa Başlığı', 'wp-seo-master' ) . '</strong>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Home Title & Description -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Ana Sayfa SEO', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-home-title"><?php esc_html_e( 'Ana Sayfa Başlığı', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[home_title]" id="wpsm-home-title" value="<?php echo esc_attr( $home_title ); ?>" class="regular-text wpsm-char-input" data-max="60" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                            <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/60</span>
                            <p class="description">
                                <?php esc_html_e( 'Ana sayfa için SEO başlığı. Boş bırakılırsa site başlığı kullanılır.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-home-description"><?php esc_html_e( 'Ana Sayfa Açıklaması', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <textarea name="wpsm_settings[home_description]" id="wpsm-home-description" class="large-text wpsm-char-input" data-max="160" rows="3" placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $home_description ); ?></textarea>
                            <span class="wpsm-char-counter"><span class="wpsm-char-count">0</span>/160</span>
                            <p class="description">
                                <?php esc_html_e( 'Ana sayfa için meta açıklaması. 160 karakter önerilir.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Verification Codes -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Webmaster Araçları Doğrulama', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-google-verification"><?php esc_html_e( 'Google Search Console', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[google_verification]" id="wpsm-google-verification" value="<?php echo esc_attr( $google_verification ); ?>" class="regular-text" placeholder="google-site-verification=..." />
                            <p class="description">
                                <?php esc_html_e( 'Google Search Console doğrulama meta etiketi içeriği.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-bing-verification"><?php esc_html_e( 'Bing Webmaster Tools', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[bing_verification]" id="wpsm-bing-verification" value="<?php echo esc_attr( $bing_verification ); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Bing Webmaster Tools doğrulama kodu.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-yandex-verification"><?php esc_html_e( 'Yandex Webmaster', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[yandex_verification]" id="wpsm-yandex-verification" value="<?php echo esc_attr( $yandex_verification ); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Yandex Webmaster doğrulama kodu.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-pinterest-verification"><?php esc_html_e( 'Pinterest', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[pinterest_verification]" id="wpsm-pinterest-verification" value="<?php echo esc_attr( $pinterest_verification ); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Pinterest site doğrulama kodu.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Robots.txt -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Robots.txt', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-robots-txt"><?php esc_html_e( 'Robots.txt İçeriği', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <textarea name="wpsm_settings[robots_txt]" id="wpsm-robots-txt" class="large-text code" rows="10"><?php echo esc_textarea( ! empty( $robots_txt ) ? $robots_txt : $default_robots_txt ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Robots.txt dosyasının içeriği. Boş bırakılırsa WordPress varsayılanları kullanılır.', 'wp-seo-master' ); ?>
                            </p>
                            <p>
                                <button type="button" class="button" id="wpsm-reset-robots" data-default="<?php echo esc_attr( $default_robots_txt ); ?>">
                                    <?php esc_html_e( 'Varsayılana Dön', 'wp-seo-master' ); ?>
                                </button>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Submit -->
        <p class="submit">
            <input type="submit" name="wpsm_save_general" class="button-primary" value="<?php esc_attr_e( 'Değişiklikleri Kaydet', 'wp-seo-master' ); ?>" />
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Robots.txt varsayılana dön
    $('#wpsm-reset-robots').on('click', function() {
        var defaultValue = $(this).data('default');
        $('#wpsm-robots-txt').val(defaultValue);
    });

    // Karakter sayaçlarını başlat
    $('.wpsm-char-input').each(function() {
        var $input = $(this);
        var $counter = $input.next('.wpsm-char-counter');
        var maxChars = parseInt($input.data('max'), 10);
        
        function updateCounter() {
            var length = $input.val().length;
            $counter.find('.wpsm-char-count').text(length);
            
            $counter.removeClass('wpsm-char-green wpsm-char-yellow wpsm-char-red');
            var percentage = (length / maxChars) * 100;
            
            if (percentage <= 80) {
                $counter.addClass('wpsm-char-green');
            } else if (percentage <= 100) {
                $counter.addClass('wpsm-char-yellow');
            } else {
                $counter.addClass('wpsm-char-red');
            }
        }
        
        $input.on('input keyup', updateCounter);
        updateCounter();
    });
});
</script>
