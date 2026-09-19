import { useState } from 'react'

const files = [
  {
    id: 'meta-tags',
    name: 'class-meta-tags.php',
    path: 'includes/Frontend/class-meta-tags.php',
    description: 'Meta etiketleri - Title, description, robots, verification kodları',
    language: 'php',
    code: `<?php
/**
 * Meta Tags Sınıfı
 *
 * wp_head hook'unda meta etiketlerini oluşturur.
 * Priority 1 ile çalışır.
 *
 * @package WPSM\\Frontend
 * @since 1.0.0
 */

namespace WPSM\\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Meta_Tags {

    private $options;
    private $other_seo_active = false;

    public function __construct( $options ) {
        $this->options = $options;
        $this->check_other_seo_plugins();
    }

    /**
     * Diğer SEO eklentilerini kontrol et
     * Yoast, AIOSEO, Rank Math aktifse devre dışı kal
     */
    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION',      // Yoast SEO
            'AIOSEO_VERSION',     // All in One SEO
            'RANK_MATH_VERSION',  // Rank Math
            'SEOPRESS_VERSION',   // SEOPress
        );

        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;
                if ( is_admin() && current_user_can( 'manage_options' ) ) {
                    add_action( 'admin_notices', array( $this, 'other_seo_notice' ) );
                }
                break;
            }
        }
    }

    /**
     * Meta etiketlerini çıktıla (wp_head priority 1)
     */
    public function output_meta_tags() {
        if ( $this->other_seo_active ) return;

        $this->output_description();
        $this->output_robots();
        $this->output_verification_codes();
    }

    /**
     * Title tag'i override et (pre_get_document_title filter)
     *
     * Öncelik: post meta _wpsm_title > şablon > site başlığı
     * Şablon değişkenleri: %%title%%, %%sitename%%, %%sep%%, %%page%%
     */
    public function filter_document_title( $title, $sep, $seplocation ) {
        if ( $this->other_seo_active ) return $title;

        $sep = $this->options->get( 'title_separator', '|' );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $custom_title = get_post_meta( $post_id, '_wpsm_title', true );

            if ( ! empty( $custom_title ) ) {
                return $this->replace_template_variables( $custom_title, $sep );
            }
            return $title;
        }

        // Ana sayfa
        if ( is_front_page() ) {
            $home_title = $this->options->get( 'home_title', '' );
            if ( ! empty( $home_title ) ) {
                return $this->replace_template_variables( $home_title, $sep );
            }
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            return get_the_archive_title() . ' ' . $sep . ' ' . get_bloginfo( 'name' );
        }

        // Arama sayfası
        if ( is_search() ) {
            return sprintf( __( 'Arama: %s', 'wp-seo-master' ), get_search_query() )
                . ' ' . $sep . ' ' . get_bloginfo( 'name' );
        }

        // 404 sayfası
        if ( is_404() ) {
            return __( 'Sayfa Bulunamadı', 'wp-seo-master' ) . ' ' . $sep . ' ' . get_bloginfo( 'name' );
        }

        return $title;
    }

    /**
     * Şablon değişkenlerini değiştir
     *
     * %%title%%, %%sitename%%, %%sep%%, %%page%%, %%category%%,
     * %%tag%%, %%search_query%%, %%date%%, %%author%%
     */
    private function replace_template_variables( $template, $sep = '' ) {
        if ( empty( $sep ) ) {
            $sep = $this->options->get( 'title_separator', '|' );
        }

        $replacements = array(
            '%%title%%'        => $this->get_page_title(),
            '%%sitename%%'     => get_bloginfo( 'name' ),
            '%%sep%%'          => $sep,
            '%%page%%'         => $this->get_page_number(),
            '%%category%%'     => $this->get_category_name(),
            '%%tag%%'          => $this->get_tag_name(),
            '%%search_query%%' => get_search_query(),
            '%%date%%'         => is_date() ? get_the_date() : '',
            '%%author%%'       => is_author() ? get_the_author() : '',
        );

        $replacements = apply_filters( 'wpsm_title_template_variables', $replacements );

        return trim( str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $template
        ));
    }

    /**
     * Meta description çıktısı
     *
     * Öncelik: post meta > excerpt > otomatik ilk 160 karakter
     */
    private function output_description() {
        $description = '';

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $description = get_post_meta( $post_id, '_wpsm_description', true );

            if ( empty( $description ) ) {
                $description = get_the_excerpt( $post_id );
            }

            if ( empty( $description ) ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    $description = wp_trim_words(
                        wp_strip_all_tags( $post->post_content ),
                        25, '...'
                    );
                }
            }
        }

        if ( is_front_page() ) {
            $description = $this->options->get( 'home_description', '' );
            if ( empty( $description ) ) {
                $description = get_bloginfo( 'description' );
            }
        }

        if ( is_archive() ) {
            $description = wp_strip_all_tags( get_the_archive_description() );
        }

        if ( ! empty( $description ) ) {
            if ( strlen( $description ) > 160 ) {
                $description = substr( $description, 0, 157 ) . '...';
            }
            echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\\n";
        }
    }

    /**
     * Robots meta çıktısı
     *
     * Arama, 404, tarih arşivleri için otomatik noindex.
     * post meta _wpsm_robots ile override.
     */
    private function output_robots() {
        $robots = array();

        // Otomatik noindex
        if ( is_search() || is_404() ) {
            $robots[] = 'noindex';
        }

        if ( is_date() ) {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        }

        // Singular - post meta override
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post_robots = get_post_meta( $post_id, '_wpsm_robots', true );

            if ( ! empty( $post_robots ) && is_array( $post_robots ) ) {
                $robots = $post_robots;
            }
        }

        if ( empty( $robots ) ) return;

        echo '<meta name="robots" content="' . esc_attr( implode( ', ', $robots ) ) . '" />' . "\\n";
    }

    /**
     * Webmaster doğrulama kodları (sadece ana sayfada)
     */
    private function output_verification_codes() {
        if ( ! is_front_page() ) return;

        $codes = array(
            'google_verification'    => 'google-site-verification',
            'bing_verification'      => 'msvalidate.01',
            'yandex_verification'    => 'yandex-verification',
            'pinterest_verification' => 'p:domain_verify',
        );

        foreach ( $codes as $option_key => $meta_name ) {
            $value = $this->options->get( $option_key, '' );
            if ( ! empty( $value ) ) {
                echo '<meta name="' . esc_attr( $meta_name ) . '" content="' . esc_attr( $value ) . '" />' . "\\n";
            }
        }
    }

    // Helper metodlar
    private function get_page_title() { /* ... */ }
    private function get_page_number() { /* ... */ }
    private function get_category_name() { /* ... */ }
    private function get_tag_name() { /* ... */ }
}`,
  },
  {
    id: 'robots',
    name: 'class-robots.php',
    path: 'includes/Frontend/class-robots.php',
    description: 'Robots yönetimi - wp_robots filter, robots.txt, sitemap URL',
    language: 'php',
    code: `<?php
/**
 * Robots Sınıfı
 *
 * robots.txt dosyasını dinamik olarak oluşturur.
 * Sitemap URL'ini otomatik ekler.
 *
 * @package WPSM\\Frontend
 * @since 1.0.0
 */

namespace WPSM\\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Robots {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Robots meta etiketini filtrele (wp_robots filter - WP 5.7+)
     *
     * Post meta _wpsm_robots ile override edilir.
     * Arama, 404, tarih arşivleri için otomatik noindex.
     *
     * @param array $robots Mevcut robots direktifleri
     * @return array Düzenlenmiş robots direktifleri
     */
    public function modify_robots( $robots ) {
        // Singular sayfa - post meta kontrolü
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post_robots = get_post_meta( $post_id, '_wpsm_robots', true );

            if ( ! empty( $post_robots ) && is_array( $post_robots ) ) {
                foreach ( $post_robots as $directive ) {
                    switch ( $directive ) {
                        case 'noindex':
                            $robots['noindex'] = true;
                            break;
                        case 'nofollow':
                            $robots['nofollow'] = true;
                            break;
                        case 'noarchive':
                            $robots['noarchive'] = true;
                            break;
                        case 'nosnippet':
                            $robots['nosnippet'] = true;
                            break;
                        case 'noimageindex':
                            $robots['noimageindex'] = true;
                            break;
                    }
                }
            }
        }

        // Arama ve 404 için otomatik noindex
        if ( is_search() || is_404() ) {
            $robots['noindex'] = true;
        }

        // Tarih arşivleri
        if ( is_date() ) {
            $robots['noindex'] = true;
        }

        return $robots;
    }

    /**
     * Robots.txt içeriğini filtrele
     *
     * robots_txt filter'ı ile çalışır.
     * Sitemap URL'ini otomatik ekler.
     * Admin panelden override edilebilir.
     *
     * @param string $output Varsayılan robots.txt
     * @param bool   $public Site herkese açık mı?
     * @return string Düzenlenmiş robots.txt
     */
    public function filter_robots_txt( $output, $public ) {
        // Site özel ise
        if ( ! $public ) {
            return "User-agent: *\\nDisallow: /";
        }

        // Admin panelden özel robots.txt
        $custom_robots = $this->options->get( 'robots_txt', '' );

        if ( ! empty( $custom_robots ) ) {
            $output = $custom_robots;
        } else {
            // Varsayılan WordPress robots.txt
            $output  = "User-agent: *\\n";
            $output .= "Disallow: /wp-admin/\\n";
            $output .= "Allow: /wp-admin/admin-ajax.php\\n";
        }

        // Sitemap URL'ini ekle
        $sitemap_enabled = $this->options->get( 'enable_sitemap', true );

        if ( $sitemap_enabled ) {
            $sitemap_url = home_url( '/sitemap.xml' );
            $output .= "\\n\\nSitemap: " . esc_url( $sitemap_url );
        }

        return apply_filters( 'wpsm_robots_txt', $output );
    }

    /**
     * Robots.txt rewrite kuralını kaydet
     */
    public function register_robots_rewrite() {
        add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
    }
}`,
  },
  {
    id: 'canonical',
    name: 'class-canonical.php',
    path: 'includes/Frontend/class-canonical.php',
    description: 'Canonical URL - rel=canonical, pagination prev/next',
    language: 'php',
    code: `<?php
/**
 * Canonical Sınıfı
 *
 * Canonical URL çıktısını yönetir.
 * rel="canonical" ve opsiyonel rel="prev"/"next".
 *
 * @package WPSM\\Frontend
 * @since 1.0.0
 */

namespace WPSM\\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Canonical {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Canonical URL çıktısı (wp_head)
     */
    public function output_canonical() {
        $canonical = $this->get_canonical_url();

        if ( ! empty( $canonical ) ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\\n";
        }

        // Pagination prev/next
        $this->output_pagination_links();
    }

    /**
     * Canonical URL'ini döndür
     *
     * Öncelik: post meta _wpsm_canonical > get_permalink() > home_url()
     *
     * @return string
     */
    public function get_canonical_url() {
        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $custom = get_post_meta( $post_id, '_wpsm_canonical', true );

            if ( ! empty( $custom ) ) {
                return esc_url( $custom );
            }
            return get_permalink( $post_id );
        }

        // Ana sayfa
        if ( is_front_page() ) {
            return home_url( '/' );
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            return $this->get_archive_canonical();
        }

        // Arama
        if ( is_search() ) {
            return get_search_link();
        }

        // 404
        if ( is_404() ) {
            return home_url( '/' );
        }

        return home_url( $_SERVER['REQUEST_URI'] );
    }

    /**
     * Arşiv canonical URL
     */
    private function get_archive_canonical() {
        if ( is_category() ) {
            return get_category_link( get_queried_object_id() );
        }

        if ( is_tag() ) {
            return get_tag_link( get_queried_object_id() );
        }

        if ( is_tax() ) {
            return get_term_link( get_queried_object() );
        }

        if ( is_author() ) {
            return get_author_posts_url( get_queried_object_id() );
        }

        if ( is_date() ) {
            if ( is_day() ) {
                return get_day_link(
                    get_query_var( 'year' ),
                    get_query_var( 'monthnum' ),
                    get_query_var( 'day' )
                );
            } elseif ( is_month() ) {
                return get_month_link(
                    get_query_var( 'year' ),
                    get_query_var( 'monthnum' )
                );
            } elseif ( is_year() ) {
                return get_year_link( get_query_var( 'year' ) );
            }
        }

        if ( is_post_type_archive() ) {
            return get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        return home_url( $_SERVER['REQUEST_URI'] );
    }

    /**
     * Pagination prev/next linkleri
     *
     * WP 4.1+ kaldırıldı ama bazı temalar için opsiyonel.
     */
    private function output_pagination_links() {
        global $wp_query;

        $paged = get_query_var( 'paged' );
        if ( ! $paged || $paged < 2 ) return;

        $max_pages = $wp_query->max_num_pages;

        // Önceki sayfa
        if ( $paged > 2 ) {
            echo '<link rel="prev" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '" />' . "\\n";
        }

        // Sonraki sayfa
        if ( $paged < $max_pages ) {
            echo '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '" />' . "\\n";
        }
    }

    /**
     * Post canonical URL'ini döndür
     *
     * @param int $post_id Post ID
     * @return string
     */
    public function get_post_canonical( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( ! $post_id ) return '';

        $custom = get_post_meta( $post_id, '_wpsm_canonical', true );
        if ( ! empty( $custom ) ) {
            return esc_url( $custom );
        }

        return get_permalink( $post_id );
    }
}`,
  },
]

export default function CodeViewer() {
  const [activeFile, setActiveFile] = useState(files[0].id)
  const [copied, setCopied] = useState(false)

  const currentFile = files.find(f => f.id === activeFile) || files[0]

  const handleCopy = () => {
    navigator.clipboard.writeText(currentFile.code)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const highlightCode = (code: string, language: string) => {
    let highlighted = code
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')

    if (language === 'php') {
      highlighted = highlighted
        .replace(/\/\/.*$/gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\/\*\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/\/\*[\s\S]*?\*\//gm, '<span class="text-gray-500 italic">$&</span>')
        .replace(/(&lt;\?php)/g, '<span class="text-red-400 font-bold">$1</span>')
        .replace(/\b(class|function|private|public|protected|static|return|new|if|else|foreach|for|while|namespace|use|define|require_once|array|true|false|null|self|const|isset|echo|exit|switch|case|break|default)\b/g, '<span class="text-purple-400 font-medium">$1</span>')
        .replace(/(\$[a-zA-Z_]\w*)/g, '<span class="text-blue-300">$1</span>')
        .replace(/'([^'\\]*(?:\\.[^'\\]*)*)'/g, "'<span class=\"text-green-300\">$1</span>'")
    }

    return highlighted
  }

  return (
    <section id="code" className="py-24 relative">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <span className="inline-block px-3 py-1 text-xs font-medium bg-orange-500/10 text-orange-400 rounded-full border border-orange-500/20 mb-4">
            KAYNAK KOD
          </span>
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            Frontend Meta Tag Çıktıları
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            3 dosya: Meta etiketleri, robots yönetimi, canonical URL.
            wp_head hook'unda çalışır, diğer SEO eklentileri ile çakışma kontrolü,
            şablon değişkenleri, otomatik noindex kuralları.
          </p>
        </div>

        {/* File Tabs */}
        <div className="flex flex-wrap gap-2 mb-4">
          {files.map((file) => (
            <button
              key={file.id}
              onClick={() => setActiveFile(file.id)}
              className={`px-4 py-2 text-sm font-medium rounded-lg transition-all ${
                activeFile === file.id
                  ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
                  : 'bg-white/5 text-gray-400 border border-white/5 hover:bg-white/10 hover:text-white'
              }`}
            >
              {file.name}
            </button>
          ))}
        </div>

        {/* File Info */}
        <div className="mb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div>
            <p className="text-sm text-gray-400">
              <span className="text-gray-500">Yol:</span>{' '}
              <code className="text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded text-xs">{currentFile.path}</code>
            </p>
            <p className="text-xs text-gray-500 mt-1">{currentFile.description}</p>
          </div>
          <button
            onClick={handleCopy}
            className="flex items-center space-x-2 px-3 py-1.5 text-xs font-medium bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 transition-all text-gray-400 hover:text-white"
          >
            {copied ? (
              <>
                <svg className="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                <span className="text-green-400">Kopyalandı!</span>
              </>
            ) : (
              <>
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Kopyala</span>
              </>
            )}
          </button>
        </div>

        {/* Code Block */}
        <div className="bg-gray-900/80 border border-white/5 rounded-2xl overflow-hidden">
          <div className="flex items-center justify-between px-4 py-3 border-b border-white/5 bg-white/[0.02]">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 rounded-full bg-red-500/80" />
              <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
              <div className="w-3 h-3 rounded-full bg-green-500/80" />
            </div>
            <span className="text-xs text-gray-500 font-mono">{currentFile.name}</span>
            <span className="text-xs text-gray-600 uppercase">{currentFile.language}</span>
          </div>

          <div className="overflow-x-auto p-4 max-h-[600px] overflow-y-auto">
            <pre className="text-sm leading-relaxed">
              <code
                dangerouslySetInnerHTML={{
                  __html: highlightCode(currentFile.code, currentFile.language)
                }}
                className="text-gray-300 font-mono"
              />
            </pre>
          </div>
        </div>

        {/* Download Links */}
        <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
          {files.map((file) => (
            <a
              key={file.id}
              href={`/wp-seo-master/${file.path}`}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-xl hover:bg-white/5 hover:border-white/10 transition-all group"
            >
              <div>
                <p className="text-sm font-medium text-white group-hover:text-purple-300 transition-colors">
                  {file.name}
                </p>
                <p className="text-xs text-gray-500 mt-0.5">{file.path}</p>
              </div>
              <svg className="w-5 h-5 text-gray-500 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          ))}
        </div>

        {/* HTML Output Preview */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">HTML Çıktı Önizleme</h3>
          <div className="bg-gray-900 rounded-lg p-4 font-mono text-xs overflow-x-auto">
            <div className="text-gray-500">&lt;!-- wp_head output (priority 1) --&gt;</div>
            <div className="mt-2">
              <span className="text-red-400">&lt;title&gt;</span>
              <span className="text-white">WordPress SEO Eklentisi</span>
              <span className="text-purple-400"> | </span>
              <span className="text-white">Site Adı</span>
              <span className="text-red-400">&lt;/title&gt;</span>
            </div>
            <div className="mt-1">
              <span className="text-red-400">&lt;link</span>
              <span className="text-blue-300"> rel</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"canonical"</span>
              <span className="text-blue-300"> href</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"https://example.com/sayfa/"</span>
              <span className="text-red-400"> /&gt;</span>
            </div>
            <div className="mt-1">
              <span className="text-red-400">&lt;meta</span>
              <span className="text-blue-300"> name</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"description"</span>
              <span className="text-blue-300"> content</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"WordPress için en iyi SEO eklentisi..."</span>
              <span className="text-red-400"> /&gt;</span>
            </div>
            <div className="mt-1">
              <span className="text-red-400">&lt;meta</span>
              <span className="text-blue-300"> name</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"robots"</span>
              <span className="text-blue-300"> content</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"index, follow"</span>
              <span className="text-red-400"> /&gt;</span>
            </div>
            <div className="mt-1">
              <span className="text-red-400">&lt;meta</span>
              <span className="text-blue-300"> name</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"google-site-verification"</span>
              <span className="text-blue-300"> content</span>
              <span className="text-white">=</span>
              <span className="text-green-300">"abc123..."</span>
              <span className="text-red-400"> /&gt;</span>
            </div>
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: 'Title Override', desc: 'pre_get_document_title filter' },
            { title: 'Şablon Değişkenleri', desc: '%%title%%, %%sitename%%, %%sep%%...' },
            { title: 'Çakışma Koruması', desc: 'Yoast, AIOSEO, Rank Math kontrolü' },
            { title: 'Otomatik Noindex', desc: 'Search, 404, tarih arşivleri' },
            { title: 'Robots.txt', desc: 'Dinamik, sitemap URL otomatik' },
            { title: 'Canonical', desc: 'Post meta > permalink > home_url' },
            { title: 'Pagination', desc: 'rel="prev" / rel="next"' },
            { title: 'Escape', desc: 'esc_attr, esc_url ile güvenli' },
          ].map((item, i) => (
            <div key={i} className="p-4 bg-white/[0.02] border border-white/5 rounded-xl">
              <p className="text-sm font-medium text-purple-300">{item.title}</p>
              <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
            </div>
          ))}
        </div>

        {/* Conditional Logic */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">WordPress Conditional Kontrolleri</h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {[
              'is_singular()',
              'is_front_page()',
              'is_archive()',
              'is_search()',
              'is_404()',
              'is_date()',
              'is_category()',
              'is_tag()',
              'is_author()',
              'is_post_type_archive()',
              'is_tax()',
              'is_paged()',
            ].map((fn, i) => (
              <code key={i} className="text-xs bg-blue-500/10 text-blue-300 px-3 py-1.5 rounded border border-blue-500/20 text-center">
                {fn}
              </code>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
