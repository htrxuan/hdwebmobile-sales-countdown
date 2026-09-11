=== HDWebmobile Sales Countdown ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, countdown, sale timer, scarcity, stock countdown
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sale countdown timers and honest low-stock labels. The "only N left" figure is read live from real stock.

== Description ==

HDWebmobile Sales Countdown adds three small pieces of urgency to your store:

* **A per-product sale countdown** on products whose sale has a scheduled end date -- "Sale ends in 02:14:09", ticking down.
* **A store-wide countdown bar** -- set an end time and a line of text; the bar shows the countdown and hides itself when the time passes.
* **A low-stock label** -- "Only 3 left in stock" on products with managed stock at or below a threshold you choose.

= Why this plugin exists =
Countdown and scarcity plugins are a recurring home for two problems, and this plugin is built so neither can happen:

* **No stored XSS from a timer label.** Every label and every piece of text is set only on the plugin's settings page, saved through WordPress's own Settings API (which enforces the `manage_options` capability and a nonce), sanitised with `sanitize_text_field()`, and printed with `esc_html()`/`esc_attr()`. There is no rich-text field, and nothing a shopper submits is ever displayed. The countdown itself ticks in a small script that only reads a numeric timestamp from a `data-` attribute and only writes `element.textContent` -- it never assigns `innerHTML` and never inserts markup.
* **No fake scarcity.** The "only N left" number is always `WC_Product::get_stock_quantity()` -- WooCommerce's real, current stock. It is never a number an admin types in or a value carried in a request. If a product isn't managing stock, allows backorders, or is a variable parent, no count is shown at all.

= Key Features =
* Per-product sale countdown, driven by WooCommerce's own "Sale price dates"
* Optional store-wide countdown bar with your own end time and text
* Live low-stock label with a configurable threshold and label template ({n} = real stock)
* Works on the classic product template and block-based product templates
* No custom database table, no AJAX, no front-end input

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-sales-countdown` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Sales Countdown** to set your labels, threshold, and (optionally) a store-wide countdown.

== How to Use ==

= 1. Per-product sale countdown =
Set a "Sale price" and a "Sale price dates" end date on a product (Product data > General). While the sale is active, the countdown shows automatically.

= 2. Store-wide bar =
On the Sales Countdown tab, set "Ends at" to a date and time and enter the bar text. Clear "Ends at" to remove the bar.

= 3. Low-stock label =
Enable "Low-stock label", set a threshold (e.g. 8), and a label using `{n}` where the live stock count should appear.

== Screenshots ==

1. A sale countdown and low-stock label on a product page.
2. The store-wide countdown bar.
3. The Sales Countdown settings tab under WooCommerce > HDWebmobile.

== Changelog ==

= 1.0.0 =
* Initial release: per-product sale countdown, store-wide countdown bar, and a live-stock low-stock label -- all labels admin-only and escaped, the stock number always read from real stock.
