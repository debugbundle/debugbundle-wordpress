<?php
/**
 * Plugin Name:       DebugBundle
 * Plugin URI:        https://debugbundle.com/docs/integrations/wordpress
 * Description:       Capture backend and frontend production incidents from WordPress into DebugBundle.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            DebugBundle
 * Author URI:        https://debugbundle.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       debugbundle
 * Domain Path:       /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DEBUGBUNDLE_WORDPRESS_VERSION')) {
    define('DEBUGBUNDLE_WORDPRESS_VERSION', '1.0.0');
}

if (!defined('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION')) {
    define('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION', '1.0.0');
}

$debugbundleAutoload = __DIR__ . '/vendor/autoload.php';
if (!is_readable($debugbundleAutoload)) {
    return;
}

require $debugbundleAutoload;

\DebugBundleWp\Plugin::boot(__FILE__);
