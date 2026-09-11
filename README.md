# HDWebmobile Sales Countdown

Sale countdown timers and honest low-stock labels for WooCommerce. The "only N left" figure is read live from real stock, and every label is admin-only and escaped.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-sales-countdown/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

* **Per-product sale countdown** — on products whose sale has a scheduled end date.
* **Store-wide countdown bar** — set an end time and a line of text; it hides itself when time runs out.
* **Low-stock label** — "Only 3 left in stock" on products with managed stock at/below a threshold.

## Why this plugin exists

Countdown / scarcity plugins keep shipping two problems; this one is built so neither can happen:

* **No stored XSS from a timer label.** Every string is set only on the settings page, saved via the WordPress Settings API (`manage_options` + nonce), `sanitize_text_field()`'d, and printed with `esc_html()`/`esc_attr()`. No rich-text field. The countdown script only reads a numeric timestamp from a `data-` attribute and only writes `textContent` — never `innerHTML`.
* **No fake scarcity.** The "only N left" number is always `WC_Product::get_stock_quantity()` — WooCommerce's real stock. Never typed in, never from a request. No count is shown for unmanaged stock, backorders, or variable parents.

## Features

* Per-product sale countdown from WooCommerce's own "Sale price dates"
* Optional store-wide countdown bar
* Live low-stock label with configurable threshold and `{n}` template
* Classic and block product templates
* No database table, no AJAX, no front-end input

## Installation

1. Upload to `/wp-content/plugins/hdwebmobile-sales-countdown`, or install through the WordPress plugins screen.
2. Activate. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Sales Countdown**.

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
