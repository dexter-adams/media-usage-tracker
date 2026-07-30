# Media Usage Tracker

[![CI](https://github.com/dexter-adams/media-usage-tracker/actions/workflows/ci.yml/badge.svg)](https://github.com/dexter-adams/media-usage-tracker/actions/workflows/ci.yml)

Media Usage Tracker adds a read-only **Used In** field to WordPress attachment
details. Editors can see which editable posts use an image as a featured image
or reference it in post content, then jump directly to those posts.

## About this code sample

**What I wrote.** This plugin, version 2.0, rebuilt as a standalone tool from a
site-specific version I had originally written for one client's site. The
earlier version worked, but only there: it named that site's post types and
assumed its theme. This version discovers what to search at runtime and works
on any WordPress install.

**Why I wrote it.** Editors kept asking a question the Media Library cannot
answer: *is anything still using this image?* Without an answer, nobody deletes
anything, and the uploads directory grows forever — or someone deletes
confidently and breaks a page nobody thought to check. The honest version of
that answer is harder than it sounds, because WordPress stores the same
relationship several different ways: as `_thumbnail_id` metadata, as a block
attribute holding an attachment ID, as a classic-editor `wp-image-123` class,
as a gallery ID list, and as a bare URL pasted into content. A tool that finds
only one of those is worse than no tool, because it reports "unused" about
something that is used.

**Why I am proud of it.** The caching. The obvious implementation caches the
rendered "Used In" list, which quietly turns a cache into a permissions leak —
the first editor to view an attachment decides what every later viewer sees,
including drafts and private posts they should not know about. So the cached
value here holds post IDs and nothing else; the capability filtering and the
HTML are rebuilt per user, per request. Caching the expensive part while
refusing to cache the part that depends on *who is asking* is a small
distinction that matters, and it is the kind of thing that is very hard to
retrofit later.

**What it demonstrates.** Prepared statements for SQL whose shape is built at
runtime, bounded result sets, cache invalidation wired to the events that
actually invalidate it, capability-aware rendering, and escaped admin output.
It also shows restraint: the detection notes below state plainly what this
approach cannot find, because a media audit tool that overstates its coverage
is dangerous rather than merely incomplete.

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
