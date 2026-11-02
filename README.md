=== Kazeem Payment & Order Controls for WooCommerce ===
Contributors: kazeemquadri01
Tags: woocommerce, order control, payment gateway, shipping, webhooks
Plugin URI: https://github.com/Darkace01/kazeem-payment-order-controls-for-woocommerce
Requires at least: 6.2
Tested up to: 7.1
Stable tag: 1.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage WooCommerce order restrictions, payment gateway rules, shipping event webhooks, and advanced currency switching from one dashboard.

== Description ==

Kazeem Payment & Order Controls for WooCommerce is a comprehensive WordPress plugin that gives you complete control over your WooCommerce store operations. Manage order restrictions, payment gateway rules, shipping event webhooks, and advanced currency switching all from one powerful dashboard.

== Features ==

### 📦 Order Control

- **Enable/Disable Orders** - Turn order placement on or off globally
- **Time Restrictions** - Allow orders only during specific times of day
- **Date/Time Range** - Set specific date and time ranges for order availability
- **Conditional Restrictions:**
  - All Products
  - Specific Categories
  - Individual Products
- **Auto-hide Add to Cart Buttons** - Automatically hides purchase options when orders are disabled
- **Custom Messages** - Display custom messages to customers when orders are disabled
- **Redirect URLs** - Redirect customers attempting checkout when orders are disabled

### 💳 Payment Gateway Control

- **Currency-Based Rules** - Control which payment gateways appear for specific currencies
- **Table View Management** - Easy-to-use table interface for managing rules
- **Add/Edit/Delete Rules** - Full CRUD operations for payment rules
- **Enable/Disable Toggle** - Quickly enable or disable rules without deleting
- **Multi-Currency Support** - Select multiple currencies per rule
- **Multi-Gateway Support** - Select multiple payment gateways per rule

### 💱 Currency Control

- **Custom Exchange Rates** - Set manual exchange rates for different currencies
- **Currency Switcher** - Allow customers to switch currencies on the frontend
- **Automatic Price Conversion** - Prices automatically update based on defined rates
- **Symbol Customization** - Customize currency symbols
- **Shortcode** - `[kpoc_currency_switcher]` shows the switcher anywhere
- **Per-product Prices** - Optional fixed price per currency on each product
- **Geolocate (Coming Soon)** - Automatically detect customer currency

### 🔗 Shipping Event Webhooks

- **REST API Endpoint** - Receive event notifications from shipping platforms
- **Comprehensive Logging** - All requests logged to database
- **Detailed Log Viewer** - View request body, parameters, headers, and responses
- **Real-time Processing** - Automatically process and update orders
- **WooCommerce Integration** - Updates order meta and adds order notes

### 📊 Dashboard

- **Overview Statistics** - Quick view of all plugin features
- **Recent Activity** - View recent webhook events
- **Status Indicators** - Visual indicators for enabled/disabled features
- **Quick Links** - Easy navigation to all settings

== Installation ==

**Requirements:** WordPress 6.2+, PHP 7.2+, and the WooCommerce plugin installed and active. Without WooCommerce the plugin shows an admin notice and stays inactive.

1. Install and activate **WooCommerce** (Plugins > Add New).
2. Upload the plugin to `/wp-content/plugins/kazeem-payment-order-controls-for-woocommerce/`, or install it from Plugins > Add New.
3. Activate it through the **Plugins** menu. A database table for webhook logs is created automatically.
4. Open **Kazeem Payment & Order Controls** in the admin sidebar (or click **Settings** on the Plugins screen). The submenus are: Dashboard, Event Logs, Order Control, Payment Gateway, Currency Control.

Defaults on a fresh install: orders are allowed, no restrictions are active, no payment gateway rules exist, the currency switcher is off, and the webhook is locked until you set a secret.

== Configuration ==

All settings pages require the `manage_options` capability (Administrators).

### Order Control Settings

Go to **Kazeem Payment & Order Controls > Order Control**.

- **Enable Orders** - checked means customers may order. Uncheck it to stop all ordering immediately: Add to Cart is hidden, a notice appears on product pages and checkout redirects.
- **Restriction Type** - which products the time/date limits below apply to: All Products, Specific Categories or Specific Products. Products outside the selection are always orderable.
- **Time Restrictions** - orders only allowed between Start Time and End Time each day (store timezone from Settings > General). Overnight ranges such as 22:00-06:00 work.
- **Date/Time Range** - orders only allowed between two exact dates.
- **Redirect URL** - where checkout visitors are sent while ordering is closed (blank = homepage).
- **Disabled Message** - text shown to customers.

Click **Save Settings**. The blue "Current Status" box shows Active or Disabled right now.

### Payment Gateway Control

Go to **Kazeem Payment & Order Controls > Payment Gateway**.

1. Click **Add New Rule** and enter a name.
2. Tick the currencies the rule applies to.
3. Tick the payment gateways that should be offered for those currencies.
4. Leave **Enable this rule** ticked and click **Save Rule**.

At checkout, when the cart currency matches a rule, only the ticked gateways are offered. Use the table to edit, enable/disable or delete rules. Gateways must first be enabled in WooCommerce > Settings > Payments to appear in the list.

### Currency Control

Go to **Kazeem Payment & Order Controls > Currency Control**.

1. Tick **Enable Currency Switcher** and pick your store's Default Currency (normally your WooCommerce currency).
2. Click **Add Currency**, choose a currency (or Custom Currency), adjust the symbol and enter the exchange rate (1 unit of default currency = N units of this currency).
3. Click **Save Changes**.
4. Place the switcher where customers can see it: add the shortcode `[kpoc_currency_switcher]` to a page, widget or block (Shortcode block). It lists the currencies as links. You can also link directly with `?currency=EUR`.
5. Optional fixed prices: edit a product and fill the **Currency Pricing** box. A blank value uses the exchange rate.

Note: page-caching plugins should exclude pages that show prices from caching, or customers may see another visitor's currency.

### Shipping Webhooks

Go to **Kazeem Payment & Order Controls > Event Logs**.

1. Optionally change the **Endpoint Slug** (default `shipping-webhook`).
2. **Set a Webhook Secret** (any long random string). Until a secret is saved, every request is rejected with HTTP 403.
3. Click **Save Settings**, then give the webhook URL and secret to your shipping platform.
4. Received events appear under Recent Logs; click **View Details** for the body, headers and response.

Webhook URL format:

```
https://yoursite.com/wp-json/shipping/v1/shipping-webhook
```

Requests must be `POST` with the header `X-KPOC-Secret: <your secret>` and a JSON body. Supported fields: `order_id`, `tracking_number`, `status`, `event_type`. When `order_id` matches a WooCommerce order, the tracking number is saved to the order and the status is added as an order note. Developers can hook the `kazeem_payment_order_controls_shipping_event_received` action.

== Testing the Plugin ==

Quick checks on a fresh site (WooCommerce active, at least one product):

1. **Order Control:** uncheck Enable Orders and save. View a product page: Add to Cart is replaced by your message. Re-check it and the button returns.
2. **Payment Gateway:** enable two gateways in WooCommerce > Settings > Payments (e.g. Cash on delivery and Direct bank transfer). Add a rule for your store currency that allows only one of them. At checkout only that gateway appears.
3. **Currency Control:** enable the switcher, add EUR with rate 0.9, save, put `[kpoc_currency_switcher]` on a page, and click EUR. Product prices change.
4. **Webhook:** set a secret, then run:

```
curl -X POST "https://yoursite.com/wp-json/shipping/v1/shipping-webhook" -H "Content-Type: application/json" -H "X-KPOC-Secret: YOUR_SECRET" -d '{"order_id":123,"tracking_number":"TRK1","status":"shipped"}'
```

The response is `{"success":true,...}` and a log row appears under Event Logs. Without the header you get HTTP 401 (wrong secret) or 403 (no secret configured).

== Frequently Asked Questions ==

### Can I restrict orders for specific products only?

Yes! Use the "Restriction Type" setting in Order Control to select specific products or categories.

### Do payment gateway rules work on the cart page?

Yes, the rules apply to both cart and checkout pages.

### What happens when orders are disabled?

- Add to Cart buttons are hidden
- Custom message is displayed on product pages
- Checkout page redirects to your specified URL
- Error messages shown if customer attempts to place order

### Can I have multiple payment gateway rules?

Yes, you can create as many rules as needed. Rules can be enabled/disabled individually.

### Is the webhook endpoint secure?

Yes. Requests are rejected unless they send the `X-KPOC-Secret` header matching the Webhook Secret you set. Always use HTTPS.

### The webhook returns 403

No Webhook Secret has been saved yet. Set one under Event Logs and send it in the `X-KPOC-Secret` header.

### How do customers switch currency?

Add the `[kpoc_currency_switcher]` shortcode to a page or widget.

== Changelog ==

### 1.2.7

- Added `[kpoc_currency_switcher]` shortcode for the frontend currency switcher
- Added setup, usage and testing instructions to the readme
- Added a notice explaining that the webhook secret is required

- Renamed plugin to "Kazeem Payment & Order Controls for WooCommerce"
- Updated text domain to `kazeem-payment-order-controls-for-woocommerce`
- Added advanced currency switching features.
- Fixed "Trying to access array offset on null" warning on fresh installations.
- Added strict dependency check for WooCommerce to prevent fatal errors.
- Improved plugin initialization with admin notices for missing dependencies.
- Synchronized version constants across the plugin.

### 1.2.1

- Renamed plugin to "Control Suite For Woocommerce"
- Added dashicons to admin pages
- Improved UI/UX with visual indicators

### 1.2.0

- Added advanced order control features
- Implemented hide add to cart functionality
- Added date/time range restrictions
- Added conditional restrictions by product/category
- Added redirect URL feature

### 1.1.2

- Fixed syntax error in payment gateway control

### 1.1.1

- Implemented table view for payment gateway rules
- Added edit/delete/enable/disable functionality
- Added rule names and status indicators

### 1.1.0

- Added dashboard with statistics
- Restructured menu navigation

### 1.0.x

- Initial releases with basic features

== Support ==

For support, feature requests, or bug reports, please contact the plugin author.

== Credits ==

Developed by Kazeem Quadri

== License ==

This plugin is licensed under the GPL v2 or later.