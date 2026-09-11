<?php

namespace htrxuan\hdcd;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The hub tab. All configuration is written through the WordPress Settings API
 * (`options.php`), which performs its own `manage_options` capability check and nonce
 * verification. There is no custom write path, no AJAX, and nothing a shopper can submit.
 * Every stored string is passed through sanitize_text_field() here and esc_html()/esc_attr()
 * at the point of output -- there is no rich-text or HTML field.
 */
class HDCD_Admin
{
    const OPTION_KEY = 'hdcd_settings';

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
        require_once HDCD_PLUGIN_DIR . 'includes/class-hdcd-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function defaults()
    {
        return array(
            'sale_countdown_enabled' => 1,
            'sale_countdown_label'   => __('Sale ends in', 'hdwebmobile-sales-countdown'),
            'scarcity_enabled'       => 1,
            'scarcity_threshold'     => 8,
            'scarcity_label'         => __('Only {n} left in stock', 'hdwebmobile-sales-countdown'),
            'store_deadline'         => '',
            'store_label'            => __('Flash sale ends soon!', 'hdwebmobile-sales-countdown'),
        );
    }

    public static function get_options()
    {
        $opts = get_option(self::OPTION_KEY, array());
        return wp_parse_args(is_array($opts) ? $opts : array(), self::defaults());
    }

    public static function get_option($key)
    {
        $opts = self::get_options();
        return isset($opts[$key]) ? $opts[$key] : null;
    }

    public function register_settings()
    {
        register_setting('hdcd_group', self::OPTION_KEY, array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize'),
            'default'           => self::defaults(),
        ));
    }

    public function sanitize($input)
    {
        $d   = self::defaults();
        $out = array();

        $out['sale_countdown_enabled'] = !empty($input['sale_countdown_enabled']) ? 1 : 0;
        $out['sale_countdown_label']   = isset($input['sale_countdown_label']) ? sanitize_text_field($input['sale_countdown_label']) : $d['sale_countdown_label'];

        $out['scarcity_enabled']   = !empty($input['scarcity_enabled']) ? 1 : 0;
        $out['scarcity_threshold'] = isset($input['scarcity_threshold']) ? max(1, min(999, absint($input['scarcity_threshold']))) : $d['scarcity_threshold'];
        $out['scarcity_label']     = isset($input['scarcity_label']) ? sanitize_text_field($input['scarcity_label']) : $d['scarcity_label'];

        // A datetime-local value: keep only if it parses to a real future-or-past instant.
        $raw_deadline = isset($input['store_deadline']) ? sanitize_text_field($input['store_deadline']) : '';
        $out['store_deadline'] = ('' !== $raw_deadline && false !== strtotime($raw_deadline)) ? $raw_deadline : '';
        $out['store_label']    = isset($input['store_label']) ? sanitize_text_field($input['store_label']) : $d['store_label'];

        return $out;
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['sales-countdown'] = array(
            'label'  => __('Sales Countdown', 'hdwebmobile-sales-countdown'),
            'order'  => 47,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function render_page()
    {
        $o = self::get_options();
        ?>
        <p><?php esc_html_e('Show a countdown on products that have a scheduled sale end date, an optional store-wide countdown bar, and a "only N left" label driven by real stock levels.', 'hdwebmobile-sales-countdown'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('hdcd_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Per-product sale countdown', 'hdwebmobile-sales-countdown'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[sale_countdown_enabled]" value="1" <?php checked(!empty($o['sale_countdown_enabled'])); ?> /> <?php esc_html_e('Show a countdown on products whose sale has a scheduled end date', 'hdwebmobile-sales-countdown'); ?></label>
                        <p><label><?php esc_html_e('Label', 'hdwebmobile-sales-countdown'); ?><br />
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[sale_countdown_label]" value="<?php echo esc_attr($o['sale_countdown_label']); ?>" />
                        </label></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Low-stock label', 'hdwebmobile-sales-countdown'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[scarcity_enabled]" value="1" <?php checked(!empty($o['scarcity_enabled'])); ?> /> <?php esc_html_e('Show a low-stock label on products with managed stock at or below the threshold', 'hdwebmobile-sales-countdown'); ?></label>
                        <p>
                            <label><?php esc_html_e('Threshold', 'hdwebmobile-sales-countdown'); ?>
                                <input type="number" min="1" max="999" step="1" class="small-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[scarcity_threshold]" value="<?php echo esc_attr($o['scarcity_threshold']); ?>" />
                            </label>
                        </p>
                        <p><label><?php esc_html_e('Label', 'hdwebmobile-sales-countdown'); ?> (<?php esc_html_e('use {n} for the live stock count', 'hdwebmobile-sales-countdown'); ?>)<br />
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[scarcity_label]" value="<?php echo esc_attr($o['scarcity_label']); ?>" />
                        </label></p>
                        <p class="description"><?php esc_html_e('The number is always the product\'s real remaining stock -- it is never a fixed or made-up figure.', 'hdwebmobile-sales-countdown'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Store-wide countdown bar', 'hdwebmobile-sales-countdown'); ?></th>
                    <td>
                        <p><label><?php esc_html_e('Ends at', 'hdwebmobile-sales-countdown'); ?><br />
                            <input type="datetime-local" name="<?php echo esc_attr(self::OPTION_KEY); ?>[store_deadline]" value="<?php echo esc_attr($o['store_deadline']); ?>" />
                        </label></p>
                        <p><label><?php esc_html_e('Bar text', 'hdwebmobile-sales-countdown'); ?><br />
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[store_label]" value="<?php echo esc_attr($o['store_label']); ?>" />
                        </label></p>
                        <p class="description"><?php esc_html_e('Leave "Ends at" blank to hide the bar. It hides itself once the time passes.', 'hdwebmobile-sales-countdown'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Countdown Settings', 'hdwebmobile-sales-countdown')); ?>
        </form>
        <?php
    }
}
