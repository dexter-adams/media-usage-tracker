# Media Usage Tracker

Media Usage Tracker adds a read-only **Used In** field to WordPress attachment
details. Editors can see which editable posts use an image as a featured image
or reference it in post content, then jump directly to those posts.

## Standalone by design

The plugin depends only on WordPress core:

- searchable post types are discovered dynamically from registered post types;
- no theme names or custom post types are hard-coded;
- core image/gallery block IDs, classic-editor classes, and attachment URLs are
  recognized;
- cached query results contain IDs only, while capability-sensitive HTML is
  rendered for the current user; and
- cache entries are invalidated when posts, attachments, or featured-image
  metadata change.

This is a compact example of prepared dynamic SQL, capability-aware rendering,
bounded queries, cache invalidation, and escaped WordPress admin output.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer

## Installation

1. Copy this directory to `wp-content/plugins/media-usage-tracker`.
2. Activate **Media Usage Tracker**.
3. Open the Media Library and select an attachment.

## Filters

Control which post types are searched:

```php
add_filter(
    'media_usage_tracker_post_types',
    static fn ( array $post_types ): array => [ 'post', 'page', 'product' ]
);
```

Bound the number of matches returned by either query (default `200`, maximum
`1000`):

```php
add_filter( 'media_usage_tracker_max_results', static fn (): int => 100 );
```

## Detection notes

Content detection searches for the attachment URL and common core block/class
references. It is deliberately conservative: media references stored only in
arbitrary custom fields or proprietary page-builder tables require an
integration specific to that storage model.

## Development

```bash
php tests/run.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
