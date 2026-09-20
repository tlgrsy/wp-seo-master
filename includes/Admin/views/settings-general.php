<?php
/**
 * Genel Ayarlar View Dosyası
 * 
 * Genel SEO ayarları için admin paneli view dosyası.
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
	<h1><?php echo esc_html__( 'Genel Ayarlar', 'wp-seo-master' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wpsm_save_settings">
		<?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_settings_nonce' ); ?>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Sayfa Başlığı Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Başlık Ayracı', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Başlık Ayracı', 'wp-seo-master' ); ?></span></legend>
									<label><input name="wpsm_settings[title_separator]" type="radio" value="|" <?php checked( $options->get( 'title_separator' ), '|' ); ?>> |</label><br>
									<label><input name="wpsm_settings[title_separator]" type="radio" value="-" <?php checked( $options->get( 'title_separator' ), '-' ); ?>> -</label><br>
									<label><input name="wpsm_settings[title_separator]" type="radio" value="&mdash;" <?php checked( $options->get( 'title_separator' ), '&mdash;' ); ?>> &mdash;</label><br>
									<label><input name="wpsm_settings[title_separator]" type="radio" value="&bull;" <?php checked( $options->get( 'title_separator' ), '&bull;' ); ?>> &bull;</label><br>
									<label><input name="wpsm_settings[title_separator]" type="radio" value=":" <?php checked( $options->get( 'title_separator' ), ':' ); ?>> :</label><br>
									<label><input name="wpsm_settings[title_separator]" type="text" value="<?php echo esc_attr( $options->get( 'title_separator' ) ); ?>" style="width:50px;"> <?php esc_html_e( '(Diğer)', 'wp-seo-master' ); ?></label>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Anasayfa Meta Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Anasayfa Başlığı', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[home_title]" type="text" id="wpsm_home_title" value="<?php echo esc_attr( $options->get( 'home_title' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Boş bırakıldığında site başlığı kullanılacaktır.', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Anasayfa Açıklaması', 'wp-seo-master' ); ?></th>
							<td>
								<textarea name="wpsm_settings[home_description]" id="wpsm_home_description" rows="3" cols="50" class="large-text"><?php echo esc_textarea( $options->get( 'home_description' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Boş bırakıldığında site açıklaması kullanılacaktır.', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Doğrulama Kodları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Google Site Doğrulama', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[gsc_verification]" type="text" id="wpsm_gsc_verification" value="<?php echo esc_attr( $options->get( 'gsc_verification' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Google Search Console doğrulama kodu', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Bing Site Doğrulama', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[bing_verification]" type="text" id="wpsm_bing_verification" value="<?php echo esc_attr( $options->get( 'bing_verification' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Bing Webmaster Tools doğrulama kodu', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Custom Robots.txt', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Ekstra Robots.txt Kuralları', 'wp-seo-master' ); ?></th>
							<td>
								<textarea name="wpsm_settings[robots_txt_custom]" id="wpsm_robots_txt_custom" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $options->get( 'robots_txt_custom' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Varsayılan robots.txt kurallarına ek olarak yazılacak özel kurallar.', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
