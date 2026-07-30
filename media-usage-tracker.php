<?php
/**
 * Plugin Name:       Media Usage Tracker
 * Plugin URI:        https://github.com/dexter-adams/media-usage-tracker
 * Description:       Shows where WordPress media attachments are used, directly in attachment details.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Dexter Adams
 * Author URI:        https://dapd.net
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       media-usage-tracker
 */

defined( 'ABSPATH' ) || exit;

define( 'MEDIA_USAGE_TRACKER_VERSION', '2.0.0' );
define( 'MEDIA_USAGE_TRACKER_FILE', __FILE__ );
define( 'MEDIA_USAGE_TRACKER_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEDIA_USAGE_TRACKER_URL', plugin_dir_url( __FILE__ ) );

require_once MEDIA_USAGE_TRACKER_PATH . 'src/UsageFinder.php';
require_once MEDIA_USAGE_TRACKER_PATH . 'src/Renderer.php';
require_once MEDIA_USAGE_TRACKER_PATH . 'src/Plugin.php';

add_action( 'plugins_loaded', [ DAPD\MediaUsageTracker\Plugin::class, 'register' ] );
