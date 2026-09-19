import { useState } from 'react'

const files = [
  {
    id: 'manager',
    name: 'class-schema-manager.php',
    path: 'includes/Schema/class-schema-manager.php',
    description: 'Schema yönetici - @graph yapısı, WebSite, Organization, BreadcrumbList',
    language: 'php',
    code: `<?php
/**
 * Schema Manager Sınıfı
 *
 * JSON-LD şema çıktılarını yönetir.
 * wp_head priority 10'da çalışır.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Schema_Manager {

    private $options;
    private $other_seo_active = false;

    public function __construct( $options ) {
        $this->options = $options;
        $this->check_other_seo_plugins();
    }

    private function check_other_seo_plugins() {
        $other_plugins = array(
            'WPSEO_VERSION', 'AIOSEO_VERSION',
            'RANK_MATH_VERSION', 'SEOPRESS_VERSION',
        );
        foreach ( $other_plugins as $constant ) {
            if ( defined( $constant ) ) {
                $this->other_seo_active = true;
                break;
            }
        }
    }

    /**
     * JSON-LD çıktısı ver (wp_head priority 10)
     */
    public function output_schema() {
        if ( $this->other_seo_active ) return;

        $enabled = $this->options->get( 'enable_schema', true );
        if ( ! $enabled ) return;

        $schema_data = $this->build_schema_graph();
        if ( empty( $schema_data ) ) return;

        echo '<script type="application/ld+json">' . "\\n";
        echo wp_json_encode( $schema_data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        echo "\\n" . '</script>' . "\\n";
    }

    /**
     * Schema @graph yapısını oluştur
     */
    private function build_schema_graph() {
        $graph = array();

        // WebSite şeması (her sayfada)
        $graph[] = $this->get_website_schema();

        // Organization şeması
        $org_schema = $this->get_organization_schema();
        if ( ! empty( $org_schema ) ) {
            $graph[] = $org_schema;
        }

        // BreadcrumbList şeması
        $breadcrumb_enabled = $this->options->get( 'enable_breadcrumbs', true );
        if ( $breadcrumb_enabled ) {
            $breadcrumb_class = new Class_Breadcrumb_Schema( $this->options );
            $breadcrumb_schema = $breadcrumb_class->get_schema();
            if ( ! empty( $breadcrumb_schema ) ) {
                $graph[] = $breadcrumb_schema;
            }
        }

        // Singular sayfa için özel şema
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $schema_type = get_post_meta( $post_id, '_wpsm_schema_type', true );

            if ( empty( $schema_type ) || 'none' === $schema_type ) {
                $schema_type = $this->get_default_schema_type();
            }

            $schema_class = $this->get_schema_class( $schema_type );
            if ( $schema_class ) {
                $schema_data = $schema_class->get_schema( $post_id );
                if ( ! empty( $schema_data ) ) {
                    $graph[] = $schema_data;
                }
            }
        }

        $graph = apply_filters( 'wpsm_schema_graph', $graph );

        if ( empty( $graph ) ) return array();

        return array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );
    }

    /**
     * WebSite şeması (SearchAction ile)
     */
    private function get_website_schema() {
        return array(
            '@type'       => 'WebSite',
            '@id'         => home_url( '/#website' ),
            'url'         => home_url( '/' ),
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => array(
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => home_url( '/?s={search_term_string}' ),
                ),
                'query-input' => 'required name=search_term_string',
            ),
            'publisher' => array( '@id' => home_url( '/#organization' ) ),
        );
    }

    /**
     * Organization şeması (logo, sosyal profiller)
     */
    private function get_organization_schema() {
        $schema = array(
            '@type' => 'Organization',
            '@id'   => home_url( '/#organization' ),
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        );

        // Logo
        $logo_url = $this->get_site_logo_url();
        if ( ! empty( $logo_url ) ) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $logo_url,
            );
            $logo_id = get_theme_mod( 'custom_logo' );
            if ( $logo_id ) {
                $meta = wp_get_attachment_metadata( $logo_id );
                if ( $meta ) {
                    $schema['logo']['width']  = $meta['width'];
                    $schema['logo']['height'] = $meta['height'];
                }
            }
        }

        // Sosyal profiller
        $profiles = $this->get_social_profiles();
        if ( ! empty( $profiles ) ) {
            $schema['sameAs'] = $profiles;
        }

        return $schema;
    }

    private function get_default_schema_type() {
        if ( is_page() ) return 'WebPage';
        return $this->options->get( 'default_schema_type', 'Article' );
    }

    private function get_schema_class( $type ) {
        switch ( strtolower( $type ) ) {
            case 'article':
            case 'blogposting':
            case 'newsarticle':
                return new Class_Article_Schema( $this->options, $type );
            case 'webpage':
                return $this;
            default:
                return null;
        }
    }

    public function get_schema( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) return array();

        return array(
            '@type'       => 'WebPage',
            '@id'         => get_permalink( $post_id ) . '#webpage',
            'url'         => get_permalink( $post_id ),
            'name'        => get_the_title( $post_id ),
            'description' => $this->get_post_description( $post_id ),
            'publisher'   => array( '@id' => home_url( '/#organization' ) ),
            'breadcrumb'  => array( '@id' => get_permalink( $post_id ) . '#breadcrumb' ),
        );
    }

    private function get_site_logo_url() {
        $logo_id = get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            $url = wp_get_attachment_image_url( $logo_id, 'full' );
            if ( $url ) return $url;
        }
        return $this->options->get( 'organization_logo', '' );
    }

    private function get_social_profiles() {
        $profiles = array();
        $keys = array( 'social_facebook', 'social_twitter', 'social_instagram',
                       'social_linkedin', 'social_youtube', 'social_pinterest' );
        foreach ( $keys as $key ) {
            $url = $this->options->get( $key, '' );
            if ( ! empty( $url ) ) $profiles[] = esc_url( $url );
        }
        return $profiles;
    }

    private function get_post_description( $post_id ) {
        $desc = get_post_meta( $post_id, '_wpsm_description', true );
        if ( empty( $desc ) ) {
            $excerpt = get_the_excerpt( $post_id );
            if ( ! empty( $excerpt ) ) {
                $desc = wp_trim_words( $excerpt, 25, '...' );
            }
        }
        return $desc;
    }
}`,
  },
  {
    id: 'article',
    name: 'class-article-schema.php',
    path: 'includes/Schema/class-article-schema.php',
    description: 'Article/BlogPosting/NewsArticle şeması - author, publisher, image',
    language: 'php',
    code: `<?php
/**
 * Article Schema Sınıfı
 *
 * Article, BlogPosting, NewsArticle şemalarını oluşturur.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Article_Schema {

    private $options;
    private $schema_type;

    public function __construct( $options, $schema_type = 'Article' ) {
        $this->options     = $options;
        $this->schema_type = $schema_type;
    }

    /**
     * Article şemasını döndür
     */
    public function get_schema( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) return array();

        $schema = array(
            '@type'            => $this->get_schema_type(),
            '@id'              => get_permalink( $post_id ) . '#article',
            'isPartOf'         => array( '@id' => get_permalink( $post_id ) . '#webpage' ),
            'mainEntityOfPage' => array( '@id' => get_permalink( $post_id ) . '#webpage' ),
            'headline'         => $this->sanitize( get_the_title( $post_id ) ),
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_the_modified_date( 'c', $post_id ),
        );

        // Description
        $desc = $this->get_post_description( $post_id );
        if ( ! empty( $desc ) ) {
            $schema['description'] = $desc;
        }

        // Author (Person)
        $author = $this->get_author_schema( $post->post_author );
        if ( ! empty( $author ) ) {
            $schema['author'] = $author;
        }

        // Publisher (Organization)
        $schema['publisher'] = array( '@id' => home_url( '/#organization' ) );

        // Image
        $image = $this->get_article_image( $post_id );
        if ( ! empty( $image ) ) {
            $schema['image'] = $image;
            $schema['thumbnailUrl'] = $image['url'];
        }

        // Article section (kategori)
        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            $schema['articleSection'] = $this->sanitize( $categories[0]->name );
        }

        // Keywords (etiketler)
        $tags = get_the_tags( $post_id );
        if ( ! empty( $tags ) ) {
            $keywords = array_map( function( $tag ) {
                return $this->sanitize( $tag->name );
            }, $tags );
            $schema['keywords'] = implode( ', ', $keywords );
        }

        // Word count
        $schema['wordCount'] = str_word_count(
            wp_strip_all_tags( $post->post_content )
        );

        // Comment count
        $schema['commentCount'] = intval( $post->comment_count );

        // Breadcrumb
        $schema['breadcrumb'] = array(
            '@id' => get_permalink( $post_id ) . '#breadcrumb',
        );

        return apply_filters( 'wpsm_article_schema', $schema, $post_id );
    }

    private function get_schema_type() {
        $allowed = array( 'Article', 'BlogPosting', 'NewsArticle' );
        return in_array( $this->schema_type, $allowed, true )
            ? $this->schema_type : 'Article';
    }

    /**
     * Author (Person) şeması
     */
    private function get_author_schema( $author_id ) {
        $author = get_userdata( $author_id );
        if ( ! $author ) return array();

        $schema = array(
            '@type' => 'Person',
            'name'  => $this->sanitize( $author->display_name ),
        );

        $author_url = get_author_posts_url( $author_id );
        if ( ! empty( $author_url ) ) {
            $schema['url'] = esc_url( $author_url );
        }

        $description = get_the_author_meta( 'description', $author_id );
        if ( ! empty( $description ) ) {
            $schema['description'] = $this->sanitize( $description );
        }

        $avatar_url = get_avatar_url( $author_id, array( 'size' => 200 ) );
        if ( ! empty( $avatar_url ) ) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url'   => esc_url( $avatar_url ),
            );
        }

        // Sosyal profiller
        $profiles = array();
        $twitter = get_the_author_meta( 'twitter', $author_id );
        if ( ! empty( $twitter ) ) {
            $profiles[] = 'https://twitter.com/' . ltrim( $twitter, '@' );
        }
        $facebook = get_the_author_meta( 'facebook', $author_id );
        if ( ! empty( $facebook ) ) {
            if ( strpos( $facebook, 'http' ) !== 0 ) {
                $facebook = 'https://facebook.com/' . $facebook;
            }
            $profiles[] = esc_url( $facebook );
        }
        if ( ! empty( $profiles ) ) {
            $schema['sameAs'] = $profiles;
        }

        return $schema;
    }

    /**
     * Makale görseli
     * Öncelik: _wpsm_og_image > featured image > default_og_image
     */
    private function get_article_image( $post_id ) {
        // Özel OG görseli
        $og_image = get_post_meta( $post_id, '_wpsm_og_image', true );
        if ( ! empty( $og_image ) ) {
            return $this->build_image_object( $og_image );
        }

        // Featured image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            if ( $src ) {
                $alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                $image = array(
                    '@type'  => 'ImageObject',
                    'url'    => esc_url( $src[0] ),
                    'width'  => $src[1],
                    'height' => $src[2],
                );
                if ( ! empty( $alt ) ) {
                    $image['caption'] = $this->sanitize( $alt );
                }
                return $image;
            }
        }

        // Varsayılan
        $default = $this->options->get( 'default_og_image', '' );
        if ( ! empty( $default ) ) {
            return $this->build_image_object( $default );
        }

        return array();
    }

    private function build_image_object( $url ) {
        $image = array(
            '@type' => 'ImageObject',
            'url'   => esc_url( $url ),
        );
        $attachment_id = attachment_url_to_postid( $url );
        if ( $attachment_id ) {
            $meta = wp_get_attachment_metadata( $attachment_id );
            if ( $meta ) {
                $image['width']  = $meta['width'];
                $image['height'] = $meta['height'];
            }
            $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            if ( ! empty( $alt ) ) {
                $image['caption'] = $this->sanitize( $alt );
            }
        }
        return $image;
    }

    private function get_post_description( $post_id ) {
        $desc = get_post_meta( $post_id, '_wpsm_description', true );
        if ( empty( $desc ) ) {
            $excerpt = get_the_excerpt( $post_id );
            if ( ! empty( $excerpt ) ) {
                $desc = wp_strip_all_tags( $excerpt );
            }
        }
        if ( empty( $desc ) ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $desc = wp_trim_words( wp_strip_all_tags( $post->post_content ), 55, '...' );
            }
        }
        return $this->sanitize( $desc );
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
    }
}`,
  },
  {
    id: 'breadcrumb',
    name: 'class-breadcrumb-schema.php',
    path: 'includes/Schema/class-breadcrumb-schema.php',
    description: 'BreadcrumbList şeması - itemListElement, position, hiyerarşi',
    language: 'php',
    code: `<?php
/**
 * Breadcrumb Schema Sınıfı
 *
 * BreadcrumbList JSON-LD şemasını oluşturur.
 *
 * @package WPSM\\Schema
 * @since 1.0.0
 */

namespace WPSM\\Schema;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Class_Breadcrumb_Schema {

    private $options;

    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * BreadcrumbList şemasını döndür
     */
    public function get_schema() {
        $items = $this->get_breadcrumb_items();
        if ( empty( $items ) ) return array();

        $schema = array(
            '@type'           => 'BreadcrumbList',
            '@id'             => $this->get_current_url() . '#breadcrumb',
            'itemListElement' => array(),
        );

        $position = 1;
        foreach ( $items as $item ) {
            $list_item = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $this->sanitize( $item['name'] ),
            );
            if ( ! empty( $item['url'] ) ) {
                $list_item['item'] = esc_url( $item['url'] );
            }
            $schema['itemListElement'][] = $list_item;
            $position++;
        }

        return apply_filters( 'wpsm_breadcrumb_schema', $schema, $items );
    }

    /**
     * Breadcrumb öğelerini döndür
     * Ana sayfa > kategori > alt kategori > yazı
     */
    private function get_breadcrumb_items() {
        $items = array();

        // Ana sayfa (her zaman ilk)
        $home_text = $this->options->get( 'breadcrumb_home_text', 'Ana Sayfa' );
        $items[] = array( 'name' => $home_text, 'url' => home_url( '/' ) );

        // Singular sayfa
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post = get_post( $post_id );
            if ( ! $post ) return $items;

            // Post type arşivi
            $post_type = $post->post_type;
            $pt_obj = get_post_type_object( $post_type );
            if ( $pt_obj && $post_type !== 'post' && $post_type !== 'page' ) {
                $archive_link = get_post_type_archive_link( $post_type );
                if ( $archive_link ) {
                    $items[] = array(
                        'name' => $pt_obj->labels->name,
                        'url'  => $archive_link,
                    );
                }
            }

            // Kategori hiyerarşisi
            if ( 'post' === $post_type ) {
                $categories = get_the_category( $post_id );
                if ( ! empty( $categories ) ) {
                    $chain = $this->get_category_hierarchy( $categories[0] );
                    foreach ( $chain as $cat ) {
                        $items[] = array(
                            'name' => $cat->name,
                            'url'  => get_category_link( $cat->term_id ),
                        );
                    }
                }
            }

            // Sayfa ebeveyn hiyerarşisi
            if ( 'page' === $post_type ) {
                $parents = $this->get_page_hierarchy( $post_id );
                foreach ( $parents as $pid ) {
                    $items[] = array(
                        'name' => get_the_title( $pid ),
                        'url'  => get_permalink( $pid ),
                    );
                }
            }

            // Mevcut sayfa (son öğe)
            $bc_title = get_post_meta( $post_id, '_wpsm_breadcrumb_title', true );
            $items[] = array(
                'name' => ! empty( $bc_title ) ? $bc_title : get_the_title( $post_id ),
                'url'  => '',
            );
        }

        // Arşiv sayfaları
        if ( is_archive() ) {
            if ( is_category() ) {
                $cat = get_queried_object();
                $chain = $this->get_category_hierarchy( $cat );
                foreach ( $chain as $c ) {
                    $items[] = array(
                        'name' => $c->name,
                        'url'  => get_category_link( $c->term_id ),
                    );
                }
            }
            if ( is_tag() ) {
                $items[] = array( 'name' => get_queried_object()->name, 'url' => '' );
            }
            if ( is_author() ) {
                $items[] = array(
                    'name' => get_queried_object()->display_name, 'url' => ''
                );
            }
            if ( is_date() ) {
                if ( is_year() ) {
                    $items[] = array( 'name' => get_the_date( 'Y' ), 'url' => '' );
                } elseif ( is_month() ) {
                    $items[] = array(
                        'name' => get_the_date( 'Y' ),
                        'url'  => get_year_link( get_query_var( 'year' ) ),
                    );
                    $items[] = array( 'name' => get_the_date( 'F' ), 'url' => '' );
                }
            }
        }

        // Arama
        if ( is_search() ) {
            $items[] = array(
                'name' => sprintf( 'Arama: %s', get_search_query() ),
                'url'  => '',
            );
        }

        // 404
        if ( is_404() ) {
            $items[] = array( 'name' => 'Sayfa Bulunamadı', 'url' => '' );
        }

        return $items;
    }

    /**
     * Kategori hiyerarşisi (üstten alta)
     */
    private function get_category_hierarchy( $category ) {
        $chain = array();
        if ( ! $category ) return $chain;

        $parent_id = $category->parent;
        while ( $parent_id ) {
            $parent = get_category( $parent_id );
            if ( $parent && ! is_wp_error( $parent ) ) {
                array_unshift( $chain, $parent );
                $parent_id = $parent->parent;
            } else {
                break;
            }
        }
        $chain[] = $category;
        return $chain;
    }

    /**
     * Sayfa ebeveyn hiyerarşisi
     */
    private function get_page_hierarchy( $page_id ) {
        $chain = array();
        $page = get_post( $page_id );
        if ( ! $page ) return $chain;

        $parent_id = $page->post_parent;
        while ( $parent_id ) {
            array_unshift( $chain, $parent_id );
            $parent = get_post( $parent_id );
            $parent_id = $parent ? $parent->post_parent : 0;
        }
        return $chain;
    }

    private function get_current_url() {
        if ( is_singular() ) return get_permalink();
        if ( is_front_page() ) return home_url( '/' );
        if ( is_category() ) return get_category_link( get_queried_object_id() );
        if ( is_tag() ) return get_tag_link( get_queried_object_id() );
        if ( is_search() ) return get_search_link();
        return home_url( $_SERVER['REQUEST_URI'] );
    }

    private function sanitize( $string ) {
        if ( empty( $string ) ) return '';
        return sanitize_text_field( wp_strip_all_tags( $string ) );
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
            Schema.org JSON-LD Modülü
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            3 dosya: Schema Manager (@graph yapısı), Article Schema (author, publisher, image),
            Breadcrumb Schema (hiyerarşi). Google Rich Results Test uyumlu.
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

        {/* JSON-LD Output Preview */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">JSON-LD Çıktı Önizleme</h3>
          <div className="bg-gray-900 rounded-lg p-4 font-mono text-xs overflow-x-auto">
            <pre className="text-gray-300">
{`{
  `}<span className="text-green-300">"@context"</span>{`: `}<span className="text-blue-300">"https://schema.org"</span>{`,
  `}<span className="text-green-300">"@graph"</span>{`: [
    {
      `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"WebSite"</span>{`,
      `}<span className="text-green-300">"@id"</span>{`: `}<span className="text-blue-300">"https://example.com/#website"</span>{`,
      `}<span className="text-green-300">"url"</span>{`: `}<span className="text-blue-300">"https://example.com/"</span>{`,
      `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Site Adı"</span>{`,
      `}<span className="text-green-300">"potentialAction"</span>{`: {
        `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"SearchAction"</span>{`,
        `}<span className="text-green-300">"target"</span>{`: { ... }
      }
    },
    {
      `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"Organization"</span>{`,
      `}<span className="text-green-300">"@id"</span>{`: `}<span className="text-blue-300">"https://example.com/#organization"</span>{`,
      `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Site Adı"</span>{`,
      `}<span className="text-green-300">"logo"</span>{`: { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"ImageObject"</span>{`, ... }
    },
    {
      `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"BreadcrumbList"</span>{`,
      `}<span className="text-green-300">"@id"</span>{`: `}<span className="text-blue-300">"https://example.com/sayfa/#breadcrumb"</span>{`,
      `}<span className="text-green-300">"itemListElement"</span>{`: [
        { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"ListItem"</span>{`, `}<span className="text-green-300">"position"</span>{`: 1, `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Ana Sayfa"</span>{` },
        { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"ListItem"</span>{`, `}<span className="text-green-300">"position"</span>{`: 2, `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Kategori"</span>{` },
        { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"ListItem"</span>{`, `}<span className="text-green-300">"position"</span>{`: 3, `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Yazı"</span>{` }
      ]
    },
    {
      `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"Article"</span>{`,
      `}<span className="text-green-300">"@id"</span>{`: `}<span className="text-blue-300">"https://example.com/sayfa/#article"</span>{`,
      `}<span className="text-green-300">"headline"</span>{`: `}<span className="text-blue-300">"Yazı Başlığı"</span>{`,
      `}<span className="text-green-300">"datePublished"</span>{`: `}<span className="text-blue-300">"2024-01-15T10:00:00+00:00"</span>{`,
      `}<span className="text-green-300">"author"</span>{`: { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"Person"</span>{`, `}<span className="text-green-300">"name"</span>{`: `}<span className="text-blue-300">"Yazar Adı"</span>{` },
      `}<span className="text-green-300">"publisher"</span>{`: { `}<span className="text-green-300">"@id"</span>{`: `}<span className="text-blue-300">"https://example.com/#organization"</span>{` },
      `}<span className="text-green-300">"image"</span>{`: { `}<span className="text-green-300">"@type"</span>{`: `}<span className="text-blue-300">"ImageObject"</span>{`, ... }
    }
  ]
}`}
            </pre>
          </div>
        </div>

        {/* @id Yapısı */}
        <div className="mt-12 p-6 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-lg font-bold text-white mb-4">Benzersiz @id Yapısı</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {[
              { id: '#website', type: 'WebSite', desc: 'Her sayfada, SearchAction ile' },
              { id: '#organization', type: 'Organization', desc: 'Logo, sosyal profiller' },
              { id: '#breadcrumb', type: 'BreadcrumbList', desc: 'itemListElement dizisi' },
              { id: '#article', type: 'Article', desc: 'Author, publisher, image' },
            ].map((item, i) => (
              <div key={i} className="p-4 bg-white/[0.02] border border-white/5 rounded-xl">
                <code className="text-xs text-purple-300 bg-purple-500/10 px-2 py-1 rounded">{item.id}</code>
                <p className="text-sm font-medium text-white mt-2">{item.type}</p>
                <p className="text-xs text-gray-500 mt-1">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Features */}
        <div className="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { title: '@graph Yapısı', desc: 'Birden fazla şema tek JSON-LD\'de' },
            { title: 'WebSite + SearchAction', desc: 'Google sitelinks search box' },
            { title: 'Organization', desc: 'Logo, sameAs sosyal profiller' },
            { title: 'Article Schema', desc: 'Author (Person), publisher, image' },
            { title: 'BreadcrumbList', desc: 'Kategori/sayfa hiyerarşisi' },
            { title: 'wp_json_encode', desc: 'Güvenli JSON çıktısı' },
            { title: 'sanitize_text_field', desc: 'Tüm kullanıcı girdileri temiz' },
            { title: 'Rich Results Test', desc: 'Google uyumlu, hata yok' },
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
