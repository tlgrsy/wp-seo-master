<?php
/**
 * Şema Ayarları View Dosyası
 * 
 * Schema.org ayarları için admin paneli view dosyası.
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
	<h1><?php echo esc_html__( 'Şema Ayarları', 'wp-seo-master' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wpsm_save_settings">
		<?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_settings_nonce' ); ?>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Organizasyon Bilgileri', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Organizasyon Adı', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[org_name]" type="text" id="wpsm_org_name" value="<?php echo esc_attr( $options->get( 'org_name' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Organizasyonunuzun adı (Website ve Article schema\'larında kullanılacak)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Organizasyon Logosu', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[org_logo]" type="text" id="wpsm_org_logo" value="<?php echo esc_url( $options->get( 'org_logo' ) ); ?>" class="regular-text">
								<button type="button" class="upload-image-button button-secondary" data-target="wpsm_org_logo"><?php esc_html_e( 'Resim Seç', 'wp-seo-master' ); ?></button>
								<p class="description"><?php esc_html_e( 'Organizasyon logonuz (Website ve Article schema\'larında kullanılacak)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Facebook Sayfası', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[org_facebook]" type="url" id="wpsm_org_facebook" value="<?php echo esc_url( $options->get( 'org_facebook' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Organizasyonun Facebook sayfası bağlantısı', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Twitter Profili', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[org_twitter]" type="text" id="wpsm_org_twitter" value="<?php echo esc_attr( $options->get( 'org_twitter' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Organizasyonun Twitter kullanıcı adı (@ olmadan)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Instagram Hesabı', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[org_instagram]" type="text" id="wpsm_org_instagram" value="<?php echo esc_attr( $options->get( 'org_instagram' ) ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Organizasyonun Instagram kullanıcı adı', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Website Schema Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Search Action', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Search Action', 'wp-seo-master' ); ?></span></legend>
									<label>
										<input name="wpsm_settings[enable_search_action]" type="checkbox" id="wpsm_enable_search_action" value="1" <?php checked( $options->get( 'enable_search_action' ) ); ?>>
										<?php esc_html_e( 'Web sitesi search action özelliğini etkinleştir', 'wp-seo-master' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Google\'ın sitenizde arama yapma eylemini anlamasını sağlar', 'wp-seo-master' ); ?></p>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Varsayılan Schema Tipi', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Varsayılan Tip', 'wp-seo-master' ); ?></th>
							<td>
								<select name="wpsm_settings[default_schema_type]" id="wpsm_default_schema_type">
									<option value="Article" <?php selected( $options->get( 'default_schema_type' ), 'Article' ); ?>><?php esc_html_e( 'Article', 'wp-seo-master' ); ?></option>
									<option value="BlogPosting" <?php selected( $options->get( 'default_schema_type' ), 'BlogPosting' ); ?>><?php esc_html_e( 'Blog Posting', 'wp-seo-master' ); ?></option>
									<option value="NewsArticle" <?php selected( $options->get( 'default_schema_type' ), 'NewsArticle' ); ?>><?php esc_html_e( 'News Article', 'wp-seo-master' ); ?></option>
									<option value="Product" <?php selected( $options->get( 'default_schema_type' ), 'Product' ); ?>><?php esc_html_e( 'Product', 'wp-seo-master' ); ?></option>
									<option value="LocalBusiness" <?php selected( $options->get( 'default_schema_type' ), 'LocalBusiness' ); ?>><?php esc_html_e( 'Local Business', 'wp-seo-master' ); ?></option>
									<option value="FAQPage" <?php selected( $options->get( 'default_schema_type' ), 'FAQPage' ); ?>><?php esc_html_e( 'FAQ Page', 'wp-seo-master' ); ?></option>
									<option value="HowTo" <?php selected( $options->get( 'default_schema_type' ), 'HowTo' ); ?>><?php esc_html_e( 'How To', 'wp-seo-master' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Yeni içerikler için varsayılan olarak kullanılacak schema tipi', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
