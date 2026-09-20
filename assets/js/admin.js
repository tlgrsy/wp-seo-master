jQuery(document).ready(function($) {
	// Admin panel JavaScript
	
	// Media uploader için genel fonksiyon
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
});
