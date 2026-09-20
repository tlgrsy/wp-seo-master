jQuery(document).ready(function($) {
	// Sekme geçişleri
	$('.wpsm-tab-nav a').on('click', function(e) {
		e.preventDefault();
		
		var target = $(this).attr('href');
		
		// Aktif sekme değiştir
		$(this).parent().siblings().removeClass('active');
		$(this).parent().addClass('active');
		
		// Aktif içerik değiştir
		$('.wpsm-tab-content').removeClass('active');
		$(target).addClass('active');
	});
	
	// Karakter sayaçları
	$('#wpsm_title, #wpsm_description').on('input', function() {
		var $this = $(this);
		var max = parseInt($this.siblings('.wpsm-char-count').data('max'));
		var length = $this.val().length;
		var $count = $this.siblings('.wpsm-char-count');
		
		$count.text(length + '/' + max);
		
		// Renk kodlaması
		if (length <= max * 0.7) {
			$count.removeClass('char-good char-warning char-error').addClass('char-error');
		} else if (length <= max) {
			$count.removeClass('char-good char-warning char-error').addClass('char-warning');
		} else {
			$count.removeClass('char-good char-warning char-error').addClass('char-good');
		}
	});
	
	// Medya uploader butonları
	$(document).on('click', '.upload-image-button', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var targetId = $button.data('target');
		var $targetInput = $('#' + targetId);
		
		var custom_uploader = wp.media({
			title: 'Resim Seç',
			button: {
				text: 'Seç'
			},
			multiple: false
		});
		
		custom_uploader.on('select', function() {
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			$targetInput.val(attachment.url);
		});
		
		custom_uploader.open();
	});
	
	// Schema tipi değiştiğinde alanları yükle
	$('#wpsm_schema_type').on('change', function() {
		var type = $(this).val();
		var postId = $('#post_ID').val();
		
		if (!postId) return;
		
		$.post(ajaxurl, {
			action: 'wpsm_get_schema_fields',
			type: type,
			post_id: postId,
			nonce: $('#wpsm_metabox_nonce').val()
		}, function(response) {
			if (response.success) {
				$('#wpsm-schema-fields').html(response.data);
			}
		});
	});
	
	// SSS ekleme
	$(document).on('click', '#add-faq', function() {
		var faqHtml = `
			<div class="wpsm-faq-item">
				<p>
					<label>Soru:</label><br>
					<input type="text" name="wpsm_faq_question[]" placeholder="Soruyu girin..." style="width:100%;">
				</p>
				<p>
					<label>Cevap:</label><br>
					<textarea name="wpsm_faq_answer[]" placeholder="Cevabı girin..." style="width:100%;"></textarea>
				</p>
				<button type="button" class="button button-link-delete remove-faq">Sil</button>
			</div>
		`;
		$('#wpsm-faq-container').append(faqHtml);
	});
	
	// SSS silme
	$(document).on('click', '.remove-faq', function() {
		$(this).closest('.wpsm-faq-item').remove();
	});
	
	// HowTo adımı ekleme
	$(document).on('click', '#add-step', function() {
		var stepHtml = `
			<div class="wpsm-howto-step">
				<p>
					<label>Adım Başlığı:</label><br>
					<input type="text" name="wpsm_howto_name[]" placeholder="Adım başlığını girin..." style="width:100%;">
				</p>
				<p>
					<label>Açıklama:</label><br>
					<textarea name="wpsm_howto_text[]" placeholder="Açıklamayı girin..." style="width:100%;"></textarea>
				</p>
				<button type="button" class="button button-link-delete remove-step">Sil</button>
			</div>
		`;
		$('#wpsm-howto-container').append(stepHtml);
	});
	
	// HowTo adımı silme
	$(document).on('click', '.remove-step', function() {
		$(this).closest('.wpsm-howto-step').remove();
	});
	
	// İçerik analiz et butonu
	$('#wpsm-analyze-btn').on('click', function() {
		var postId = $('#post_ID').val();
		var focusKeyword = $('#wpsm_focus_keyword').val();
		
		if (!postId || !focusKeyword) {
			alert('Lütfen odak kelimesini girin.');
			return;
		}
		
		$(this).prop('disabled', true).text('Analiz Ediliyor...');
		
		$.post(ajaxurl, {
			action: 'wpsm_analyze_content',
			post_id: postId,
			focus_keyword: focusKeyword,
			nonce: $('#wpsm_metabox_nonce').val()
		}, function(response) {
			$('#wpsm-analyze-btn').prop('disabled', false).text('İçerik Analiz Et');
			
			if (response.success) {
				var result = response.data;
				var html = '<div class="wpsm-analysis-result">';
				html += '<h4>Analiz Sonuçları</h4>';
				html += '<div class="wpsm-score-circle" data-score="' + result.score + '">';
				html += '<span class="wpsm-score">' + result.score + '</span>';
				html += '</div>';
				html += '<ul>';
				$.each(result.checks, function(index, check) {
					var status = check.passed ? 'passed' : 'failed';
					html += '<li class="' + status + '">' + check.message + '</li>';
				});
				html += '</ul>';
				html += '</div>';
				
				$('#wpsm-analysis-results').html(html);
			} else {
				$('#wpsm-analysis-results').html('<div class="error">' + response.data.message + '</div>');
			}
		});
	});
	
	// Başlangıç karakter sayaçları
	$('#wpsm_title, #wpsm_description').trigger('input');
});
