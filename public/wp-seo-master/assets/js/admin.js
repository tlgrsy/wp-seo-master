/**
 * WP SEO Master - Admin JavaScript
 *
 * Metabox işlevleri:
 * - Karakter sayacı (Title: 60, Description: 160)
 * - Tab geçişleri
 * - Media uploader (wp.media)
 * - Schema tipi değişince AJAX ile alanları getir
 * - SERP önizleme güncelleme
 * - Schema repeater (FAQ soruları, HowTo adımları)
 *
 * @package WPSM
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * WP SEO Master Admin Modülü
     */
    var WPSM_Admin = {

        /**
         * Başlat
         */
        init: function () {
            this.initCharCounters();
            this.initTabs();
            this.initMediaUploader();
            this.initSchemaTypeChange();
            this.initSchemaRepeater();
            this.initSerpPreview();
        },

        /**
         * Karakter Sayacı
         *
         * Input alanlarındaki karakter sayısını gösterir.
         * Renk kodları:
         * - Yeşil: Önerilen aralıkta
         * - Sarı: Uyarı (yaklaşıyor)
         * - Kırmızı: Aşıldı
         */
        initCharCounters: function () {
            var self = this;

            // Her karakter sayacı için
            $('.wpsm-char-counter').each(function () {
                var $counter = $(this);
                var targetId = $counter.data('target');
                var maxChars = parseInt($counter.data('max'), 10);
                var $input = $('#' + targetId);

                if (!$input.length) return;

                // İlk yükleme
                self.updateCharCounter($input, $counter, maxChars);

                // Input değiştiğinde güncelle
                $input.on('input keyup', function () {
                    self.updateCharCounter($(this), $counter, maxChars);
                });
            });
        },

        /**
         * Karakter sayacını güncelle
         *
         * @param {jQuery} $input Input elemanı
         * @param {jQuery} $counter Sayaç elemanı
         * @param {number} maxChars Maksimum karakter
         */
        updateCharCounter: function ($input, $counter, maxChars) {
            var currentLength = $input.val().length;
            var $countSpan = $counter.find('.wpsm-char-count');

            // Sayıyı güncelle
            $countSpan.text(currentLength);

            // Renk sınıflarını kaldır
            $counter.removeClass('wpsm-char-green wpsm-char-yellow wpsm-char-red');

            // Renk belirle
            var percentage = (currentLength / maxChars) * 100;

            if (currentLength === 0) {
                // Boş - gri
                $counter.addClass('wpsm-char-gray');
            } else if (percentage <= 80) {
                // İyi - yeşil
                $counter.addClass('wpsm-char-green');
            } else if (percentage <= 100) {
                // Uyarı - sarı
                $counter.addClass('wpsm-char-yellow');
            } else {
                // Aşıldı - kırmızı
                $counter.addClass('wpsm-char-red');
            }
        },

        /**
         * Tab Geçişleri
         *
         * Tab butonlarına tıklanınca ilgili paneli gösterir.
         */
        initTabs: function () {
            $(document).on('click', '.wpsm-tab-btn', function (e) {
                e.preventDefault();

                var $btn = $(this);
                var tabId = $btn.data('tab');

                // Aktif buton
                $('.wpsm-tab-btn').removeClass('active');
                $btn.addClass('active');

                // Aktif panel
                $('.wpsm-tab-panel').removeClass('active');
                $('#wpsm-tab-' + tabId).addClass('active');
            });
        },

        /**
         * Media Uploader
         *
         * WordPress Media Library ile görsel seçimi.
         * wp.media API kullanır.
         */
        initMediaUploader: function () {
            var mediaFrame;

            // Görsel seç butonu
            $(document).on('click', '.wpsm-media-upload-btn', function (e) {
                e.preventDefault();

                var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
                var $input = $wrapper.find('input[type="hidden"]');
                var $preview = $wrapper.find('.wpsm-media-preview img');
                var $previewWrap = $wrapper.find('.wpsm-media-preview');
                var $removeBtn = $wrapper.find('.wpsm-media-remove-btn');

                // Media frame zaten varsa aç
                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }

                // Yeni media frame oluştur
                mediaFrame = wp.media({
                    title: wpsmAdmin.i18n.selectImage || 'Görsel Seç',
                    button: {
                        text: wpsmAdmin.i18n.useImage || 'Kullan'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                // Görsel seçildiğinde
                mediaFrame.on('select', function () {
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    var imageUrl = attachment.sizes && attachment.sizes.medium
                        ? attachment.sizes.medium.url
                        : attachment.url;

                    // Değerleri güncelle
                    $input.val(imageUrl);
                    $preview.attr('src', imageUrl);
                    $previewWrap.show();
                    $removeBtn.show();
                });

                mediaFrame.open();
            });

            // Görsel kaldır butonu
            $(document).on('click', '.wpsm-media-remove-btn', function (e) {
                e.preventDefault();

                var $wrapper = $(this).closest('.wpsm-media-upload-wrapper');
                var $input = $wrapper.find('input[type="hidden"]');
                var $preview = $wrapper.find('.wpsm-media-preview');
                var $removeBtn = $(this);

                // Değerleri temizle
                $input.val('');
                $preview.hide();
                $removeBtn.hide();
            });
        },

        /**
         * Schema Tipi Değişimi (AJAX)
         *
         * Schema tipi select'i değiştiğinde AJAX ile ilgili alanları yükler.
         */
        initSchemaTypeChange: function () {
            var self = this;

            $('#wpsm-schema-type').on('change', function () {
                var schemaType = $(this).val();
                var $fieldsWrapper = $('#wpsm-schema-fields');
                var $dynamicFields = $fieldsWrapper.find('.wpsm-schema-dynamic-fields');
                var $loader = $fieldsWrapper.find('.wpsm-schema-loader');
                var $empty = $fieldsWrapper.find('.wpsm-schema-empty');

                // "none" seçildiyse alanları temizle
                if (schemaType === 'none' || schemaType === '') {
                    $dynamicFields.empty().hide();
                    $empty.show();
                    return;
                }

                // Empty mesajını gizle, loader'ı göster
                $empty.hide();
                $loader.show();

                // AJAX isteği
                $.ajax({
                    url: wpsmAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wpsm_load_schema_fields',
                        schema_type: schemaType,
                        nonce: wpsmAdmin.nonce
                    },
                    success: function (response) {
                        $loader.hide();

                        if (response.success) {
                            $dynamicFields.html(response.data.html).show();
                            // Yeni alanları data attribute'a kaydet
                            $dynamicFields.attr('data-schema-type', schemaType);
                        } else {
                            $dynamicFields.html('<p class="wpsm-error">' + (response.data.message || 'Hata oluştu.') + '</p>').show();
                        }
                    },
                    error: function () {
                        $loader.hide();
                        $dynamicFields.html('<p class="wpsm-error">Bağlantı hatası. Lütfen tekrar deneyin.</p>').show();
                    }
                });
            });
        },

        /**
         * Schema Repeater
         *
         * FAQ soruları ve HowTo adımları için tekrarlayan alan yönetimi.
         */
        initSchemaRepeater: function () {
            // Eleman ekle
            $(document).on('click', '.wpsm-schema-add-item', function (e) {
                e.preventDefault();

                var $container = $(this).prev('.wpsm-schema-repeater');
                var type = $(this).data('type');
                var index = $container.find('.wpsm-schema-repeater-item').length;

                var html = '';

                if (type === 'faq') {
                    html = '<div class="wpsm-schema-repeater-item">' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Soru ' + (index + 1) + '</label>' +
                        '<input type="text" name="wpsm_schema_data[questions][' + index + '][question]" class="wpsm-input" />' +
                        '</div>' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Cevap</label>' +
                        '<textarea name="wpsm_schema_data[questions][' + index + '][answer]" class="wpsm-textarea" rows="3"></textarea>' +
                        '</div>' +
                        '<button type="button" class="button wpsm-schema-remove-item">Kaldır</button>' +
                        '</div>';
                } else if (type === 'howto') {
                    html = '<div class="wpsm-schema-repeater-item">' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Adım ' + (index + 1) + ' - Başlık</label>' +
                        '<input type="text" name="wpsm_schema_data[steps][' + index + '][name]" class="wpsm-input" />' +
                        '</div>' +
                        '<div class="wpsm-schema-field">' +
                        '<label>Açıklama</label>' +
                        '<textarea name="wpsm_schema_data[steps][' + index + '][text]" class="wpsm-textarea" rows="2"></textarea>' +
                        '</div>' +
                        '<button type="button" class="button wpsm-schema-remove-item">Kaldır</button>' +
                        '</div>';
                }

                $container.append(html);
            });

            // Eleman kaldır
            $(document).on('click', '.wpsm-schema-remove-item', function (e) {
                e.preventDefault();
                $(this).closest('.wpsm-schema-repeater-item').remove();

                // En az bir eleman kalmalı
                var $container = $(this).closest('.wpsm-schema-repeater');
                if ($container.find('.wpsm-schema-repeater-item').length === 0) {
                    // Boş bir eleman ekle
                    $container.parent().find('.wpsm-schema-add-item').trigger('click');
                }
            });
        },

        /**
         * SERP Önizleme
         *
         * SEO başlığı ve meta açıklaması değiştikçe
         * Google arama sonucu önizlemesini günceller.
         */
        initSerpPreview: function () {
            // Title önizleme
            $('#wpsm-title').on('input keyup', function () {
                var value = $(this).val();
                var $preview = $('#wpsm-serp-title-preview');

                if (value) {
                    $preview.text(value);
                } else {
                    // Placeholder'daki değeri kullan
                    $preview.text($(this).attr('placeholder') || 'Sayfa Başlığı');
                }
            });

            // Description önizleme
            $('#wpsm-description').on('input keyup', function () {
                var value = $(this).val();
                var $preview = $('#wpsm-serp-desc-preview');

                if (value) {
                    $preview.text(value);
                } else {
                    $preview.text('Meta açıklamanız burada görünecek...');
                }
            });
        }
    };

    /**
     * DOM Ready
     */
    $(document).ready(function () {
        // Sadece metabox varsa başlat
        if ($('#wpsm-seo-metabox').length) {
            WPSM_Admin.init();
        }
    });

})(jQuery);
