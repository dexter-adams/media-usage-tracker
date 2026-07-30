<?php

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

function absint( $value ): int {
    return abs( (int) $value );
}

require_once dirname( __DIR__ ) . '/src/UsageFinder.php';

use DAPD\MediaUsageTracker\UsageFinder;

$failures = 0;

$assert_same = static function ( $expected, $actual, string $name ) use ( &$failures ): void {
    if ( $expected === $actual ) {
        fwrite( STDOUT, "PASS {$name}\n" );
        return;
    }

    ++$failures;
    fwrite(
        STDERR,
        "FAIL {$name}\nExpected: " . var_export( $expected, true ) .
        "\nActual: " . var_export( $actual, true ) . "\n"
    );
};

$assert_same(
    [ 3, 8, 2 ],
    UsageFinder::normalize_ids( [ '3', 8, 3, 0, -2 ] ),
    'normalizes and deduplicates post IDs'
);

$needles = UsageFinder::content_needles(
    42,
    'https://example.test/wp-content/uploads/photo.jpg'
);

$assert_same(
    true,
    in_array( 'wp-image-42', $needles, true ),
    'detects classic editor image classes'
);
$assert_same(
    true,
    in_array( '"id":42', $needles, true ),
    'detects compact core block IDs'
);
$assert_same(
    true,
    in_array( '"id": 42', $needles, true ),
    'detects spaced core block IDs'
);
$assert_same(
    true,
    in_array( 'https://example.test/wp-content/uploads/photo.jpg', $needles, true ),
    'detects direct attachment URLs'
);

fwrite( STDOUT, sprintf( "5 tests, %d failures\n", $failures ) );
exit( $failures > 0 ? 1 : 0 );
