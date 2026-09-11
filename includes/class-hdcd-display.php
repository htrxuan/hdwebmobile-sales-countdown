<?php

namespace htrxuan\hdcd;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the sale countdown, the store-wide bar, and the low-stock label.
 *
 * Countdown-timer plugins have repeatedly shipped stored XSS (a label/text field rendered
 * unescaped) and CSRF settings writes. This plugin gives both nowhere to live:
 *   - Every string comes from HDCD_Admin's settings, saved through the WordPress Settings
 *     API (its own manage_options + nonce) and sanitize_text_field(); it is printed with
 *     esc_html()/esc_attr() here. There is no rich-text field and nothing a shopper submits.
 *   - The countdown tick runs in assets/js/hdcd-countdown.js, which only ever reads a
 *     numeric ISO timestamp from a data- attribute and writes element.textContent. It never
 *     assigns innerHTML and never injects markup.
 *   - The "only N left" number is $product->get_stock_quantity() -- WooCommerce's real
 *     stock. It is never a value typed by an admin or supplied in a request.
 */
final class HDCD_Display
{

    private static $instance = null;
    private static $sale_done  = false;
    private static $stock_done = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'assets'));

        // Classic product template.
        add_action('woocommerce_single_product_summary', array($this, 'render_sale_countdown'), 11);
        add_action('woocommerce_single_product_summary', array($this, 'render_stock_label'), 12);
        // Block product template fallback.
        add_filter('render_block', array($this, 'block_fallback'), 10, 2);
        // Store-wide bar.
        add_action('wp_body_open', array($this, 'render_store_bar'));
        add_action('wp_footer', array($this, 'render_store_bar_footer_fallback'));
    }

    public function assets()
    {
        wp_enqueue_style('hdcd', HDCD_PLUGIN_URL . 'assets/css/hdcd.css', array(), HDCD_VERSION);
        wp_enqueue_script('hdcd', HDCD_PLUGIN_URL . 'assets/js/hdcd-countdown.js', array(), HDCD_VERSION, true);
    }

    /* ---------- per-product sale countdown ---------- */

    public function render_sale_countdown()
    {
        if (self::$sale_done) {
            return;
        }
        global $product;
        if (!$product instanceof \WC_Product || !HDCD_Admin::get_option('sale_countdown_enabled')) {
            return;
        }
        $to = $product->get_date_on_sale_to();
        if (!$to || !$product->is_on_sale()) {
            return;
        }
        $deadline = $to->getTimestamp();
        if ($deadline <= time()) {
            return;
        }
        self::$sale_done = true;

        printf(
            '<div class="hdcd-countdown hdcd-countdown--sale"><span class="hdcd-countdown__label">%s</span> <span class="hdcd-countdown__timer" data-hdcd-deadline="%s">%s</span></div>',
            esc_html(HDCD_Admin::get_option('sale_countdown_label')),
            esc_attr(gmdate('c', $deadline)),
            esc_html($this->fallback_remaining($deadline))
        );
    }

    /* ---------- low-stock label ---------- */

    public function render_stock_label()
    {
        if (self::$stock_done) {
            return;
        }
        global $product;
        if (!$product instanceof \WC_Product || !HDCD_Admin::get_option('scarcity_enabled')) {
            return;
        }
        $qty = $this->live_stock_quantity($product);
        if (null === $qty) {
            return;
        }
        $threshold = (int) HDCD_Admin::get_option('scarcity_threshold');
        if ($qty < 1 || $qty > $threshold) {
            return;
        }
        self::$stock_done = true;

        // {n} is replaced with the REAL stock quantity, cast to int, then the whole string is escaped.
        $label = str_replace('{n}', (string) (int) $qty, (string) HDCD_Admin::get_option('scarcity_label'));
        echo '<div class="hdcd-scarcity">' . esc_html($label) . '</div>';
    }

    /**
     * The product's real, current stock quantity, or null if it can't be shown as a count
     * (stock not managed, backorders, variable parent, etc.).
     */
    private function live_stock_quantity($product)
    {
        if (!$product->managing_stock() || $product->is_type('variable')) {
            return null;
        }
        if ('instock' !== $product->get_stock_status()) {
            return null;
        }
        if ($product->backorders_allowed()) {
            return null;
        }
        $qty = $product->get_stock_quantity();
        return is_numeric($qty) ? (int) $qty : null;
    }

    /* ---------- block-theme fallback ---------- */

    /**
     * Block product templates fire neither woocommerce_single_product_summary reliably nor
     * the classic add-to-cart hooks. Append both blocks after the add-to-cart-form block.
     */
    public function block_fallback($block_content, $block)
    {
        $name = is_array($block) && isset($block['blockName']) ? $block['blockName'] : '';
        if ('woocommerce/add-to-cart-form' !== $name || !is_singular('product')) {
            return $block_content;
        }
        $extra = '';
        if (!self::$sale_done) {
            ob_start();
            $this->render_sale_countdown();
            $extra .= ob_get_clean();
        }
        if (!self::$stock_done) {
            ob_start();
            $this->render_stock_label();
            $extra .= ob_get_clean();
        }
        return $block_content . $extra;
    }

    /* ---------- store-wide bar ---------- */

    public function render_store_bar()
    {
        if (is_admin() || defined('REST_REQUEST')) {
            return;
        }
        static $done = false;
        if ($done) {
            return;
        }
        $deadline = (string) HDCD_Admin::get_option('store_deadline');
        if ('' === $deadline) {
            return;
        }
        $ts = strtotime($deadline);
        if (!$ts || $ts <= time()) {
            return;
        }
        $done = true;

        printf(
            '<div class="hdcd-store-bar"><span class="hdcd-store-bar__text">%s</span> <span class="hdcd-countdown__timer" data-hdcd-deadline="%s">%s</span></div>',
            esc_html(HDCD_Admin::get_option('store_label')),
            esc_attr(gmdate('c', $ts)),
            esc_html($this->fallback_remaining($ts))
        );
    }

    public function render_store_bar_footer_fallback()
    {
        // Only used if the theme has no wp_body_open (older themes). wp_body_open is standard
        // since WP 5.2, so this is a belt-and-braces path.
        if (!did_action('wp_body_open')) {
            $this->render_store_bar();
        }
    }

    /* ---------- helpers ---------- */

    private function fallback_remaining($deadline_ts)
    {
        $secs = max(0, $deadline_ts - time());
        $d    = intdiv($secs, DAY_IN_SECONDS);
        $h    = intdiv($secs % DAY_IN_SECONDS, HOUR_IN_SECONDS);
        $m    = intdiv($secs % HOUR_IN_SECONDS, MINUTE_IN_SECONDS);
        $s    = $secs % MINUTE_IN_SECONDS;
        if ($d > 0) {
            /* translators: 1: days 2: hours 3: minutes */
            return sprintf(__('%1$dd %2$dh %3$dm', 'hdwebmobile-sales-countdown'), $d, $h, $m);
        }
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
