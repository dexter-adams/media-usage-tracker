<?php

namespace DAPD\MediaUsageTracker;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress integration for the attachment details panel.
 */
final class Plugin {

    public static function register(): void {
        add_filter( 'attachment_fields_to_edit', [ self::class, 'add_usage_field' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_styles' ] );

        add_action( 'save_post', [ UsageFinder::class, 'flush_all' ] );
        add_action( 'deleted_post', [ UsageFinder::class, 'flush_all' ] );
        add_action( 'delete_attachment', [ UsageFinder::class, 'flush_all' ] );
        add_action( 'added_post_meta', [ self::class, 'maybe_flush_thumbnail_cache' ], 10, 4 );
        add_action( 'updated_post_meta', [ self::class, 'maybe_flush_thumbnail_cache' ], 10, 4 );
        add_action( 'deleted_post_meta', [ self::class, 'maybe_flush_thumbnail_cache' ], 10, 4 );
    }

    /**
     * Add a read-only "Used In" field to attachment details.
     *
     * @param array    $form_fields Existing attachment fields.
     * @param \WP_Post $attachment  Attachment post object.
     */
    public static function add_usage_field( array $form_fields, \WP_Post $attachment ): array {
        if ( 'attachment' !== $attachment->post_type ||
            ! current_user_can( 'edit_post', $attachment->ID )
        ) {
            return $form_fields;
        }

        $usage = UsageFinder::find( $attachment->ID );

        $form_fields['media_usage_tracker'] = [
            'label' => __( 'Used In', 'media-usage-tracker' ),
            'input' => 'html',
            'html'  => Renderer::usage_list( $usage ),
            'helps' => __( 'Editable content that currently references this media item.', 'media-usage-tracker' ),
        ];

        return $form_fields;
    }

    public static function enqueue_admin_styles( string $hook ): void {
        if ( ! in_array( $hook, [ 'upload.php', 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        wp_enqueue_style(
            'media-usage-tracker',
            MEDIA_USAGE_TRACKER_URL . 'assets/admin.css',
            [],
            MEDIA_USAGE_TRACKER_VERSION
        );
    }

    /**
     * Invalidate cached usage when featured-image metadata changes outside a
     * normal post save.
     *
     * @param int    $meta_id    Metadata row ID.
     * @param int    $object_id  Post ID.
     * @param string $meta_key   Metadata key.
     * @param mixed  $meta_value Metadata value.
     */
    public static function maybe_flush_thumbnail_cache( $meta_id, $object_id, $meta_key, $meta_value ): void {
        unset( $meta_id, $object_id, $meta_value );

        if ( '_thumbnail_id' === $meta_key ) {
            UsageFinder::flush_all();
        }
    }
}
