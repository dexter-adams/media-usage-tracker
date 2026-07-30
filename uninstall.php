<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$ids = get_option( 'mut_cached_attachment_ids', [] );
if ( is_array( $ids ) ) {
    foreach ( $ids as $attachment_id ) {
        delete_transient( 'mut_usage_' . absint( $attachment_id ) );
    }
}

delete_option( 'mut_cached_attachment_ids' );
