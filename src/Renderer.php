<?php

namespace DAPD\MediaUsageTracker;

defined( 'ABSPATH' ) || exit;

/**
 * Renders user-specific, escaped admin HTML from user-neutral cached IDs.
 */
final class Renderer {

    /**
     * @param array{featured:int[],content:int[]} $usage
     */
    public static function usage_list( array $usage ): string {
        $featured = UsageFinder::normalize_ids( $usage['featured'] ?? [] );
        $content  = UsageFinder::normalize_ids( $usage['content'] ?? [] );
        $all_ids  = array_values( array_unique( array_merge( $featured, $content ) ) );

        if ( empty( $all_ids ) ) {
            return self::empty_message();
        }

        $posts = get_posts(
            [
                'post__in'               => $all_ids,
                'post_type'              => 'any',
                'post_status'            => 'any',
                'posts_per_page'         => count( $all_ids ),
                'orderby'                => 'post_type',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );

        $items = [];
        foreach ( $posts as $post ) {
            if ( ! current_user_can( 'edit_post', $post->ID ) ) {
                continue;
            }

            $edit_link = get_edit_post_link( $post->ID, 'raw' );
            if ( ! $edit_link ) {
                continue;
            }

            $types = [];
            if ( in_array( $post->ID, $featured, true ) ) {
                $types[] = __( 'Featured image', 'media-usage-tracker' );
            }
            if ( in_array( $post->ID, $content, true ) ) {
                $types[] = __( 'In content', 'media-usage-tracker' );
            }

            $post_type_object = get_post_type_object( $post->post_type );
            $post_type_label  = $post_type_object
                ? $post_type_object->labels->singular_name
                : $post->post_type;
            $status_object    = get_post_status_object( $post->post_status );
            $status_label     = $status_object ? $status_object->label : $post->post_status;

            $items[] = sprintf(
                '<li class="mut-item status-%1$s"><a href="%2$s" target="_blank" rel="noopener noreferrer"><strong>%3$s</strong></a><span class="mut-meta">%4$s &middot; %5$s &middot; %6$s</span></li>',
                esc_attr( sanitize_html_class( $post->post_status ) ),
                esc_url( $edit_link ),
                esc_html( $post->post_title ?: __( '(no title)', 'media-usage-tracker' ) ),
                esc_html( $post_type_label ),
                esc_html( $status_label ),
                esc_html( implode( ', ', $types ) )
            );
        }

        if ( empty( $items ) ) {
            return self::empty_message();
        }

        return sprintf(
            '<div class="mut-list"><ul>%1$s</ul><p class="mut-count">%2$s</p></div>',
            implode( '', $items ),
            esc_html(
                sprintf(
                    /* translators: %d: number of editable content items. */
                    _n( 'Used in %d editable item', 'Used in %d editable items', count( $items ), 'media-usage-tracker' ),
                    count( $items )
                )
            )
        );
    }

    private static function empty_message(): string {
        return '<p class="mut-none">' .
            esc_html__( 'No editable posts currently reference this media item.', 'media-usage-tracker' ) .
            '</p>';
    }
}
