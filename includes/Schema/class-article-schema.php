<?php
/**
 * Article Schema Sınıfı
 * 
 * Article, BlogPosting ve NewsArticle schema oluşturur.
 *
 * @package WPSM\Schema
 * @since 1.0.0
 */

namespace WPSM\Schema;

// Doğrudan erişimi engelle
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Article Schema Sınıfı
 * 
 * Article, BlogPosting ve NewsArticle schema oluşturur.
 *
 * @since 1.0.0
 */
class Article_Schema {

	/**
	 * Article schema oluşturur
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object
	 * @param string   $type Schema tipi (Article, BlogPosting, NewsArticle)
	 * @return array Article schema
	 */
	public function get_article_schema( $post, $type = 'Article' ) {
		// Base schema
		$article_schema = array(
			'@context' => 'https://schema.org',
			'@type' => $type,
			'@id' => get_permalink( $post->ID ) . '#article',
			'url' => get_permalink( $post->ID ),
			'headline' => $post->post_title,
			'description' => wp_strip_all_tags( wp_trim_words( $post->post_content, 30, '...' ) ),
			'datePublished' => mysql2date( 'c', $post->post_date, false ),
			'dateModified' => mysql2date( 'c', $post->post_modified, false ),
			'mainEntityOfPage' => get_permalink( $post->ID ),
		);

		// Author
		$author = get_userdata( $post->post_author );
		if ( $author ) {
			$article_schema['author'] = array(
				'@type' => 'Person',
				'name' => $author->display_name,
				'url' => get_author_posts_url( $author->ID ),
			);
		}

		// Publisher (Organization)
		$article_schema['publisher'] = array(
			'@type' => 'Organization',
			'@id' => home_url( '/' ) . '#organization',
			'name' => get_bloginfo( 'name' ),
		);

		// Image
		if ( has_post_thumbnail( $post->ID ) ) {
			$thumb_id = get_post_thumbnail_id( $post->ID );
			$image = wp_get_attachment_image_src( $thumb_id, 'full' );
			$image_alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );

			if ( $image ) {
				$article_schema['image'] = array(
					'@type' => 'ImageObject',
					'url' => $image[0],
					'width' => $image[1],
					'height' => $image[2],
					'caption' => $image_alt,
				);
			}
		}

		// Category
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$article_schema['articleSection'] = $categories[0]->name;
		}

		// Tags
		$tags = get_the_tags( $post->ID );
		if ( $tags ) {
			$tag_names = array();
			foreach ( $tags as $tag ) {
				$tag_names[] = $tag->name;
			}
			$article_schema['keywords'] = implode( ',', $tag_names );
		}

		// Word count
		$word_count = str_word_count( strip_tags( $post->post_content ) );
		$article_schema['articleBody'] = $post->post_content; // Only if needed
		$article_schema['wordCount'] = $word_count;

		// Comment count
		$comment_count = get_comments_number( $post->ID );
		$article_schema['commentCount'] = $comment_count;

		return $article_schema;
	}
}
