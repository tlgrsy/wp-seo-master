<?php
/**
 * Metabox Sınıfı
 * 
 * Post editöründe SEO ayarları için metabox oluşturur.
 *
 * @package WPSM
 * @since 1.0.0
 */

namespace WPSM\Admin;

use WPSM\Options;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Metabox Sınıfı
 * 
 * Post editöründe SEO ayarları için metabox oluşturur.
 *
 * @since 1.0.0
 */
class Metabox {

	/**
	 * Options sınıfı örneği
	 *
	 * @since 1.0.0
	 * @var Options
	 */
	private $options;

	/**
	 * Sınıfı başlatır
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		$this->options = Options::get_instance();
		
		add_action( 'add_meta_boxes', array( $this, 'add_seo_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ) );
		
		// REST API'ye meta alanlarını aç
		$this->register_meta_fields();
		
		// AJAX handler'lar
		add_action( 'wp_ajax_wpsm_get_schema_fields', array( $this, 'get_schema_fields_ajax' ) );
		add_action( 'wp_ajax_wpsm_analyze_content', array( $this, 'analyze_content_ajax' ) );
	}

	/**
	 * SEO metabox'ı ekle
	 *
	 * @since 1.0.0
	 * @param string $post_type Post tipi
	 * @return void
	 */
	public function add_seo_metabox( $post_type ) {
		// Public post typelarda göster
		$post_types = get_post_types( array( 'public' => true ) );
		unset( $post_types['attachment'] ); // Attachment hariç
		
		if ( in_array( $post_type, $post_types ) ) {
			add_meta_box(
				'wpsm_seo_metabox',
				__( 'WP SEO Master', 'wp-seo-master' ),
				array( $this, 'render_metabox' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Metabox'ı render et
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Current post
	 * @return void
	 */
	public function render_metabox( $post ) {
		wp_nonce_field( 'wpsm_metabox_nonce', 'wpsm_metabox_nonce' );
		include WPSM_PATH . 'includes/Admin/views/metabox.php';
	}

	/**
	 * Metabox verilerini kaydet
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID
	 * @return void
	 */
	public function save_metabox( $post_id ) {
		// Otomatik kaydetmeleri atla
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Revision kontrolü
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Capability kontrolü
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Nonce kontrolü
		if ( ! isset( $_POST['wpsm_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpsm_metabox_nonce'] ) ), 'wpsm_metabox_nonce' ) ) {
			return;
		}

		// Meta verileri kaydet
		$this->save_meta_fields( $post_id );
	}

	/**
	 * Meta alanlarını kaydet
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID
	 * @return void
	 */
	private function save_meta_fields( $post_id ) {
		$meta_fields = array(
			'_wpsm_title'             => 'text',
			'_wpsm_description'       => 'textarea',
			'_wpsm_focus_keyword'     => 'text',
			'_wpsm_canonical'         => 'url',
			'_wpsm_og_title'          => 'text',
			'_wpsm_og_description'    => 'textarea',
			'_wpsm_og_image'          => 'url',
			'_wpsm_twitter_title'     => 'text',
			'_wpsm_twitter_description' => 'textarea',
			'_wpsm_twitter_image'     => 'url',
			'_wpsm_schema_type'       => 'text',
			'_wpsm_schema_data'       => 'json',
			'_wpsm_robots'            => 'array',
			'_wpsm_breadcrumb_title'  => 'text',
		);

		foreach ( $meta_fields as $meta_key => $type ) {
			if ( isset( $_POST[ $meta_key ] ) ) {
				$value = wp_unslash( $_POST[ $meta_key ] );
				
				switch ( $type ) {
					case 'text':
						$sanitized_value = sanitize_text_field( $value );
						break;
					case 'textarea':
						$sanitized_value = sanitize_textarea_field( $value );
						break;
					case 'url':
						$sanitized_value = esc_url_raw( $value );
						break;
					case 'json':
						// JSON validasyonu
						json_decode( $value );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							$sanitized_value = wp_kses_post( $value );
						} else {
							$sanitized_value = '';
						}
						break;
					case 'array':
						$sanitized_value = array_map( 'sanitize_text_field', (array) $value );
						break;
					default:
						$sanitized_value = sanitize_text_field( $value );
						break;
				}
				
				update_post_meta( $post_id, $meta_key, $sanitized_value );
			} else {
				// Alan yoksa sil
				delete_post_meta( $post_id, $meta_key );
			}
		}
	}

	/**
	 * Meta alanlarını REST API'ye aç
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_meta_fields() {
		$args = array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
			'auth_callback' => function( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		);

		$fields = array(
			'_wpsm_title',
			'_wpsm_description',
			'_wpsm_focus_keyword',
			'_wpsm_canonical',
			'_wpsm_og_title',
			'_wpsm_og_description',
			'_wpsm_og_image',
			'_wpsm_twitter_title',
			'_wpsm_twitter_description',
			'_wpsm_twitter_image',
			'_wpsm_schema_type',
			'_wpsm_schema_data',
			'_wpsm_breadcrumb_title',
		);

		foreach ( $fields as $field ) {
			register_post_meta( '', $field, $args );
		}

		// Robots alanı özel (dizi)
		register_post_meta( '', '_wpsm_robots', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'array',
			'auth_callback' => function( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );
	}

	/**
	 * Schema alanlarını AJAX ile getir
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_schema_fields_ajax() {
		// Capability kontrolü
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bu işlemi yapmak için yetkiniz yok.', 'wp-seo-master' ) );
		}

		// Nonce kontrolü
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpsm_schema_nonce' ) ) {
			wp_die( esc_html__( 'Geçersiz nonce.', 'wp-seo-master' ) );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		
		$fields = $this->get_schema_fields_by_type( $type );
		
		wp_send_json_success( $fields );
	}

	/**
	 * Schema tipine göre alanları getir
	 *
	 * @since 1.0.0
	 * @param string $type Schema tipi
	 * @return string HTML alanlar
	 */
	private function get_schema_fields_by_type( $type ) {
		ob_start();

		switch ( $type ) {
			case 'FAQPage':
				?>
				<div class="wpsm-schema-field-group">
					<h4><?php esc_html_e( 'SSS Soruları', 'wp-seo-master' ); ?></h4>
					<div id="wpsm-faq-container">
						<?php
						$faq_data = json_decode( get_post_meta( get_the_ID(), '_wpsm_schema_data', true ), true );
						$faqs = is_array( $faq_data ) ? $faq_data : array();
						
						foreach ( $faqs as $index => $faq ) {
							?>
							<div class="wpsm-faq-item">
								<p>
									<label><?php esc_html_e( 'Soru', 'wp-seo-master' ); ?>:</label><br>
									<input type="text" name="wpsm_faq_question[]" value="<?php echo esc_attr( $faq['question'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Soruyu girin...', 'wp-seo-master' ); ?>" style="width:100%;">
								</p>
								<p>
									<label><?php esc_html_e( 'Cevap', 'wp-seo-master' ); ?>:</label><br>
									<textarea name="wpsm_faq_answer[]" placeholder="<?php esc_attr_e( 'Cevabı girin...', 'wp-seo-master' ); ?>" style="width:100%;"><?php echo esc_textarea( $faq['answer'] ?? '' ); ?></textarea>
								</p>
								<button type="button" class="button button-link-delete remove-faq"><?php esc_html_e( 'Sil', 'wp-seo-master' ); ?></button>
							</div>
							<?php
						}
						?>
					</div>
					<button type="button" class="button button-secondary" id="add-faq"><?php esc_html_e( '+ Yeni Soru Ekle', 'wp-seo-master' ); ?></button>
				</div>
				<?php
				break;
				
			case 'HowTo':
				?>
				<div class="wpsm-schema-field-group">
					<h4><?php esc_html_e( 'Nasıl Yapılır Adımları', 'wp-seo-master' ); ?></h4>
					<div id="wpsm-howto-container">
						<?php
						$howto_data = json_decode( get_post_meta( get_the_ID(), '_wpsm_schema_data', true ), true );
						$steps = is_array( $howto_data ) ? $howto_data : array();
						
						foreach ( $steps as $index => $step ) {
							?>
							<div class="wpsm-howto-step">
								<p>
									<label><?php esc_html_e( 'Adım Başlığı', 'wp-seo-master' ); ?>:</label><br>
									<input type="text" name="wpsm_howto_name[]" value="<?php echo esc_attr( $step['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Adım başlığını girin...', 'wp-seo-master' ); ?>" style="width:100%;">
								</p>
								<p>
									<label><?php esc_html_e( 'Açıklama', 'wp-seo-master' ); ?>:</label><br>
									<textarea name="wpsm_howto_text[]" placeholder="<?php esc_attr_e( 'Açıklamayı girin...', 'wp-seo-master' ); ?>" style="width:100%;"><?php echo esc_textarea( $step['text'] ?? '' ); ?></textarea>
								</p>
								<button type="button" class="button button-link-delete remove-step"><?php esc_html_e( 'Sil', 'wp-seo-master' ); ?></button>
							</div>
							<?php
						}
						?>
					</div>
					<button type="button" class="button button-secondary" id="add-step"><?php esc_html_e( '+ Yeni Adım Ekle', 'wp-seo-master' ); ?></button>
				</div>
				<?php
				break;
				
			case 'Product':
				?>
				<div class="wpsm-schema-field-group">
					<h4><?php esc_html_e( 'Ürün Bilgileri', 'wp-seo-master' ); ?></h4>
					<p>
						<label><?php esc_html_e( 'SKU', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_product_sku" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_product_sku', true ) ); ?>" style="width:100%;">
					</p>
					<p>
						<label><?php esc_html_e( 'Marka', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_product_brand" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_product_brand', true ) ); ?>" style="width:100%;">
					</p>
					<p>
						<label><?php esc_html_e( 'Fiyat', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_product_price" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_product_price', true ) ); ?>" style="width:100%;">
					</p>
					<p>
						<label><?php esc_html_e( 'Para Birimi', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_product_currency" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_product_currency', true ) ?: 'TRY' ); ?>" style="width:100%;">
					</p>
				</div>
				<?php
				break;
				
			case 'LocalBusiness':
				?>
				<div class="wpsm-schema-field-group">
					<h4><?php esc_html_e( 'Yerel İşletme Bilgileri', 'wp-seo-master' ); ?></h4>
					<p>
						<label><?php esc_html_e( 'Telefon Numarası', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_local_phone" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_local_phone', true ) ); ?>" style="width:100%;">
					</p>
					<p>
						<label><?php esc_html_e( 'Adres', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_local_address" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_local_address', true ) ); ?>" style="width:100%;">
					</p>
					<p>
						<label><?php esc_html_e( 'Açılış Saatleri', 'wp-seo-master' ); ?>:</label><br>
						<input type="text" name="wpsm_local_opening_hours" value="<?php echo esc_attr( get_post_meta( get_the_ID(), '_wpsm_local_opening_hours', true ) ); ?>" style="width:100%;">
					</p>
				</div>
				<?php
				break;
				
			default:
				// Article, BlogPosting, NewsArticle için özel alan yok
				echo '<p>' . esc_html__( 'Bu schema tipi için özel alan bulunmamaktadır.', 'wp-seo-master' ) . '</p>';
				break;
		}

		return ob_get_clean();
	}

	/**
	 * İçerik analiz AJAX handler
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function analyze_content_ajax() {
		// Capability kontrolü
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bu işlemi yapmak için yetkiniz yok.', 'wp-seo-master' ) );
		}

		// Nonce kontrolü
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpsm_analyze_nonce' ) ) {
			wp_die( esc_html__( 'Geçersiz nonce.', 'wp-seo-master' ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$focus_keyword = sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ?? '' ) );

		if ( ! $post_id || ! $focus_keyword ) {
			wp_send_json_error( array( 'message' => __( 'Geçersiz parametreler.', 'wp-seo-master' ) ) );
		}

		// Analyzer sınıfını kullanarak analiz yap
		if ( class_exists( 'WPSM\Analyzer\Content_Analyzer' ) ) {
			$analyzer = new \WPSM\Analyzer\Content_Analyzer();
			$results = $analyzer->analyze( $post_id, $focus_keyword );
			
			wp_send_json_success( $results );
		} else {
			wp_send_json_error( array( 'message' => __( 'Analiz modülü yüklenemedi.', 'wp-seo-master' ) ) );
		}
	}
}
