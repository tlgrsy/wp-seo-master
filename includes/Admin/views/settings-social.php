<?php
/**
 * Sosyal Medya Ayarları View Dosyası
 * 
 * OpenGraph ve Twitter Cards ayarları için admin paneli view dosyası.
 *
 * @package WPSM
 * @since 1.0.0
 */

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = Options::get_instance();
?>

<div class="wrap">
	<h1><?php echo esc_html__( 'Sosyal Medya Ayarları', 'wp-seo-master' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wpsm_save_settings">
		<?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_settings_nonce' ); ?>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Facebook / OpenGraph Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Facebook App ID', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[facebook_app_id]" type="text" id="wpsm_facebook_app_id" value="<?php echo esc_attr( $options->get( 'facebook_app_id' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Facebook\'ın sitenizi tanımak için kullandığı App ID (isteğe bağlı)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Varsayılan OG Görseli', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[default_og_image]" type="text" id="wpsm_default_og_image" value="<?php echo esc_url( $options->get( 'default_og_image' ) ); ?>" class="regular-text">
								<button type="button" class="upload-image-button button-secondary" data-target="wpsm_default_og_image"><?php esc_html_e( 'Resim Seç', 'wp-seo-master' ); ?></button>
								<p class="description"><?php esc_html_e( 'Her sayfada kullanılacak varsayılan OpenGraph görseli', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Twitter Cards Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Twitter Hesabı', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[twitter_site]" type="text" id="wpsm_twitter_site" value="<?php echo esc_attr( $options->get( 'twitter_site' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Twitter kullanıcı adınız (@ olmadan)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Twitter Card Tipi', 'wp-seo-master' ); ?></th>
							<td>
								<select name="wpsm_settings[twitter_card_type]" id="wpsm_twitter_card_type">
									<option value="summary" <?php selected( $options->get( 'twitter_card_type' ), 'summary' ); ?>><?php esc_html_e( 'Summary', 'wp-seo-master' ); ?></option>
									<option value="summary_large_image" <?php selected( $options->get( 'twitter_card_type' ), 'summary_large_image' ); ?>><?php esc_html_e( 'Summary Large Image', 'wp-seo-master' ); ?></option>
									<option value="app" <?php selected( $options->get( 'twitter_card_type' ), 'app' ); ?>><?php esc_html_e( 'App', 'wp-seo-master' ); ?></option>
									<option value="player" <?php selected( $options->get( 'twitter_card_type' ), 'player' ); ?>><?php esc_html_e( 'Player', 'wp-seo-master' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Twitter\'da paylaşım sırasında kullanılacak kart tipi', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Sosyal Medya Özellikleri', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'OpenGraph', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'OpenGraph', 'wp-seo-master' ); ?></span></legend>
									<label>
										<input name="wpsm_settings[enable_opengraph]" type="checkbox" id="wpsm_enable_opengraph" value="1" <?php checked( $options->get( 'enable_opengraph' ) ); ?>>
										<?php esc_html_e( 'OpenGraph meta etiketlerini etkinleştir', 'wp-seo-master' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Twitter Cards', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Twitter Cards', 'wp-seo-master' ); ?></span></legend>
									<label>
										<input name="wpsm_settings[enable_twitter]" type="checkbox" id="wpsm_enable_twitter" value="1" <?php checked( $options->get( 'enable_twitter' ) ); ?>>
										<?php esc_html_e( 'Twitter Cards meta etiketlerini etkinleştir', 'wp-seo-master' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
