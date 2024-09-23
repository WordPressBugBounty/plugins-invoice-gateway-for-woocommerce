=== Invoice Gateway for WooCommerce - Invoice Payment Gateway ===
Contributors: jkohlbach, RymeraWebCo, smub
Tags: woocommerce invoice gateway, woocommerce payment gateway, woocommerce invoices, invoice gateway, woocommerce quotes
Requires at least: 5.2
Tested up to: 6.3.1
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a WooCommerce invoice gateway to your store. An easy invoicing payment gateway solution for WooCommerce.

== Description ==

**INVOICE GATEWAY FOR WOOCOMMERCE - AN EASY INVOICE PAYMENT GATEWAY SOLUTION**

Add a WooCommerce invoice gateway option for your customers on the checkout. The invoice payment gateway lets your customers checkout without paying and you can invoice separately via your Xero, Quickbooks, MYOB, Reckon Online, or other accounting package.

There's no integration required, you just invoice as you normally would, the plugin lets you record the invoice number which gets added to the customer's WooCommerce invoice email.

Orders get created in Processing status and from there you can generate a separate invoice from your accounting system to have your customer pay outside of WooCommerce.

Once you have been paid just add the invoice number to the order then change the Order status to Completed.

It's perfect for situations like wholesale selling where customers are often reluctant to pay large orders online. It's very similar to a WooCommerce quote at this stage and many people do use it as a quotes gateway.

This plugin is also fully compatible with Wholesale Suite's [WooCommerce Wholesale Prices](https://wordpress.org/plugins/woocommerce-wholesale-prices/) plugin which is the #1 solution for wholesale sales in WooCommerce.

*The WooCommerce invoicing process with Invoice Gateway For WooCommerce:*

1. Customer prepares their order as normal and heads to the checkout.
1. Customer selects the WooCommerce Invoice gateway as their preferred payment option.
1. The order goes into the system as "Processing" status - the customer doesn't pay anything yet.
1. You, the WooCommerce store owner, send the customer an invoice outside of WooCommerce from your accounting software (such as Xero, Quickbooks, etc).
1. You go back to the order and insert the "Invoice Number" into the field provided and update the order. The customer will get an email.
1. The customer pays the invoice directly to you.
1. When the order is fulfilled, you mark the WooCommerce Order complete (as normal), the customer will see their WooCommerce invoice number on the Completed order email.

**A WOOCOMMERCE INVOICE GATEWAY COMPATIBLE WITH WHOLESALE**

This WooCommerce invoice gateway plugin was brought to you by the folks at [Wholesale Suite](https://wholesalesuiteplugin.com).

Users of our popular free [Wholesale Prices extension for WooCommerce](https://wordpress.org/plugins/woocommerce-wholesale-prices/) were asking for the option to provide an invoice payment option for their wholesale customers.

If you use [Wholesale Suite's Prices Premium plugin](https://wholesalesuiteplugin.com/woocommerce-wholesale-prices-premium/), you'll also be able to use Payment Gateway mapping to map this Invoice Payment Gateway to the specific wholesale user roles that need it. [Click here for more information about how that works](https://wholesalesuiteplugin.com/kb/how-to-restrict-wholesale-customers-to-use-particular-payment-gateways/).

We decided to give this invoice gateway WooCommerce feature away for free, not just to our customers, but to everyone using WooCommerce. If you want to say thanks, please [leave us a rating](https://wordpress.org/support/plugin/invoice-gateway-for-woocommerce/reviews/#new-post) :)

== Installation ==

1. Upload the `invoice-gateway-for-woocommerce/` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Setup your new invoice gateway under WooCommerce->Settings, Checkout tab, then click on Invoice Gateway in the submenu.
1. ... Profit!

== Frequently asked questions ==

We'll be publishing a list of frequently asked questions soon.

== Screenshots ==

Coming Soon!

== Changelog ==

= 1.1.3 =
* Improvement: Add filters to Purchase Order text and descriptions
* Improvement: Add new setting to set default invoice gateway order status
* Improvement: Allow PO number field on checkout page if the cart total is `0`
* Bug Fix: Too few arguments fatal error when saving or updating orders from legacy api

= 1.1.2 =
* Improvement: HPOS compatibility

= 1.1.1 =
* Bug Fix: Various security and sanitization fixes

= 1.1 =
* Feature: Add option to enter purchase order number

= 1.0.1 =
* Improvement: WooCommerce 3.0 compatibility
* Bug Fix: Warnings shown during adding of variations
* Bug Fix: Issue with provided invoice number reference not being shown on order emails

= 1.0.0 =
* Initial version

== Upgrade notice ==

There is a new version of Invoice Gateway For WooCommerce available.
