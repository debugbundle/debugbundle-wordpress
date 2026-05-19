<?php
/**
 * Plugin Name:       DebugBundle
 * Plugin URI:        https://debugbundle.com/integrations/wordpress
 * Description:       Capture backend and frontend production incidents from WordPress into DebugBundle.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            DebugBundle
 * Author URI:        https://debugbundle.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       debugbundle
 * Domain Path:       /languages
 * Update URI:        https://github.com/debugbundle/debugbundle-wordpress
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    return;
}

require $autoload;

\DebugBundleWp\Plugin::boot(__FILE__);
