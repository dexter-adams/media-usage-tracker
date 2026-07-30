<?php

namespace DAPD\MediaUsageTracker;

defined( 'ABSPATH' ) || exit;

/**
 * Finds attachment references without depending on a theme or custom post type.
 */
final class UsageFinder {

    const CACHE_PREFIX     = 'mut_usage_';
    const CACHE_INDEX      = 'mut_cached_attachment_ids';
    const CACHE_TTL        = 15 * MINUTE_IN_SECONDS;
    const DEFAULT_MAX_ROWS = 200;

    /**
     * @return array{featured:int[],content:int[]}
     */
    public static function find( int $attachment_id ): array {
        $attachment_id = absint( $attachment_id );
        if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
            return [ 'featured' => [], 'content' => [] ];
        }

        $cache_key = self::CACHE_PREFIX . $attachment_id;
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) &&
            isset( $cached['featured'], $cached['content'] )
        ) {
            return $cached;
        }

        $usage = [
            'featured' => self::find_featured_image_usage( $attachment_id ),
            'content'  => self::find_content_usage( $attachment_id ),
        ];

        set_transient( $cache_key, $usage, self::CACHE_TTL );
        self::remember_cached_attachment( $attachment_id );

        return $usage;
    }

    /**
     * @return int[]
     */
    private static function find_featured_image_usage( int $attachment_id ): array {
        global $wpdb;

        $limit = self::max_rows();
        $ids   = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_thumbnail_id'
                    AND meta_value = %d
                ORDER BY post_id DESC
                LIMIT %d",
                $attachment_id,
                $limit
            )
        );

        return self::normalize_ids( $ids );
    }

    /**
     * @return int[]
     */
    private static function find_content_usage( int $attachment_id ): array {
        global $wpdb;

        $url = wp_get_attachment_url( $attachment_id );
        if ( ! $url ) {
            return [];
        }

        $post_types = self::searchable_post_types();
        if ( empty( $post_types ) ) {
            return [];
        }

        $needles           = self::content_needles( $attachment_id, $url );
        $type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $like_clauses      = implode( ' OR ', array_fill( 0, count( $needles ), 'post_content LIKE %s' ) );
        $limit             = self::max_rows();

        $sql = "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type IN ({$type_placeholders})
                AND post_status NOT IN ('trash', 'auto-draft', 'inherit')
                AND ({$like_clauses})
            ORDER BY ID DESC
            LIMIT %d";

        $args = array_merge(
            $post_types,
            array_map(
                static fn ( string $needle ): string => '%' . $wpdb->esc_like( $needle ) . '%',
                $needles
            ),
            [ $limit ]
        );

        $ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$args ) );

        return self::normalize_ids( $ids );
    }

    /**
     * Reference patterns used by core image and gallery blocks plus classic
     * editor image markup.
     *
     * @return string[]
     */
    public static function content_needles( int $attachment_id, string $url ): array {
        return array_values(
            array_unique(
                array_filter(
                    [
                        $url,
                        'wp-image-' . $attachment_id,
                        '"id":' . $attachment_id,
                        '"id": ' . $attachment_id,
                        '"ids":[' . $attachment_id,
                        ',' . $attachment_id . ',',
                        ',' . $attachment_id . ']',
                    ]
                )
            )
        );
    }

    /**
     * @return string[]
     */
    private static function searchable_post_types(): array {
        $post_types = get_post_types( [ 'show_ui' => true ], 'names' );
        $excluded   = [ 'attachment', 'revision', 'nav_menu_item', 'wp_template', 'wp_template_part' ];
        $post_types = array_values( array_diff( $post_types, $excluded ) );

        /**
         * Filters post types searched for content references.
         *
         * @param string[] $post_types Public/admin-visible post type names.
         */
        return array_values(
            array_filter(
                (array) apply_filters( 'media_usage_tracker_post_types', $post_types ),
                'post_type_exists'
            )
        );
    }

    /**
     * Clear every attachment-usage transient created by this plugin.
     */
    public static function flush_all(): void {
        $ids = get_option( self::CACHE_INDEX, [] );
        if ( is_array( $ids ) ) {
            foreach ( $ids as $attachment_id ) {
                delete_transient( self::CACHE_PREFIX . absint( $attachment_id ) );
            }
        }
        delete_option( self::CACHE_INDEX );
    }

    /**
     * @param mixed[] $ids
     * @return int[]
     */
    public static function normalize_ids( array $ids ): array {
        return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
    }

    private static function remember_cached_attachment( int $attachment_id ): void {
        $ids   = get_option( self::CACHE_INDEX, [] );
        $ids   = is_array( $ids ) ? self::normalize_ids( $ids ) : [];
        $ids[] = $attachment_id;
        update_option( self::CACHE_INDEX, self::normalize_ids( $ids ), false );
    }

    private static function max_rows(): int {
        return max(
            1,
            min(
                1000,
                (int) apply_filters( 'media_usage_tracker_max_results', self::DEFAULT_MAX_ROWS )
            )
        );
    }
}
