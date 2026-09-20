# WP SEO MASTER — TEST PROMPTU

Az önce ürettiğin WP SEO Master eklentisini şimdi test edeceğiz.
Aşağıdaki 8 test senaryosunu sırayla uygula ve her birinin sonucunu raporla.

---

## TEST 1 — Statik Kod Analizi

1. Tüm PHP dosyalarında `?>` kapanış etiketi var mı? (Olmamalı)
2. Tüm PHP dosyalarında `ABSPATH` kontrolü var mı? (Olmalı)
3. Tüm `$_POST` / `$_GET` kullanımlarında sanitize var mı?
4. Tüm `echo` / `print` çıktılarında escape fonksiyonu var mı?
5. Tüm form işlemlerinde nonce var mı?
6. Tüm SQL sorguları `$wpdb->prepare` ile mi?
7. `wpsm_` prefix'siz global fonksiyon/sınıf var mı?

---

## TEST 2 — Autoloader Testi

Her sınıf için şu dönüşümün doğru çalıştığını doğrula:

- `WPSM\Admin\Class_Settings` → `includes/Admin/class-settings.php`
- `WPSM\Schema\Class_FAQ_Schema` → `includes/Schema/class-faq-schema.php`
- `WPSM\Frontend\Class_OpenGraph` → `includes/Frontend/class-opengraph.php`

Tutarsızlık varsa düzelt.

---

## TEST 3 — WordPress Entegrasyon Testi (sanal)

1. Eklenti aktive edildiğinde `wpsm_settings` option'ı oluşuyor mu?
2. Admin menü `manage_options` capability'si ile korunuyor mu?
3. `add_meta_box` sadece `post` ve `page`'de mi görünüyor?
4. `register_post_meta` ile REST API'ye açılan meta'lar var mı?
5. `add_rewrite_rule` ile sitemap endpoint'i doğru mu?

---

## TEST 4 — Şema Doğrulama (JSON-LD)

Aşağıdaki senaryolar için üretilecek JSON-LD'yi simüle et ve
schema.org kurallarına uygunluğunu kontrol et:

1. Blog yazısı → Article şeması
2. FAQ içeren sayfa → FAQPage şeması
3. WooCommerce ürünü → Product şeması
4. İletişim sayfası → LocalBusiness şeması

Her biri için zorunlu alanlar eksik mi? (@context, @type, @id, name/headline)

---

## TEST 5 — Çakışma Testi

Yoast, AIOSEO, Rank Math sabitleri tanımlı senaryoyu simüle et:

- Admin'de uyarı gösteriliyor mu?
- Frontend meta çıktıları devre dışı kalıyor mu?
- Schema çıktısı bastırılıyor mu?

---

## TEST 6 — Edge Case Testleri

1. Boş focus keyword ile analiz → hata vermemeli
2. 5000+ kelimelik içerik → timeout olmamalı
3. Multisite → `get_sites()` döngüsü doğru mu?
4. PHP 8.2 → deprecated uyarısı var mı?
5. Sitemap 1000+ URL → chunk'lama var mı?

---

## TEST 7 — Güvenlik Denetimi

1. XSS açığı: `_wpsm_title` meta'sına `<script>` yazılırsa escape ediliyor mu?
2. CSRF: Nonce olmadan form gönderimi engelleniyor mu?
3. SQL Injection: `$wpdb->prepare` kullanılmayan sorgu var mı?
4. Yetki yükseltme: `edit_posts` yetkisi olan kullanıcı `manage_options`
   gerektiren işlemi yapabiliyor mu?

---

## TEST 8 — Performans

1. N+1 sorgu problemi var mı? (`get_post_meta` döngü içinde mi?)
2. `Options` sınıfında `static $cache` çalışıyor mu?
3. Sitemap transient cache doğru mu?
4. `autoload` gereksiz sınıf yüklüyor mu?

---

## 📊 ÇIKTI FORMATI

Her test için aşağıdaki şablonu kullan:

---

### TEST X — [Ad]

**Durum:** ✅ GEÇTİ / ⚠️ UYARI / ❌ BAŞARISIZ

**Bulunan Sorunlar:**

- [varsa liste]

**Düzeltmeler:**

- [varsa tam kod]

**Sonuç:** [kısa özet]

---

Sonunda aşağıdaki genel raporu ver:

---

## 📋 GENEL RAPOR

- Toplam test: 8
- Geçen: X
- Uyarı: Y
- Başarısız: Z

## 🔧 KRİTİK DÜZELTMELER (varsa)

Dosya: [yol/dosya.php]
Satır: [X-Y]

Eski kod:

```php
[eski kod]
```

Yeni kod:

```php
[yeni kod]
```

## 🚀 SONUÇ

Eklenti üretime hazır mı? (Evet/Hayır)

Eksikler:

1. ...
2. ...

Sonraki adımlar:

1. Local WP'ye kur
2. Eklentiyi aktive et
3. Admin menüsünden ayarları yapılandır
4. Bir test yazısı oluştur ve metabox'u kontrol et

---

Şimdi testleri uygula.
