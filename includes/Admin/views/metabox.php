<?php
/**
 * Metabox View Dosyası
 * 
 * Post editöründe görünen SEO ayarları view dosyası.
 *
 * @package WPSM
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();
?>

<div class="wpsm-metabox-wrapper">
	<div class="wpsm-tabs">
		<ul class="wpsm-tab-nav">
			<li class="active"><a href="#wpsm-tab-content"><?php esc_html_e( 'İçerik', 'wp-seo-master' ); ?></a></li>
			<li><a href="#wpsm-tab-social"><?php esc_html_e( 'Sosyal', 'wp-seo-master' ); ?></a></li>
			<li><a href="#wpsm-tab-schema"><?php esc_html_e( 'Şema', 'wp-seo-master' ); ?></a></li>
			<li><a href="#wpsm-tab-advanced"><?php esc_html_e( 'Gelişmiş', 'wp-seo-master' ); ?></a></li>
		</ul>

		<div class="wpsm-tab-content active" id="wpsm-tab-content">
			<p>
				<label for="wpsm_title"><?php esc_html_e( 'SEO Başlığı', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_title" id="wpsm_title" value="<?php echo esc_attr( get_post_meta( $post_id, '_wpsm_title', true ) ); ?>" style="width:100%;" maxlength="60">
				<span class="wpsm-char-count" data-max="60"><?php echo esc_html( strlen( get_post_meta( $post_id, '_wpsm_title', true ) ) ); ?>/60</span>
			</p>
			<p>
				<label for="wpsm_description"><?php esc_html_e( 'Meta Açıklama', 'wp-seo-master' ); ?></label><br>
				<textarea name="_wpsm_description" id="wpsm_description" rows="3" style="width:100%;"><?php echo esc_textarea( get_post_meta( $post_id, '_wpsm_description', true ) ); ?></textarea>
				<span class="wpsm-char-count" data-max="160"><?php echo esc_html( strlen( get_post_meta( $post_id, '_wpsm_description', true ) ) ); ?>/160</span>
			</p>
			<p>
				<label for="wpsm_focus_keyword"><?php esc_html_e( 'Odak Kelimesi', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_focus_keyword" id="wpsm_focus_keyword" value="<?php echo esc_attr( get_post_meta( $post_id, '_wpsm_focus_keyword', true ) ); ?>" style="width:100%;">
				<button type="button" id="wpsm-analyze-btn" class="button button-secondary"><?php esc_html_e( 'İçerik Analiz Et', 'wp-seo-master' ); ?></button>
				<div id="wpsm-analysis-results"></div>
			</p>
		</div>

		<div class="wpsm-tab-content" id="wpsm-tab-social">
			<p>
				<label for="wpsm_og_title"><?php esc_html_e( 'OpenGraph Başlığı', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_og_title" id="wpsm_og_title" value="<?php echo esc_attr( get_post_meta( $post_id, '_wpsm_og_title', true ) ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="wpsm_og_description"><?php esc_html_e( 'OpenGraph Açıklaması', 'wp-seo-master' ); ?></label><br>
				<textarea name="_wpsm_og_description" id="wpsm_og_description" rows="3" style="width:100%;"><?php echo esc_textarea( get_post_meta( $post_id, '_wpsm_og_description', true ) ); ?></textarea>
			</p>
			<p>
				<label for="wpsm_og_image"><?php esc_html_e( 'OpenGraph Görseli', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_og_image" id="wpsm_og_image" value="<?php echo esc_url( get_post_meta( $post_id, '_wpsm_og_image', true ) ); ?>" style="width:80%; float:left;">
				<button type="button" class="upload-image-button button-secondary" data-target="wpsm_og_image" style="float:left; margin-left: 5px;"><?php esc_html_e( 'Seç', 'wp-seo-master' ); ?></button>
				<br style="clear:both;">
			</p>
			<p>
				<label for="wpsm_twitter_title"><?php esc_html_e( 'Twitter Başlığı', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_twitter_title" id="wpsm_twitter_title" value="<?php echo esc_attr( get_post_meta( $post_id, '_wpsm_twitter_title', true ) ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="wpsm_twitter_description"><?php esc_html_e( 'Twitter Açıklaması', 'wp-seo-master' ); ?></label><br>
				<textarea name="_wpsm_twitter_description" id="wpsm_twitter_description" rows="3" style="width:100%;"><?php echo esc_textarea( get_post_meta( $post_id, '_wpsm_twitter_description', true ) ); ?></textarea>
			</p>
			<p>
				<label for="wpsm_twitter_image"><?php esc_html_e( 'Twitter Görseli', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_twitter_image" id="wpsm_twitter_image" value="<?php echo esc_url( get_post_meta( $post_id, '_wpsm_twitter_image', true ) ); ?>" style="width:80%; float:left;">
				<button type="button" class="upload-image-button button-secondary" data-target="wpsm_twitter_image" style="float:left; margin-left: 5px;"><?php esc_html_e( 'Seç', 'wp-seo-master' ); ?></button>
				<br style="clear:both;">
			</p>
		</div>

		<div class="wpsm-tab-content" id="wpsm-tab-schema">
			<p>
				<label for="wpsm_schema_type"><?php esc_html_e( 'Schema Tipi', 'wp-seo-master' ); ?></label><br>
				<select name="_wpsm_schema_type" id="wpsm_schema_type">
					<option value="Article" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'Article' ); ?>><?php esc_html_e( 'Article', 'wp-seo-master' ); ?></option>
					<option value="BlogPosting" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'BlogPosting' ); ?>><?php esc_html_e( 'Blog Posting', 'wp-seo-master' ); ?></option>
					<option value="NewsArticle" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'NewsArticle' ); ?>><?php esc_html_e( 'News Article', 'wp-seo-master' ); ?></option>
					<option value="Product" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'Product' ); ?>><?php esc_html_e( 'Product', 'wp-seo-master' ); ?></option>
					<option value="LocalBusiness" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'LocalBusiness' ); ?>><?php esc_html_e( 'Local Business', 'wp-seo-master' ); ?></option>
					<option value="FAQPage" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'FAQPage' ); ?>><?php esc_html_e( 'FAQ Page', 'wp-seo-master' ); ?></option>
					<option value="HowTo" <?php selected( get_post_meta( $post_id, '_wpsm_schema_type', true ), 'HowTo' ); ?>><?php esc_html_e( 'How To', 'wp-seo-master' ); ?></option>
				</select>
			</p>
			<div id="wpsm-schema-fields">
				<?php
				// Dinamik schema alanları JS ile yüklenecek
				$current_type = get_post_meta( $post_id, '_wpsm_schema_type', true );
				if ( ! empty( $current_type ) ) {
					// Şablon için boş bir örnek oluştur
					$metabox = new \WPSM\Admin\Metabox();
					echo $metabox->get_schema_fields_by_type( $current_type );
				}
				?>
			</div>
		</div>

		<div class="wpsm-tab-content" id="wpsm-tab-advanced">
			<p>
				<label for="wpsm_canonical"><?php esc_html_e( 'Kanonik URL', 'wp-seo-master' ); ?></label><br>
				<input type="url" name="_wpsm_canonical" id="wpsm_canonical" value="<?php echo esc_url( get_post_meta( $post_id, '_wpsm_canonical', true ) ); ?>" style="width:100%;">
			</p>
			<p>
				<label for="wpsm_breadcrumb_title"><?php esc_html_e( 'Breadcrumb Başlığı', 'wp-seo-master' ); ?></label><br>
				<input type="text" name="_wpsm_breadcrumb_title" id="wpsm_breadcrumb_title" value="<?php echo esc_attr( get_post_meta( $post_id, '_wpsm_breadcrumb_title', true ) ); ?>" style="width:100%;">
			</p>
			<p>
				<fieldset>
					<legend><?php esc_html_e( 'Robots', 'wp-seo-master' ); ?></legend>
					<label><input type="checkbox" name="_wpsm_robots[]" value="noindex" <?php checked( in_array( 'noindex', (array) get_post_meta( $post_id, '_wpsm_robots', true ) ) ); ?>> <?php esc_html_e( 'No Index', 'wp-seo-master' ); ?></label><br>
					<label><input type="checkbox" name="_wpsm_robots[]" value="nofollow" <?php checked( in_array( 'nofollow', (array) get_post_meta( $post_id, '_wpsm_robots', true ) ) ); ?>> <?php esc_html_e( 'No Follow', 'wp-seo-master' ); ?></label><br>
					<label><input type="checkbox" name="_wpsm_robots[]" value="noarchive" <?php checked( in_array( 'noarchive', (array) get_post_meta( $post_id, '_wpsm_robots', true ) ) ); ?>> <?php esc_html_e( 'No Archive', 'wp-seo-master' ); ?></label>
				</fieldset>
			</p>
		</div>
	</div>
</div>
