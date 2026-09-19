<?php
/**
 * Sitemap Ayarları Sayfası
 *
 * Sitemap durumu, URL listesi, cache temizleme.
 *
 * @package WPSM\Admin\Views
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Options instance
if ( ! isset( $options ) ) {
    $options = new \WPSM\Class_Options();
}

// Sitemap generator instance
$sitemap_generator = new \WPSM\Sitemap\Class_Sitemap_Generator( $options );

// Mevcut değerler
$enable_sitemap         = $options->get( 'enable_sitemap', true );
$sitemap_post_types     = $options->get( 'sitemap_post_types', array( 'post', 'page' ) );
$sitemap_taxonomies     = $options->get( 'sitemap_taxonomies', array( 'category' ) );
$sitemap_posts_per_page = $options->get( 'sitemap_posts_per_page', 1000 );
$enable_author_sitemap  = $options->get( 'enable_author_sitemap', false );

// Kaydetme işlemi
$message = '';
if ( isset( $_POST['wpsm_save_sitemap'] ) ) {
    // Nonce kontrolü
    if ( ! isset( $_POST['wpsm_nonce'] ) || ! wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Güvenlik doğrulaması başarısız.', 'wp-seo-master' ) . '</p></div>';
    } elseif ( ! current_user_can( 'manage_options' ) ) {
        $message = '<div class="notice notice-error"><p>' . esc_html__( 'Yetkiniz yok.', 'wp-seo-master' ) . '</p></div>';
    } else {
        $data = array();

        if ( isset( $_POST['wpsm_settings'] ) && is_array( $_POST['wpsm_settings'] ) ) {
            foreach ( $_POST['wpsm_settings'] as $key => $value ) {
                $key = sanitize_text_field( $key );

                switch ( $key ) {
                    case 'enable_sitemap':
                    case 'enable_author_sitemap':
                        $data[ $key ] = (bool) $value;
                        break;
                    case 'sitemap_posts_per_page':
                        $data[ $key ] = intval( $value );
                        break;
                    case 'sitemap_post_types':
                    case 'sitemap_taxonomies':
                        if ( is_array( $value ) ) {
                            $data[ $key ] = array_map( 'sanitize_text_field', $value );
                        }
                        break;
                    default:
                        $data[ $key ] = sanitize_text_field( $value );
                }
            }

            // Checkbox'lar işaretlenmemişse false olarak kaydet
            if ( ! isset( $_POST['wpsm_settings']['enable_sitemap'] ) ) {
                $data['enable_sitemap'] = false;
            }
            if ( ! isset( $_POST['wpsm_settings']['enable_author_sitemap'] ) ) {
                $data['enable_author_sitemap'] = false;
            }

            $options->update( $data );
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sitemap ayarları başarıyla kaydedildi.', 'wp-seo-master' ) . '</p></div>';

            // Cache'i temizle
            $sitemap_generator->clear_cache();

            // Rewrite kurallarını yenile
            flush_rewrite_rules();

            // Güncel değerleri yeniden al
            $enable_sitemap         = $options->get( 'enable_sitemap', true );
            $sitemap_post_types     = $options->get( 'sitemap_post_types', array( 'post', 'page' ) );
            $sitemap_taxonomies     = $options->get( 'sitemap_taxonomies', array( 'category' ) );
            $sitemap_posts_per_page = $options->get( 'sitemap_posts_per_page', 1000 );
            $enable_author_sitemap  = $options->get( 'enable_author_sitemap', false );
        }
    }
}

// Cache temizleme işlemi
if ( isset( $_POST['wpsm_clear_sitemap_cache'] ) ) {
    if ( isset( $_POST['wpsm_nonce'] ) && wp_verify_nonce( $_POST['wpsm_nonce'], 'wpsm_save_settings' ) ) {
        if ( current_user_can( 'manage_options' ) ) {
            $sitemap_generator->clear_cache();
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sitemap cache başarıyla temizlendi.', 'wp-seo-master' ) . '</p></div>';
        }
    }
}

// Sitemap URL'lerini al
$sitemap_urls = $sitemap_generator->get_sitemap_urls();
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 8px;"></span>
        <?php esc_html_e( 'Sitemap Ayarları', 'wp-seo-master' ); ?>
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

        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'sitemap';

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

        <!-- Sitemap Durumu -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Sitemap Durumu', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-enable-sitemap"><?php esc_html_e( 'Sitemap Etkin', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_sitemap]" id="wpsm-enable-sitemap" value="1" <?php checked( $enable_sitemap ); ?> />
                                <?php esc_html_e( 'XML Sitemap\'i etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Sitemap, arama motorlarına sitenizdeki tüm sayfaları bildirmenizi sağlar.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Sitemap URL', 'wp-seo-master' ); ?>
                        </th>
                        <td>
                            <code><?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?></code>
                            <p class="description">
                                <?php esc_html_e( 'Bu URL\'i Google Search Console ve diğer arama motorlarına gönderin.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Dahil Edilecek İçerikler -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Dahil Edilecek İçerikler', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Post Türleri', 'wp-seo-master' ); ?>
                        </th>
                        <td>
                            <?php
                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                            foreach ( $post_types as $post_type ) :
                                if ( 'attachment' === $post_type->name ) {
                                    continue;
                                }
                            ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="wpsm_settings[sitemap_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $sitemap_post_types, true ) ); ?> />
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">
                                <?php esc_html_e( 'Sitemap\'e dahil edilecek post türlerini seçin.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Taksonomiler', 'wp-seo-master' ); ?>
                        </th>
                        <td>
                            <?php
                            $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
                            foreach ( $taxonomies as $taxonomy ) :
                                if ( 'post_format' === $taxonomy->name ) {
                                    continue;
                                }
                            ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="wpsm_settings[sitemap_taxonomies][]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php checked( in_array( $taxonomy->name, $sitemap_taxonomies, true ) ); ?> />
                                    <?php echo esc_html( $taxonomy->label ); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">
                                <?php esc_html_e( 'Sitemap\'e dahil edilecek taksonomileri seçin.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-enable-author-sitemap"><?php esc_html_e( 'Yazar Sitemap', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_author_sitemap]" id="wpsm-enable-author-sitemap" value="1" <?php checked( $enable_author_sitemap ); ?> />
                                <?php esc_html_e( 'Yazar sayfalarını sitemap\'e ekle', 'wp-seo-master' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-sitemap-posts-per-page"><?php esc_html_e( 'URL Sayısı (Maksimum)', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="wpsm_settings[sitemap_posts_per_page]" id="wpsm-sitemap-posts-per-page" value="<?php echo esc_attr( $sitemap_posts_per_page ); ?>" class="small-text" min="1" max="50000" />
                            <p class="description">
                                <?php esc_html_e( 'Her sitemap dosyasında maksimum URL sayısı. Performans için 1000 önerilir.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Sitemap URL Listesi -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Sitemap URL Listesi', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Tip', 'wp-seo-master' ); ?></th>
                            <th><?php esc_html_e( 'URL', 'wp-seo-master' ); ?></th>
                            <th><?php esc_html_e( 'İşlem', 'wp-seo-master' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $sitemap_urls as $type => $url ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( ucfirst( $type ) ); ?></strong></td>
                                <td><code><?php echo esc_url( $url ); ?></code></td>
                                <td>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" class="button button-small">
                                        <?php esc_html_e( 'Görüntüle', 'wp-seo-master' ); ?>
                                    </a>
                                    <a href="https://www.google.com/webmasters/tools/ping?sitemap=<?php echo esc_url( rawurlencode( $url ) ); ?>" target="_blank" class="button button-small">
                                        <?php esc_html_e( 'Google\'a Gönder', 'wp-seo-master' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cache Yönetimi -->
        <div class="postbox">
            <h2 class="hndle">
                <span><?php esc_html_e( 'Cache Yönetimi', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <p class="description">
                    <?php esc_html_e( 'Sitemap dosyaları 12 saat boyunca cache\'lenir. İçerik güncellendiğinde otomatik temizlenir, ancak manuel olarak da temizleyebilirsiniz.', 'wp-seo-master' ); ?>
                </p>
                <p>
                    <button type="submit" name="wpsm_clear_sitemap_cache" value="1" class="button button-secondary">
                        <?php esc_html_e( 'Sitemap Cache\'ini Temizle', 'wp-seo-master' ); ?>
                    </button>
                </p>
            </div>
        </div>

        <!-- Submit -->
        <p class="submit">
            <input type="submit" name="wpsm_save_sitemap" class="button-primary" value="<?php esc_attr_e( 'Değişiklikleri Kaydet', 'wp-seo-master' ); ?>" />
        </p>
    </form>
</div>
