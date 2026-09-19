<?php
/**
 * Sosyal Medya Ayarları Sayfası View
 *
 * Facebook App ID, Twitter Site, varsayılan OG görseli, Twitter Card tipi
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
$facebook_app_id   = $options->get( 'facebook_app_id', '' );
$facebook_admins   = $options->get( 'facebook_admins', '' );
$twitter_site      = $options->get( 'twitter_site', '' );
$twitter_creator   = $options->get( 'twitter_creator', '' );
$default_og_image  = $options->get( 'default_og_image', '' );
$twitter_card_type = $options->get( 'twitter_card_type', 'summary_large_image' );
$enable_opengraph  = $options->get( 'enable_opengraph', true );
$enable_twitter    = $options->get( 'enable_twitter', true );

// Kaydetme işlemi
$message = '';
if ( isset( $_POST['wpsm_save_social'] ) ) {
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
                    case 'facebook_app_id':
                    case 'facebook_admins':
                    case 'twitter_site':
                    case 'twitter_creator':
                        $data[ $key ] = sanitize_text_field( $value );
                        // Twitter kullanıcı adından @ işaretini kaldır
                        if ( strpos( $key, 'twitter' ) !== false ) {
                            $data[ $key ] = ltrim( $data[ $key ], '@' );
                        }
                        break;
                    case 'default_og_image':
                        $data[ $key ] = esc_url_raw( $value );
                        break;
                    case 'twitter_card_type':
                        $allowed_types = array( 'summary', 'summary_large_image' );
                        $data[ $key ] = in_array( $value, $allowed_types, true ) ? $value : 'summary_large_image';
                        break;
                    case 'enable_opengraph':
                    case 'enable_twitter':
                        $data[ $key ] = (bool) $value;
                        break;
                    default:
                        $data[ $key ] = sanitize_text_field( $value );
                }
            }

            // Checkbox'lar işaretlenmemişse false olarak kaydet
            if ( ! isset( $_POST['wpsm_settings']['enable_opengraph'] ) ) {
                $data['enable_opengraph'] = false;
            }
            if ( ! isset( $_POST['wpsm_settings']['enable_twitter'] ) ) {
                $data['enable_twitter'] = false;
            }

            $options->update( $data );
            $message = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sosyal medya ayarları başarıyla kaydedildi.', 'wp-seo-master' ) . '</p></div>';

            // Güncel değerleri yeniden al
            $facebook_app_id   = $options->get( 'facebook_app_id', '' );
            $facebook_admins   = $options->get( 'facebook_admins', '' );
            $twitter_site      = $options->get( 'twitter_site', '' );
            $twitter_creator   = $options->get( 'twitter_creator', '' );
            $default_og_image  = $options->get( 'default_og_image', '' );
            $twitter_card_type = $options->get( 'twitter_card_type', 'summary_large_image' );
            $enable_opengraph  = $options->get( 'enable_opengraph', true );
            $enable_twitter    = $options->get( 'enable_twitter', true );
        }
    }
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-share" style="vertical-align: middle; margin-right: 8px;"></span>
        <?php esc_html_e( 'Sosyal Medya Ayarları', 'wp-seo-master' ); ?>
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

        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'social';

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

        <!-- Facebook Ayarları -->
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-facebook-alt" style="margin-right: 5px;"></span>
                <span><?php esc_html_e( 'Facebook / Open Graph', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-enable-opengraph"><?php esc_html_e( 'Open Graph Etkin', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_opengraph]" id="wpsm-enable-opengraph" value="1" <?php checked( $enable_opengraph ); ?> />
                                <?php esc_html_e( 'Open Graph meta etiketlerini etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Facebook, LinkedIn ve diğer sosyal platformlar için OG etiketleri.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-facebook-app-id"><?php esc_html_e( 'Facebook App ID', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[facebook_app_id]" id="wpsm-facebook-app-id" value="<?php echo esc_attr( $facebook_app_id ); ?>" class="regular-text" placeholder="123456789012345" />
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: Facebook Developers link */
                                    wp_kses(
                                        __( 'Facebook Insights için App ID. <a href="%s" target="_blank">Facebook Developers</a> sayfasından alabilirsiniz.', 'wp-seo-master' ),
                                        array( 'a' => array( 'href' => array(), 'target' => array() ) )
                                    ),
                                    esc_url( 'https://developers.facebook.com/' )
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-facebook-admins"><?php esc_html_e( 'Facebook Admins', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpsm_settings[facebook_admins]" id="wpsm-facebook-admins" value="<?php echo esc_attr( $facebook_admins ); ?>" class="regular-text" />
                            <p class="description">
                                <?php esc_html_e( 'Facebook admin kullanıcı ID\'leri (birden fazla ise virgülle ayırın).', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Varsayılan OG Görseli', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <div class="wpsm-media-upload-wrapper" data-field="default_og_image">
                                <input type="hidden" name="wpsm_settings[default_og_image]" id="wpsm-default-og-image" value="<?php echo esc_url( $default_og_image ); ?>" />
                                <div class="wpsm-media-preview" <?php echo empty( $default_og_image ) ? 'style="display:none;"' : ''; ?>>
                                    <img src="<?php echo esc_url( $default_og_image ); ?>" alt="<?php esc_attr_e( 'Varsayılan OG Görseli', 'wp-seo-master' ); ?>" />
                                </div>
                                <button type="button" class="button wpsm-media-upload-btn">
                                    <span class="dashicons dashicons-format-image" style="margin-top: 3px;"></span>
                                    <?php esc_html_e( 'Görsel Seç', 'wp-seo-master' ); ?>
                                </button>
                                <button type="button" class="button wpsm-media-remove-btn" <?php echo empty( $default_og_image ) ? 'style="display:none;"' : ''; ?>>
                                    <?php esc_html_e( 'Kaldır', 'wp-seo-master' ); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Yazılarda OG görseli belirtilmemişse kullanılacak varsayılan görsel.', 'wp-seo-master' ); ?>
                                <br />
                                <?php esc_html_e( 'Önerilen boyut: 1200 x 630 piksel (minimum 600 x 315).', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Twitter Ayarları -->
        <div class="postbox">
            <h2 class="hndle">
                <span class="dashicons dashicons-twitter" style="margin-right: 5px;"></span>
                <span><?php esc_html_e( 'Twitter / X Cards', 'wp-seo-master' ); ?></span>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpsm-enable-twitter"><?php esc_html_e( 'Twitter Cards Etkin', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpsm_settings[enable_twitter]" id="wpsm-enable-twitter" value="1" <?php checked( $enable_twitter ); ?> />
                                <?php esc_html_e( 'Twitter Card meta etiketlerini etkinleştir', 'wp-seo-master' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Twitter/X paylaşımlarında zengin kart gösterimi.', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-twitter-site"><?php esc_html_e( 'Twitter Kullanıcı Adı', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="font-size: 16px; color: #666;">@</span>
                                <input type="text" name="wpsm_settings[twitter_site]" id="wpsm-twitter-site" value="<?php echo esc_attr( $twitter_site ); ?>" class="regular-text" placeholder="kullaniciadi" />
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Sitenizin Twitter/X kullanıcı adı (@ işareti olmadan).', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-twitter-creator"><?php esc_html_e( 'Twitter İçerik Oluşturucu', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="font-size: 16px; color: #666;">@</span>
                                <input type="text" name="wpsm_settings[twitter_creator]" id="wpsm-twitter-creator" value="<?php echo esc_attr( $twitter_creator ); ?>" class="regular-text" placeholder="icerik_olusturucu" />
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'İçerik oluşturucunun Twitter/X kullanıcı adı (opsiyonel).', 'wp-seo-master' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpsm-twitter-card-type"><?php esc_html_e( 'Twitter Card Türü', 'wp-seo-master' ); ?></label>
                        </th>
                        <td>
                            <select name="wpsm_settings[twitter_card_type]" id="wpsm-twitter-card-type">
                                <option value="summary" <?php selected( $twitter_card_type, 'summary' ); ?>>
                                    <?php esc_html_e( 'Özet (Summary)', 'wp-seo-master' ); ?>
                                </option>
                                <option value="summary_large_image" <?php selected( $twitter_card_type, 'summary_large_image' ); ?>>
                                    <?php esc_html_e( 'Büyük Görsel (Summary Large Image)', 'wp-seo-master' ); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Twitter kartlarında kullanılacak görsel boyutu.', 'wp-seo-master' ); ?>
                            </p>
                            <div class="wpsm-twitter-card-preview" style="margin-top: 10px;">
                                <div class="wpsm-card-preview-item" data-type="summary" style="<?php echo 'summary' !== $twitter_card_type ? 'display:none;' : ''; ?>">
                                    <div style="width: 120px; height: 120px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; display: inline-block; vertical-align: middle;"></div>
                                    <div style="display: inline-block; vertical-align: top; margin-left: 10px; width: 200px;">
                                        <div style="height: 14px; background: #1da1f2; border-radius: 2px; margin-bottom: 6px; width: 80%;"></div>
                                        <div style="height: 10px; background: #ddd; border-radius: 2px; margin-bottom: 4px;"></div>
                                        <div style="height: 10px; background: #ddd; border-radius: 2px; width: 60%;"></div>
                                    </div>
                                </div>
                                <div class="wpsm-card-preview-item" data-type="summary_large_image" style="<?php echo 'summary_large_image' !== $twitter_card_type ? 'display:none;' : ''; ?>">
                                    <div style="width: 340px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px;">
                                        <div style="height: 170px; background: #e0e0e0; border-radius: 4px 4px 0 0;"></div>
                                        <div style="padding: 10px;">
                                            <div style="height: 14px; background: #1da1f2; border-radius: 2px; margin-bottom: 6px; width: 80%;"></div>
                                            <div style="height: 10px; background: #ddd; border-radius: 2px; margin-bottom: 4px;"></div>
                                            <div style="height: 10px; background: #ddd; border-radius: 2px; width: 60%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Submit -->
        <p class="submit">
            <input type="submit" name="wpsm_save_social" class="button-primary" value="<?php esc_attr_e( 'Değişiklikleri Kaydet', 'wp-seo-master' ); ?>" />
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Twitter Card tipi değişince önizleme güncelle
    $('#wpsm-twitter-card-type').on('change', function() {
        var type = $(this).val();
        $('.wpsm-card-preview-item').hide();
        $('.wpsm-card-preview-item[data-type="' + type + '"]').show();
    });

    // Media uploader
    var mediaFrame;
    $('.wpsm-media-upload-btn').on('click', function(e) {
        e.preventDefault();

        var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
        var $input = $wrapper.find('input[type="hidden"]');
        var $preview = $wrapper.find('.wpsm-media-preview');
        var $previewImg = $preview.find('img');
        var $removeBtn = $wrapper.find('.wpsm-media-remove-btn');

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: wpsmAdmin.i18n.selectImage || 'Görsel Seç',
            button: { text: wpsmAdmin.i18n.useImage || 'Kullan' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaFrame.on('select', function() {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            var imageUrl = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;

            $input.val(imageUrl);
            $previewImg.attr('src', imageUrl);
            $preview.show();
            $removeBtn.show();
        });

        mediaFrame.open();
    });

    // Görsel kaldır
    $('.wpsm-media-remove-btn').on('click', function(e) {
        e.preventDefault();
        var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
        $wrapper.find('input[type="hidden"]').val('');
        $wrapper.find('.wpsm-media-preview').hide();
        $(this).hide();
    });
});
</script>
