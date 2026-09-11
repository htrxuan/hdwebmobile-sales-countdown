<?php

/**
 * Plugin Name: HDWebmobile Sales Countdown
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-sales-countdown/
 * Description: Sale countdown timers and low-stock urgency labels. The "only N left" figure is read live from real stock, and every label is admin-only and escaped.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-sales-countdown
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdcd;

if (!defined('ABSPATH')) {
    exit;
}

define('HDCD_VERSION', '1.0.0');
define('HDCD_PLUGIN_FILE', __FILE__);
define('HDCD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDCD_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDCD_PLUGIN_DIR . 'includes/class-hdcd-activator.php';

register_activation_hook(__FILE__, array(HDCD_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDCD_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDCD_PLUGIN_DIR . 'includes/class-hdcd-core.php';
    HDCD_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-sales-countdown') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
