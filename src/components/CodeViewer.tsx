import { useState } from 'react'

const files = [
  {
    id: 'opengraph',
    name: 'class-opengraph.php',
    path: 'includes/Frontend/class-opengraph.php',
    description: 'Open Graph meta etiketleri - Facebook, LinkedIn, article, product',
    language: 'php',
    code: `<?php
/**
 * Open Graph Sınıfı
 *
 * Facebook, LinkedIn ve diğer platformlar için OG meta etiketleri.
 * wp_head priority 5
 *
 * @package WPSM\\Frontend
 * @since 1.0.0
 */

namespace WPSM\\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Opengraph {

    private $options;
    private $other_seo_active = false;

    public function __construct( $options ) {
        $this->options = $options;
        $this->check_other_seo_plugins();
    }

    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION',      // Yoast
            'AIOSEO_VERSION',     // AIOSEO
            'RANK_MATH_VERSION',  // Rank Math
            'SEOPRESS_VERSION',   // SEOPress
        );

        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;
                break;
            }
        }
    }

    /**
     * Open Graph etiketlerini çıktıla (wp_head priority 5)
     */
    public function output_opengraph() {
        if ( $this->other_seo_active ) return;

        $enabled = $this->options->get( 'enable_opengraph', true );
        if ( ! $enabled ) return;

        // Temel OG etiketleri
        $this->output_basic_tags();

        // Görsel
        $this->output_image_tags();

        // Article etiketleri (singular)
        if ( is_singular() ) {
            $this->output_article_tags();
        }

        // Product etiketleri (WooCommerce)
        if ( $this->is_woocommerce_product() ) {
            $this->output_product_tags();
        }
    }

    /**
     * Temel OG etiketleri
     * og:locale, og:type, og:title, og:description, og:url, og:site_name
     */
    private function output_basic_tags() {
        $og_data = $this->get_og_data();

        // og:locale
        echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\\n";

        // og:type
        $type = $this->get_og_type();
        echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\\n";

        // og:title
        if ( ! empty( $og_data['title'] ) ) {
            echo '<meta property="og:title" content="' . esc_attr( $og_data['title'] ) . '" />' . "\\n";
        }

        // og:description
        if ( ! empty( $og_data['description'] ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $og_data['description'] ) . '" />' . "\\n";
        }

        // og:url
        if ( ! empty( $og_data['url'] ) ) {
            echo '<meta property="og:url" content="' . esc_url( $og_data['url'] ) . '" />' . "\\n";
        }

        // og:site_name
        $site_name = get_bloginfo( 'name' );
        if ( ! empty( $site_name ) ) {
            echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\\n";
        }

        // Facebook App ID
        $fb_app_id = $this->options->get( 'facebook_app_id', '' );
        if ( ! empty( $fb_app_id ) ) {
            echo '<meta property="fb:app_id" content="' . esc_attr( $fb_app_id ) . '" />' . "\\n";
        }
    }

    /**
     * OG görsel etiketleri
     * og:image, og:image:width, og:image:height, og:image:alt
     */
    private function output_image_tags() {
        $image_data = $this->get_og_image();

        if ( empty( $image_data['url'] ) ) return;

        echo '<meta property="og:image" content="' . esc_url( $image_data['url'] ) . '" />' . "\\n";

        if ( ! empty( $image_data['width'] ) ) {
            echo '<meta property="og:image:width" content="' . esc_attr( $image_data['width'] ) . '" />' . "\\n";
        }

        if ( ! empty( $image_data['height'] ) ) {
            echo '<meta property="og:image:height" content="' . esc_attr( $image_data['height'] ) . '" />' . "\\n";
        }

        if ( ! empty( $image_data['alt'] ) ) {
            echo '<meta property="og:image:alt" content="' . esc_attr( $image_data['alt'] ) . '" />' . "\\n";
        }
    }

    /**
     * Article etiketleri
     * article:published_time, article:modified_time, article:author,
     * article:section, article:tag
     */
    private function output_article_tags() {
        $post_id = get_queried_object_id();
        $post = get_post( $post_id );

        if ( ! $post ) return;

        // article:published_time
        if ( ! empty( $post->post_date_gmt ) ) {
            $published = mysql2date( 'c', $post->post_date_gmt );
            echo '<meta property="article:published_time" content="' . esc_attr( $published ) . '" />' . "\\n";
        }

        // article:modified_time
        if ( ! empty( $post->post_modified_gmt ) ) {
            $modified = mysql2date( 'c', $post->post_modified_gmt );
            echo '<meta property="article:modified_time" content="' . esc_attr( $modified ) . '" />' . "\\n";
        }

        // article:author
        $author = get_the_author_meta( 'display_name', $post->post_author );
        if ( ! empty( $author ) ) {
            echo '<meta property="article:author" content="' . esc_attr( $author ) . '" />' . "\\n";
        }

        // article:section (kategori)
        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '" />' . "\\n";
        }

        // article:tag (etiketler)
        $tags = get_the_tags( $post_id );
        if ( ! empty( $tags ) ) {
            foreach ( $tags as $tag ) {
                echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '" />' . "\\n";
            }
        }
    }

    /**
     * Product etiketleri (WooCommerce)
     * product:price:amount, product:price:currency,
     * product:availability, product:retailer_item_id
     */
    private function output_product_tags() {
        if ( ! function_exists( 'wc_get_product' ) ) return;

        $post_id = get_queried_object_id();
        $product = wc_get_product( $post_id );

        if ( ! $product ) return;

        // product:price:amount
        $price = $product->get_price();
        if ( ! empty( $price ) ) {
            echo '<meta property="product:price:amount" content="' . esc_attr( $price ) . '" />' . "\\n";
        }

        // product:price:currency
        $currency = get_woocommerce_currency();
        if ( ! empty( $currency ) ) {
            echo '<meta property="product:price:currency" content="' . esc_attr( $currency ) . '" />' . "\\n";
        }

        // product:availability
        $availability = $product->is_in_stock() ? 'in stock' : 'out of stock';
        echo '<meta property="product:availability" content="' . esc_attr( $availability ) . '" />' . "\\n";

        // product:retailer_item_id (SKU)
        $sku = $product->get_sku();
        if ( ! empty( $sku ) ) {
            echo '<meta property="product:retailer_item_id" content="' . esc_attr( $sku ) . '" />' . "\\n";
        }
    }

    /**
     * OG verilerini topla
     * Öncelik: post meta > global ayar > otomatik
     */
    private function get_og_data() {
        $data = array( 'title' => '', 'description' => '', 'url' => '' );

        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Post meta
            $data['title'] = get_post_meta( $post_id, '_wpsm_og_title', true );
            $data['description'] = get_post_meta( $post_id, '_wpsm_og_description', true );

            // SEO meta fallback
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_post_meta( $post_id, '_wpsm_title', true );
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = get_post_meta( $post_id, '_wpsm_description', true );
            }

            // Otomatik
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_title( $post_id );
            }
            if ( empty( $data['description'] ) ) {
                $excerpt = get_the_excerpt( $post_id );
                if ( ! empty( $excerpt ) ) {
                    $data['description'] = wp_trim_words( $excerpt, 25, '...' );
                }
            }

            $data['url'] = get_permalink( $post_id );
        }

        if ( is_front_page() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = $this->options->get( 'home_title', '' );
                if ( empty( $data['title'] ) ) {
                    $data['title'] = get_bloginfo( 'name' );
                }
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = $this->options->get( 'home_description', '' );
                if ( empty( $data['description'] ) ) {
                    $data['description'] = get_bloginfo( 'description' );
                }
            }
            $data['url'] = home_url( '/' );
        }

        if ( is_archive() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_archive_title();
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = wp_strip_all_tags( get_the_archive_description() );
            }
            $data['url'] = $this->get_archive_url();
        }

        if ( is_search() ) {
            $search_query = get_search_query();
            $data['title'] = sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query );
            $data['url'] = get_search_link();
        }

        return $data;
    }

    /**
     * OG görsel verilerini topla
     * Öncelik: post meta _wpsm_og_image > featured image > default_og_image
     */
    private function get_og_image() {
        $image_data = array( 'url' => '', 'width' => '', 'height' => '', 'alt' => '' );

        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Özel OG görseli
            $custom_image = get_post_meta( $post_id, '_wpsm_og_image', true );

            if ( ! empty( $custom_image ) ) {
                $image_data['url'] = $custom_image;
                $attachment_id = attachment_url_to_postid( $custom_image );
                if ( $attachment_id ) {
                    $meta = wp_get_attachment_metadata( $attachment_id );
                    if ( $meta ) {
                        $image_data['width'] = $meta['width'];
                        $image_data['height'] = $meta['height'];
                    }
                    $image_data['alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            } else {
                // Featured image
                $thumbnail_id = get_post_thumbnail_id( $post_id );
                if ( $thumbnail_id ) {
                    $image = wp_get_attachment_image_src( $thumbnail_id, 'large' );
                    if ( $image ) {
                        $image_data['url'] = $image[0];
                        $image_data['width'] = $image[1];
                        $image_data['height'] = $image[2];
                        $image_data['alt'] = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                    }
                }
            }
        }

        // Varsayılan OG görseli
        if ( empty( $image_data['url'] ) ) {
            $default = $this->options->get( 'default_og_image', '' );
            if ( ! empty( $default ) ) {
                $image_data['url'] = $default;
                $attachment_id = attachment_url_to_postid( $default );
                if ( $attachment_id ) {
                    $meta = wp_get_attachment_metadata( $attachment_id );
                    if ( $meta ) {
                        $image_data['width'] = $meta['width'];
                        $image_data['height'] = $meta['height'];
                    }
                    $image_data['alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            }
        }

        return $image_data;
    }

    private function get_og_type() {
        if ( is_singular() ) return 'article';
        if ( $this->is_woocommerce_product() ) return 'product';
        return 'website';
    }

    private function is_woocommerce_product() {
        if ( ! function_exists( 'is_product' ) ) return false;
        return is_product();
    }

    private function get_archive_url() {
        if ( is_category() ) return get_category_link( get_queried_object_id() );
        if ( is_tag() ) return get_tag_link( get_queried_object_id() );
        if ( is_tax() ) return get_term_link( get_queried_object() );
        if ( is_author() ) return get_author_posts_url( get_queried_object_id() );
        if ( is_post_type_archive() ) return get_post_type_archive_link( get_query_var( 'post_type' ) );
        return home_url( $_SERVER['REQUEST_URI'] );
    }
}`,
  },
  {
    id: 'twitter',
    name: 'class-twitter-cards.php',
    path: 'includes/Frontend/class-twitter-cards.php',
    description: 'Twitter Cards meta etiketleri - card, site, creator, title, description, image',
    language: 'php',
    code: `<?php
/**
 * Twitter Cards Sınıfı
 *
 * Twitter/X için meta etiketleri oluşturur.
 * wp_head priority 6
 *
 * @package WPSM\\Frontend
 * @since 1.0.0
 */

namespace WPSM\\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Twitter_Cards {

    private $options;
    private $other_seo_active = false;

    public function __construct( $options ) {
        $this->options = $options;
        $this->check_other_seo_plugins();
    }

    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION',
            'AIOSEO_VERSION',
            'RANK_MATH_VERSION',
            'SEOPRESS_VERSION',
        );

        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;
                break;
            }
        }
    }

    /**
     * Twitter Cards etiketlerini çıktıla (wp_head priority 6)
     */
    public function output_twitter_cards() {
        if ( $this->other_seo_active ) return;

        $enabled = $this->options->get( 'enable_twitter', true );
        if ( ! $enabled ) return;

        // twitter:card
        $card_type = $this->options->get( 'twitter_card_type', 'summary_large_image' );
        echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\\n";

        // twitter:site
        $twitter_site = $this->options->get( 'twitter_site', '' );
        if ( ! empty( $twitter_site ) ) {
            // @ işareti ekle (yoksa)
            if ( strpos( $twitter_site, '@' ) !== 0 ) {
                $twitter_site = '@' . $twitter_site;
            }
            echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '" />' . "\\n";
        }

        // Twitter verilerini topla
        $twitter_data = $this->get_twitter_data();

        // twitter:title
        if ( ! empty( $twitter_data['title'] ) ) {
            echo '<meta name="twitter:title" content="' . esc_attr( $twitter_data['title'] ) . '" />' . "\\n";
        }

        // twitter:description
        if ( ! empty( $twitter_data['description'] ) ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $twitter_data['description'] ) . '" />' . "\\n";
        }

        // twitter:image
        if ( ! empty( $twitter_data['image'] ) ) {
            echo '<meta name="twitter:image" content="' . esc_url( $twitter_data['image'] ) . '" />' . "\\n";

            // twitter:image:alt
            if ( ! empty( $twitter_data['image_alt'] ) ) {
                echo '<meta name="twitter:image:alt" content="' . esc_attr( $twitter_data['image_alt'] ) . '" />' . "\\n";
            }
        }

        // twitter:creator (post author)
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $creator = get_post_meta( $post_id, '_wpsm_twitter_creator', true );

            if ( empty( $creator ) ) {
                // Yazarın Twitter hesabını al
                $author_id = get_post_field( 'post_author', $post_id );
                $creator = get_the_author_meta( 'twitter', $author_id );
            }

            if ( ! empty( $creator ) ) {
                if ( strpos( $creator, '@' ) !== 0 ) {
                    $creator = '@' . $creator;
                }
                echo '<meta name="twitter:creator" content="' . esc_attr( $creator ) . '" />' . "\\n";
            }
        }
    }

    /**
     * Twitter verilerini topla
     * Öncelik: post meta > OG meta > SEO meta > otomatik
     */
    private function get_twitter_data() {
        $data = array(
            'title'       => '',
            'description' => '',
            'image'       => '',
            'image_alt'   => '',
        );

        if ( is_singular() ) {
            $post_id = get_queried_object_id();

            // Twitter meta
            $data['title'] = get_post_meta( $post_id, '_wpsm_twitter_title', true );
            $data['description'] = get_post_meta( $post_id, '_wpsm_twitter_description', true );

            // OG meta fallback
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_post_meta( $post_id, '_wpsm_og_title', true );
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = get_post_meta( $post_id, '_wpsm_og_description', true );
            }

            // SEO meta fallback
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_post_meta( $post_id, '_wpsm_title', true );
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = get_post_meta( $post_id, '_wpsm_description', true );
            }

            // Otomatik
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_title( $post_id );
            }
            if ( empty( $data['description'] ) ) {
                $excerpt = get_the_excerpt( $post_id );
                if ( ! empty( $excerpt ) ) {
                    $data['description'] = wp_trim_words( $excerpt, 25, '...' );
                }
            }

            // Twitter görseli
            $twitter_image = get_post_meta( $post_id, '_wpsm_twitter_image', true );

            if ( ! empty( $twitter_image ) ) {
                $data['image'] = $twitter_image;
                $attachment_id = attachment_url_to_postid( $twitter_image );
                if ( $attachment_id ) {
                    $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            } else {
                // OG görseli fallback
                $og_image = get_post_meta( $post_id, '_wpsm_og_image', true );
                if ( ! empty( $og_image ) ) {
                    $data['image'] = $og_image;
                    $attachment_id = attachment_url_to_postid( $og_image );
                    if ( $attachment_id ) {
                        $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                    }
                } else {
                    // Featured image
                    $thumbnail_id = get_post_thumbnail_id( $post_id );
                    if ( $thumbnail_id ) {
                        $image = wp_get_attachment_image_src( $thumbnail_id, 'large' );
                        if ( $image ) {
                            $data['image'] = $image[0];
                            $data['image_alt'] = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                        }
                    }
                }
            }
        }

        // Ana sayfa
        if ( is_front_page() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = $this->options->get( 'home_title', '' );
                if ( empty( $data['title'] ) ) {
                    $data['title'] = get_bloginfo( 'name' );
                }
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = $this->options->get( 'home_description', '' );
                if ( empty( $data['description'] ) ) {
                    $data['description'] = get_bloginfo( 'description' );
                }
            }

            // Varsayılan OG görseli
            $default_image = $this->options->get( 'default_og_image', '' );
            if ( ! empty( $default_image ) ) {
                $data['image'] = $default_image;
                $attachment_id = attachment_url_to_postid( $default_image );
                if ( $attachment_id ) {
                    $data['image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                }
            }
        }

        // Arşiv
        if ( is_archive() ) {
            if ( empty( $data['title'] ) ) {
                $data['title'] = get_the_archive_title();
            }
            if ( empty( $data['description'] ) ) {
                $data['description'] = wp_strip_all_tags( get_the_archive_description() );
            }
        }

        // Arama
        if ( is_search() ) {
            $search_query = get_search_query();
            $data['title'] = sprintf( __( 'Arama: %s', 'wp-seo-master' ), $search_query );
        }

        return $data;
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
            Sosyal Medya Meta Tag'leri
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            2 dosya: Open Graph (Facebook/LinkedIn) ve Twitter Cards.
            Öncelik sistemi, görsel boyut kontrolü, WooCommerce desteği,
            article/product etiketleri.
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
        <div className="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        <div className="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Open Graph Output */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-4 flex items-center">
              <span className="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-2">
                <span className="text-blue-400 text-sm">f</span>
              </span>
              Open Graph Çıktısı
            </h3>
            <div className="bg-gray-900 rounded-lg p-4 font-mono text-xs overflow-x-auto">
              <div className="text-gray-500">&lt;!-- Open Graph Tags --&gt;</div>
              <div className="mt-2">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"og:locale"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"tr_TR"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"og:type"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"article"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"og:title"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"Yazı Başlığı"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"og:image"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"https://.../image.jpg"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"og:image:width"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"1200"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1 text-gray-500">&lt;!-- Article Tags --&gt;</div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> property</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"article:published_time"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"2024-01-15T..."</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
            </div>
          </div>

          {/* Twitter Cards Output */}
          <div className="p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
            <h3 className="text-lg font-bold text-white mb-4 flex items-center">
              <span className="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center mr-2">
                <span className="text-sky-400 text-sm">𝕏</span>
              </span>
              Twitter Cards Çıktısı
            </h3>
            <div className="bg-gray-900 rounded-lg p-4 font-mono text-xs overflow-x-auto">
              <div className="text-gray-500">&lt;!-- Twitter Cards --&gt;</div>
              <div className="mt-2">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:card"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"summary_large_image"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:site"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"@kullaniciadi"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:title"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"Yazı Başlığı"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:description"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"Kısa açıklama..."</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:image"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"https://.../image.jpg"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
              <div className="mt-1">
                <span className="text-red-400">&lt;meta</span>
                <span className="text-blue-300"> name</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"twitter:creator"</span>
                <span className="text-blue-300"> content</span>
                <span className="text-white">=</span>
                <span className="text-green-300">"@yazar"</span>
                <span className="text-red-400"> /&gt;</span>
              </div>
            </div>
          </div>
        </div>

        {/* Priority System */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">Öncelik Sistemi</h3>
          <div className="flex flex-col md:flex-row items-center justify-center gap-4 text-sm">
            <div className="px-4 py-3 bg-purple-500/10 border border-purple-500/30 rounded-xl text-purple-300">
              <div className="font-bold">1. Post Meta</div>
              <div className="text-xs text-gray-400 mt-1">_wpsm_og_title, _wpsm_twitter_title</div>
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-blue-500/10 border border-blue-500/30 rounded-xl text-blue-300">
              <div className="font-bold">2. OG / Twitter Meta</div>
              <div className="text-xs text-gray-400 mt-1">_wpsm_og_title → twitter_title</div>
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-300">
              <div className="font-bold">3. SEO Meta</div>
              <div className="text-xs text-gray-400 mt-1">_wpsm_title, _wpsm_description</div>
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-300">
              <div className="font-bold">4. Otomatik</div>
              <div className="text-xs text-gray-400 mt-1">the_title, the_excerpt</div>
            </div>
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: 'OG Temel', desc: 'locale, type, title, desc, url, site_name' },
            { title: 'OG Görsel', desc: 'image, width, height, alt (1200x630)' },
            { title: 'Article Tags', desc: 'published_time, author, section, tag' },
            { title: 'Product Tags', desc: 'price, currency, availability (WooCommerce)' },
            { title: 'Twitter Card', desc: 'summary veya summary_large_image' },
            { title: 'Twitter Creator', desc: 'Post author veya özel twitter_creator' },
            { title: 'Görsel Öncelik', desc: 'OG > Featured > Default' },
            { title: 'Escape', desc: 'esc_attr, esc_url ile güvenli çıktı' },
          ].map((item, i) => (
            <div key={i} className="p-4 bg-white/[0.02] border border-white/5 rounded-xl">
              <p className="text-sm font-medium text-purple-300">{item.title}</p>
              <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
