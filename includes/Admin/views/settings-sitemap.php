<?php
/**
 * Sitemap Ayarları View Dosyası
 * 
 * XML sitemap ayarları için admin paneli view dosyası.
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
	<h1><?php echo esc_html__( 'Sitemap Ayarları', 'wp-seo-master' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wpsm_save_settings">
		<?php wp_nonce_field( 'wpsm_save_settings', 'wpsm_settings_nonce' ); ?>

		<div class="metabox-holder">
			<div class="postbox">
				<h2 class="hndle"><?php esc_html_e( 'Sitemap Ayarları', 'wp-seo-master' ); ?></h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'XML Sitemap', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'XML Sitemap', 'wp-seo-master' ); ?></span></legend>
									<label>
										<input name="wpsm_settings[enable_sitemap]" type="checkbox" id="wpsm_enable_sitemap" value="1" <?php checked( $options->get( 'enable_sitemap' ) ); ?>>
										<?php esc_html_e( 'XML sitemap\'i etkinleştir', 'wp-seo-master' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Post Types', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<?php
									$post_types = get_post_types( array( 'public' => true ), 'objects' );
									foreach ( $post_types as $post_type ) {
										if ( $post_type->name === 'attachment' ) {
											continue; // Attachments hariç tut
										}
										$field_name = 'wpsm_sitemap_post_types_' . $post_type->name;
										$enabled = $options->get( $field_name, true );
										?>
										<label>
											<input name="wpsm_settings[<?php echo esc_attr( $field_name ); ?>]" type="checkbox" value="1" <?php checked( $enabled ); ?>>
											<?php echo esc_html( $post_type->labels->singular_name ); ?>
										</label><br>
										<?php
									}
									?>
									<p class="description"><?php esc_html_e( 'Hangi post typeların sitemap\'e dahil edileceğini seçin', 'wp-seo-master' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Taxonomies', 'wp-seo-master' ); ?></th>
							<td>
								<fieldset>
									<?php
									$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
									foreach ( $taxonomies as $taxonomy ) {
										$field_name = 'wpsm_sitemap_taxonomies_' . $taxonomy->name;
										$enabled = $options->get( $field_name, true );
										?>
										<label>
											<input name="wpsm_settings[<?php echo esc_attr( $field_name ); ?>]" type="checkbox" value="1" <?php checked( $enabled ); ?>>
											<?php echo esc_html( $taxonomy->labels->singular_name ); ?>
										</label><br>
										<?php
									}
									?>
									<p class="description"><?php esc_html_e( 'Hangi taksonomilerin sitemap\'e dahil edileceğini seçin', 'wp-seo-master' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Cache Süresi', 'wp-seo-master' ); ?></th>
							<td>
								<input name="wpsm_settings[sitemap_cache_hours]" type="number" min="1" max="168" id="wpsm_sitemap_cache_hours" value="<?php echo esc_attr( $options->get( 'sitemap_cache_hours', 12 ) ); ?>" class="small-text">
								<?php esc_html_e( 'saat', 'wp-seo-master' ); ?>
								<p class="description"><?php esc_html_e( 'Sitemap\'ler için cache süresi (saat cinsinden)', 'wp-seo-master' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<p class="submit">
			<?php submit_button( '', 'primary', 'submit', false ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=wpsm_regenerate_sitemap&_wpnonce=' . wp_create_nonce( 'wpsm_regenerate_sitemap' ) ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Sitemap\'i Yeniden Oluştur', 'wp-seo-master' ); ?></a>
		</p>
	</form>
</div>
