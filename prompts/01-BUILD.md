# WP SEO MASTER — TAM PROJE (TEK SEFERDE ÜRET)

## 🎯 GÖREV

Sen kıdemli bir WordPress eklenti geliştiricisisin. Aşağıdaki tüm modülleri 
**tek bir oturumda, sırayla ve eksiksiz** üreteceksin. Her dosyayı tam içerik 
olarak ver, parça parça değil. Onay bekleme, soru sorma — sadece üret.

**Eklenti:** WP SEO Master  
**Amaç:** Yoast / All in One SEO'ya benzer, ancak Schema.org dahil TÜM 
özellikleri ücretsiz olan WordPress SEO eklentisi.  
**Namespace:** `WPSM\`  
**Prefix:** `wpsm_`  
**Text Domain:** `wp-seo-master`  
**Versiyon:** 1.0.0  
**Lisans:** GPL-2.0-or-later

---

## ⚙️ KODLAMA KURALLARI (HER DOSYADA ZORUNLU)

- WordPress Coding Standards (WPCS) uyumlu
- PHP 7.4+ uyumlu, PHP 8.2'de hatasız
- Composer YOK — `spl_autoload_register` ile PSR-4 benzeri autoloader
- Sınıf dosyaları: `class-isim-seklinde.php`, sınıf adı: `Class_Isim_Seklinde`
- Tüm fonksiyonlar `wpsm_` prefix'li
- Tüm girdiler sanitize: `sanitize_text_field`, `sanitize_email`, `esc_url_raw`, `absint`, `wp_kses_post`
- Tüm çıktılar escape: `esc_html`, `esc_attr`, `esc_url`, `wp_json_encode`
- Tüm form işlemleri: `wp_nonce_field` + `wp_verify_nonce`
- Tüm admin işlemleri: `current_user_can('manage_options')` veya `current_user_can('edit_post', $id)`
- SQL sadece `$wpdb->prepare` ile
- Her PHP dosyasının başında: `if ( ! defined( 'ABSPATH' ) ) exit;`
- Dosya sonunda `?>` KULLANMA
- Tüm metinler `__()`, `_e()`, `esc_html__()` ile sarılı, text domain: `wp-seo-master`
- Her sınıfın ve metodun üstünde PHPDoc (Türkçe açıklama)
- Yorumlar Türkçe

**Çakışma önleme:** `WPSEO_VERSION`, `AIOSEO_VERSION`, `RANK_MATH_VERSION`, 
`SEOPRESS_VERSION` sabitlerinden biri tanımlıysa admin uyarısı göster ve 
frontend meta çıktılarını devre dışı bırak.

---

## 📁 DOSYA YAPISI (TAM LİSTE)

```
wp-seo-master/
├── wp-seo-master.php
├── uninstall.php
├── readme.txt
├── includes/
│   ├── class-autoloader.php
│   ├── class-plugin.php
│   ├── class-installer.php
│   ├── class-options.php
│   ├── class-i18n.php
│   ├── Admin/
│   │   ├── class-admin-menu.php
│   │   ├── class-settings.php
│   │   ├── class-metabox.php
│   │   └── views/
│   │       ├── settings-general.php
│   │       ├── settings-schema.php
│   │       ├── settings-social.php
│   │       ├── settings-sitemap.php
│   │       └── metabox.php
│   ├── Frontend/
│   │   ├── class-meta-tags.php
│   │   ├── class-opengraph.php
│   │   ├── class-twitter-cards.php
│   │   ├── class-canonical.php
│   │   ├── class-robots.php
│   │   └── class-breadcrumbs.php
│   ├── Schema/
│   │   ├── class-schema-manager.php
│   │   ├── class-article-schema.php
│   │   ├── class-faq-schema.php
│   │   ├── class-howto-schema.php
│   │   ├── class-product-schema.php
│   │   ├── class-localbusiness-schema.php
│   │   └── class-breadcrumb-schema.php
│   ├── Sitemap/
│   │   ├── class-sitemap-generator.php
│   │   └── class-sitemap-index.php
│   └── Analyzer/
│       └── class-content-analyzer.php
├── assets/
│   ├── css/admin.css
│   ├── css/frontend.css
│   ├── js/admin.js
│   └── js/metabox.js
└── languages/
    └── wp-seo-master.pot
```

---

## 🔢 VARSAYILAN AYARLAR (`wpsm_settings` option'ı)

```php
[
  'title_separator'     => '|',
  'enable_sitemap'      => true,
  'enable_schema'       => true,
  'enable_opengraph'    => true,
  'enable_twitter'      => true,
  'enable_breadcrumbs'  => true,
  'enable_analyzer'     => true,
  'default_schema_type' => 'Article',
  'twitter_site'        => '',
  'facebook_app_id'     => '',
  'default_og_image'    => '',
  'gsc_verification'    => '',
  'bing_verification'   => '',
  'robots_txt_custom'   => '',
  'sitemap_cache_hours' => 12,
  'db_version'          => '1.0.0',
]
```

---

## 📦 MODÜL DETAYLARI

### MODÜL 1 — Bootstrap + Autoloader + Plugin

**`wp-seo-master.php`:**
- Plugin header (Name, URI, Description, Version 1.0.0, Author, License GPL-2.0-or-later, License URI, Text Domain: wp-seo-master, Domain Path: /languages, Requires at least: 6.0, Requires PHP: 7.4)
- `ABSPATH` kontrolü
- Sabitler: `WPSM_VERSION`, `WPSM_FILE`, `WPSM_PATH`, `WPSM_URL`, `WPSM_BASENAME`
- Autoloader require + register
- `plugins_loaded` → `WPSM\Plugin::get_instance()`
- `register_activation_hook` → `WPSM\Installer::activate`
- `register_deactivation_hook` → `WPSM\Installer::deactivate`

**`class-autoloader.php`:**
- `WPSM\Admin\Class_Admin_Menu` → `includes/Admin/class-admin-menu.php`
- Namespace ayracını `/` yap, sınıf adını lowercase yap, `_` → `-`, başına `class-` ekle, `.php` ekle
- `WPSM_PATH . $path` varsa require_once

**`class-plugin.php`:**
- Singleton: `private static $instance`, `get_instance()`, `private __construct()`
- `load_dependencies()`: Tüm sınıfları require
- `init_hooks()`: i18n, Options, Schema\Manager, Frontend\Meta_Tags, Frontend\OpenGraph, Frontend\Twitter_Cards, Frontend\Canonical, Frontend\Robots, Frontend\Breadcrumbs her zaman; Admin sınıfları sadece `is_admin()` ise; Sitemap her zaman; Analyzer admin+ajax
- Her sınıfın `init()` metodu varsa çağır

### MODÜL 2 — Installer + Options + i18n

**`class-installer.php`:**
- `activate()`: Varsayılanları yaz, rewrite flush, `db_version` kontrolü
- `deactivate()`: Sadece `flush_rewrite_rules()`
- `maybe_upgrade()`: Gelecekteki migration için altyapı

**`class-options.php`:**
- `get($key, $default = null)`, `set($key, $value)`, `all()`, `update($array)`, `delete($key)`
- `static $cache = null` — aynı request'te tek sorgu
- `sanitize($input)`: Tip bazlı temizleme (bool, string, url, email, int)

**`class-i18n.php`:**
- `load_textdomain()` → `load_plugin_textdomain('wp-seo-master', false, dirname(WPSM_BASENAME).'/languages')`
- `plugins_loaded` hook

### MODÜL 3 — Admin Menü + Settings

**`class-admin-menu.php`:**
- `admin_menu` hook: Ana menü `wpsm-dashboard`, `dashicons-chart-line`, pozisyon 80
- Alt menüler: Dashboard, Genel Ayarlar, Şema, Sosyal Medya, Sitemap
- `admin_enqueue_scripts`: `wp_enqueue_media()`, admin.css, admin.js
- Her sayfa için render metodu — view dosyasını `include`

**`class-settings.php`:**
- `admin_init` hook: `register_setting('wpsm_settings_group', 'wpsm_settings', ['sanitize' => [$this, 'sanitize']])`
- `admin_post_wpsm_save_settings` action: nonce + capability kontrolü + kaydet
- `admin_post_wpsm_regenerate_sitemap`: sitemap cache temizle

**View dosyaları:** WordPress `postbox` yapısı, tab gezinmesi, karakter sayacı, media uploader butonları, tüm alanlar `esc_*` ile.

- `settings-general.php`: Title separator (radio), Home title/desc, GSC verification, Bing verification, robots.txt textarea + "Varsayılana Dön"
- `settings-social.php`: FB App ID, Twitter Site, varsayılan OG görseli (media uploader), Twitter Card tipi
- `settings-schema.php`: Organization (name, logo, sosyal profiller), WebSite SearchAction aktif/pasif, varsayılan şema tipi
- `settings-sitemap.php`: Aktif/pasif, post type seçimi, taxonomy seçimi, cache süresi, "Yeniden Oluştur" butonu

### MODÜL 4 — Meta Box

**`class-metabox.php`:**
- `add_meta_boxes`: `post`, `page` ve public CPT'lerde görünür, context normal, priority high
- `save_post`: Autosave/revision kontrolü, nonce `wpsm_metabox_nonce`, `current_user_can('edit_post', $post_id)`
- Meta key'leri: `_wpsm_title`, `_wpsm_description`, `_wpsm_focus_keyword`, `_wpsm_canonical`, `_wpsm_og_title`, `_wpsm_og_description`, `_wpsm_og_image`, `_wpsm_twitter_title`, `_wpsm_twitter_description`, `_wpsm_twitter_image`, `_wpsm_schema_type`, `_wpsm_schema_data` (JSON), `_wpsm_robots` (array), `_wpsm_breadcrumb_title`
- `register_post_meta` ile REST API'ye aç (her biri için `show_in_rest => true`, `single => true`, `auth_callback`)

**`views/metabox.php`:** 4 sekme — İçerik, Sosyal, Şema, Gelişmiş. Karakter sayaçları (Title 60, Desc 160), schema tipi select, dinamik alanlar için `<div id="wpsm-schema-fields">`.

**`assets/js/metabox.js`:**
- Karakter sayacı (renk: 0-40 kırmızı, 41-70 sarı, 71-100 yeşil)
- Tab geçişleri
- Media uploader (`wp.media`)
- Schema tipi değişince `admin-ajax.php` POST action `wpsm_get_schema_fields` (nonce: `wpsm_schema_nonce`) ile alanları yükle
- İçerik analizi butonu: action `wpsm_analyze_content`

**`class-metabox.php` içine AJAX handler'ları:**
- `wp_ajax_wpsm_get_schema_fields`: tip'e göre HTML döner (FAQ repeater, HowTo steps, Product, LocalBusiness, Article → boş)
- `wp_ajax_wpsm_analyze_content`: skor hesapla, JSON döner

### MODÜL 5 — Frontend Meta + Canonical + Robots

**`class-meta-tags.php`:**
- `pre_get_document_title` filter (priority 99) ile title override
- Öncelik: `_wpsm_title` > şablon > site başlığı
- Şablon değişkenleri: `%%title%%`, `%%sitename%%`, `%%sep%%`, `%%page%%`, `%%category%%`, `%%tag%%`, `%%search_query%%`, `%%currentyear%%`
- Separator: `Options::get('title_separator')`
- Meta description: `wp_head` priority 1, `<meta name="description">`
- Öncelik: `_wpsm_description` > excerpt > otomatik 160 karakter (`wp_trim_words`)

**`class-canonical.php`:**
- `wp_head` priority 2, `<link rel="canonical">`
- Öncelik: `_wpsm_canonical` > `get_permalink()` > `home_url()`
- `is_search()` ve `is_404()` sayfalarında canonical çıktısı verme

**`class-robots.php`:**
- `wp_robots` filter (WP 5.7+) ile noindex/nofollow/noarchive
- Otomatik noindex: `is_search()`, `is_404()`, tarih arşivleri
- `_wpsm_robots` meta ile override
- `robots_txt` filter ile dinamik robots.txt
- Sitemap URL'ini `Sitemap: ...` olarak ekle
- Custom robots.txt (options'tan) varsa onu kullan

**GSC + Bing verification:**
- `wp_head` priority 1 ile `<meta name="google-site-verification">` ve `<meta name="msvalidate.01">`

### MODÜL 6 — OpenGraph + Twitter

**`class-opengraph.php`:**
- `wp_head` priority 5
- `og:locale`, `og:type`, `og:title`, `og:description`, `og:url`, `og:site_name`
- `og:image` (1200x630), `og:image:width`, `og:image:height`, `og:image:alt`
- Article: `article:published_time`, `article:modified_time`, `article:author`, `article:section`, `article:tag`
- WooCommerce varsa Product: `product:price:amount`, `product:price:currency`, `product:availability`
- Öncelik: post meta > global > varsayılan

**`class-twitter-cards.php`:**
- `twitter:card`, `twitter:site`, `twitter:creator`, `twitter:title`, `twitter:description`, `twitter:image`, `twitter:image:alt`

### MODÜL 7 — Schema.org (KRİTİK)

**`class-schema-manager.php`:**
- `wp_head` priority 10
- `@graph` yapısı
- Her sayfada: `WebSite` + `Organization`
- `is_singular()`: `_wpsm_schema_type` > varsayılan
- Çıktı: `<script type="application/ld+json">` + `wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`
- Yardımcı: `get_web_site()`, `get_organization()` metodları

**`class-article-schema.php`:**
- `Article` / `BlogPosting` / `NewsArticle`
- `@id`: `{permalink}#article`, headline, description, datePublished, dateModified, author (Person), publisher (Organization `#organization`), image, mainEntityOfPage, articleSection, keywords, wordCount, commentCount

**`class-faq-schema.php`:**
- `FAQPage`, `mainEntity: Question[]`
- Veri: `_wpsm_schema_data` JSON

**`class-howto-schema.php`:**
- `HowTo`, name, description, totalTime, estimatedCost, step[] (HowToStep)

**`class-product-schema.php`:**
- `Product`, name, description, image, sku, brand, offers (price, priceCurrency, availability, url)

**`class-localbusiness-schema.php`:**
- `LocalBusiness`, name, image, address (PostalAddress), telephone, openingHours, geo, priceRange, url

**`class-breadcrumb-schema.php`:**
- `BreadcrumbList`, itemListElement[] ile Breadcrumb sınıfından veri alır

### MODÜL 8 — Sitemap

**`class-sitemap-generator.php`:**
- `init` hook: `add_rewrite_rule('^sitemap_index\.xml$', 'index.php?wpsm_sitemap=index', 'top')` ve `^([a-z]+)-sitemap\.xml$` → `index.php?wpsm_sitemap=$matches[1]`
- `query_vars` filter: `wpsm_sitemap` ekle
- `template_redirect`: `wpsm_sitemap` varsa XML çıktısı ver
- Cache: transient, `wpsm_sitemap_{type}`, süre options'tan
- Post type'lar: post, page, WooCommerce varsa product
- Taxonomy'ler: category, post_tag
- noindex'li içeriği hariç tut
- Her sitemap max 1000 URL

**`class-sitemap-index.php`:**
- Ana index XML: `<sitemapindex>` + alt sitemap listesi
- Alt sitemap URL'leri: `home_url('/post-sitemap.xml')` vb.

**Rewrite flush:** Aktivasyonda `flush_rewrite_rules()`

### MODÜL 9 — Breadcrumb

**`class-breadcrumbs.php`:**
- `wpsm_breadcrumbs($args)` helper fonksiyonu (global scope'ta tanımla)
- `[wpsm_breadcrumb]` shortcode
- `get_breadcrumbs()` → array döner
- Hiyerarşi: `is_home()`, `is_category()`, `is_single()`, `is_page()`, `is_search()`, `is_404()`
- Microdata + JSON-LD entegrasyonu (Schema ile)
- Ayarlanabilir: separator, home_text, show_on_home
- `frontend.css` ile minimal stil

### MODÜL 10 — İçerik Analizi

**`class-content-analyzer.php`:**
- `analyze($post_id)` → `['score' => 0-100, 'checks' => [...]]`
- Kontroller (her biri 0-10 puan):
  - Focus keyword var mı?
  - Başlıkta geçiyor mu?
  - İlk paragrafta geçiyor mu?
  - H2/H3'lerde geçiyor mu?
  - Min 300 kelime
  - Meta description'da geçiyor mu?
  - URL'de geçiyor mu?
  - Görsel alt tag'lerinde geçiyor mu?
  - Keyword yoğunluğu %0.5-2.5
  - İç link var mı?
  - Dış link var mı?
- Skor: toplam puanlar / toplam kontrol * 100
- Metabox'ta gösterim için `metabox.js`'te circular progress bar

### MODÜL 11 — Uninstall + readme.txt

**`uninstall.php`:**
- `WP_UNINSTALL_PLUGIN` kontrolü
- `delete_option('wpsm_settings')`
- `$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpsm_%'")`
- `$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_wpsm_%'")`
- Transient temizle: `_transient_wpsm_%`, `_transient_timeout_wpsm_%`
- Multisite ise `get_sites()` döngüsü ile her site için

**`readme.txt`:**
- WordPress standart format
- Stable tag 1.0.0, Requires at least 6.0, Tested up to 6.7, Requires PHP 7.4, License GPLv2 or later
- Sections: Description, Installation, FAQ, Changelog

---

## ✅ KALİTE KONTROL

Her modül sonunda kendi kendine şu kontrolü yap ve "KONTROL: ✓" olarak raporla:
- [ ] Tüm `$_POST`/`$_GET` sanitize edildi mi?
- [ ] Tüm çıktılar escape edildi mi?
- [ ] Nonce var mı?
- [ ] Capability kontrolü var mı?
- [ ] `ABSPATH` kontrolü var mı?
- [ ] Text domain tutarlı mı?
- [ ] `wpsm_` prefix'li mi?

---

## 🎬 ÇIKTI FORMATI

Her dosyayı şu formatta ver:

```
### 📄 DOSYA: yol/dosya-adi.php

```php
[tam dosya içeriği]
```
```

Modüller arasında `---` ile ayır. Son modül bittiğinde:

```
## 🏁 PROJE TAMAMLANDI

Toplam üretilen dosya: X
Toplam satır: Y

Kurulum Adımları:
1. ...
2. ...

Test Önerileri:
1. ...
2. ...

Sonraki adım: Kullanıcı test prompt'u verecek.
```

ŞİMDİ BAŞLA. Onay bekleme. Tüm dosyaları sırayla üret.
