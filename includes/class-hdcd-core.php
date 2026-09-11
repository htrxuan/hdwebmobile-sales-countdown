<?php

namespace htrxuan\hdcd;

if (!defined('ABSPATH')) {
    exit;
}

final class HDCD_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDCD_PLUGIN_DIR . 'includes/class-hdcd-admin.php';
        require_once HDCD_PLUGIN_DIR . 'includes/class-hdcd-display.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        HDCD_Admin::get_instance();
        HDCD_Display::get_instance();
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdcd_wc_missing_notice')) {
            return;
        }
        delete_transient('hdcd_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Sales Countdown requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-sales-countdown'); ?>
            </p>
        </div>
        <?php
    }
}
