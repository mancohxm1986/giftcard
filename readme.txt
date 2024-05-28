=== Gift Cards On Demand For WooCommerce ===
Contributors: paythem
Version: 3.0
Author: PayThem.net
Author URI: https://paythem.net/
Tags: gift cards, vouchers, top-ups, paythem
Requires at least: 6.0
Tested up to: 6.5.3
Stable tag: 3.0
Requires PHP: 8.0
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Sell Gift Cards from around the world without carrying inventory. 

== Description ==

**This powerful plugin provides instant access to hundreds of popular gift cards on demand. Create WooCommerce products for selected gift cards and set customer pricing. Customers can purchase gift card products as usual and after paying, the plugin immediately acquires the gift cards. When the order is released, the gift cards are delivered via your store's email to the customer's inbox.**

### Features

- No need to carry stock
- Access hundreds of gift cards at discounted prices
- Select which gift cards to sell and set customer prices
- Purchase gift cards on demand after the customer has paid
- Control when a customer receives the gift cards via email
- Release email orders at Processing or Completed status
- View detailed Sales/Financial/Stock Reports and export reports
- Sandbox mode available for testing the plugin
- Switch to Production mode to start selling gift cards

== Demo Video ==

https://youtu.be/GgNCjMK_iEI

== Demo Credentials ==

[Get test/sandbox credentials here](https://paythem.net/gift-card-plugin-woocommerce/).

== Screenshots ==

1. Settings - API credentials and wallet balance
2. Settings - Exchange rate to see cost prices in store's currency
3. Settings - Release gift cards at desired order status
4. Products - View list of products available on demand 
5. Woo - Easily link a gift card product to a Woo product
6. Reserved Stock Report - Showing items awaiting release
7. Example of customer order email including a gift card PIN
8. Stock Report - Filter, view and export available/sold stock
9. Transactions Report - View and export financial transactions

== Compatibility ==

100% Compatible with PayThem's Verify 2FA Plugin, which enables WooCommerce Customer email verification, phone verification and Two Factor login. [Read more here.](https://paythem.net/verify-2fa-email-mobile-number-verification-plugin-for-woocommerce/)
Being a backend plugin, the chances of any conflicts are minimal. This plugin has been tested with various themes and many other plugins without a single serious conflict being reported to date.

== Support ==

View the plugin's user guide documentation [here](https://paythem.net/gift-card-plugin-woocommerce/pro-docs/).
Email support is available via our website PayThem.net
Priority support is provided to PRO version customers. Read more [here](https://paythem.net/gift-card-plugin-woocommerce/pro/).

== Requirements ==

A PayThem Reseller Account is needed to obtain API Credentials. 
Step 1 - Register as a PayThem Customer [here](https://paythem.net/my-account/)
Step 2 - Submit a Reseller Application [here](https://paythem.net/reseller-application-form/)
Step 3 - Request Sandbox/Production Credentials

== PRO Features == 

- Global Default Markup
This global setting stores a default markup percentage. When new products are created, this default % is used to calculate and set the regular price that customers pay. Default markup prices will also automatically adjust whenever cost prices increase or decrease. Default markup prices may be replaced with custom fixed or percentage-based prices at product level.

- Automatic Exchange Rates
If supplier cost prices are USD based, but the default currency of the WooCommerce Store is EUR, all pricing will be converted to EUR using a free API key from Open Exchange Rates.

- Limit Stock Availability
This optional global setting hides actual stock levels from customers and only shows stock up to the maximum limit set by the Store Owner.

- Low Wallet Balance Notifications
Store Owners have the option to set a minimum wallet level and receive a notification email when their supplier wallet balance drops below the set amount.

- Set Price Mismatch Notifications
This option allows Store Owners to set a minimum profit percentage. If supplier price changes result in a sale making less profit than the value set, a warning email is sent to the Store Owner.

- Bulk Import of Products
Select up to 100 supplier products at a time to create WooCommerce products. Category selection, custom product names and pricing can all be adjusted on one page before importing. Newly created WooCommerce products are saved to draft status, so that final adjustments can be made before publishing.

- Set Percentage Selling Price
This option sets a custom percentage markup to calculate and set customer prices for a product. The customer pricing will be automatically adjusted whenever supplier prices change so that the Store Owner does not need to adjust prices when supplier prices change.

- Set Fixed Foreign Currency Price
This option automatically adjusts store prices according to a set price in a foreign currency. For example, a store with a EUR default currency might want to sell a USD 100 product for exactly USD 100 irrespective of the exchange rate. This option allows the EUR price to be adjusted so that customers always pay the EUR equivalent of USD 100 for this product. Store Owners can select any currency and set a fixed price for any product. This option relies on the Automatic Exchange Rates feature being enabled.

- Hide Or Customize Redemption Instructions
Standard redemption instructions are provided by many suppliers. These default instructions are included in delivery emails to customers. This option allows Store Owners to remove redemption instructions or replace the default instructions with their own.  

- Purchase At Completed Status
By default, the plugin will reserve a gift card as soon as the customer has paid, and the order status changes from “Pending payment” to “Processing”. This option allows the purchase of gift cards to be delayed until the order status is changed to “Completed”.

- Send Bulk Order CSV Files
This option allows bulk orders to be delivered in CSV files. If a Store Owner sets a value of 10 items for example, orders for 10 or more gift cards will be provided in CSV files for the customers convenience.

- Restrict Email Delivery
Email delivery is not always reliable or secure. This option removes gift cards from delivery emails if the customer has an account. Instead, the customer receives a notification email when the gift cards can be downloaded. The customer then needs to login to their account to access/download their gift cards.

- [Upgrade to PRO here](https://paythem.net/gift-card-plugin-woocommerce/pro/)

== Privacy ==
The plugin does not share any user information with any external party. The Plugin requires PayThem API Credentials to function, and the Store Owner requires a PayThem Reseller account to obtain API Credentials. The Reseller Application and Account will be subject to PayThem’s Privacy policy, which may be viewed [here](https://paythem.net/legal-terms/privacy-policy/)

== About Us ==
PayThem is an electronic voucher generation and gift card distribution software specialist, with offices in Australia, Canada, Qatar and UAE. Processing more than three million transactions per annum, PayThem’s electronic voucher distribution software provides a customizable and reliable electronic voucher distribution and bill payment solution for suppliers, distributors, retailers, resellers and consumers. This Plugin has been tried and tested over many years and is one of our favorite components in PayThem’s software solutions universe.

== Frequently Asked Questions ==

= Why is a Reseller Account required? =
Users maintain a wallet balance on their secure Reseller Account. The Reseller Account can be used for various sales channels including API transactions. When an item is purchased, the discounted cost price of the item is deducted from the Reseller wallet.

= What about Multisite? =
The plugin cannot be used on a multisite and while it is possible to install the free version on multiple sites, using the same credentials will cause conflicts.

= What about page speed? =
The plugin has virtually no impact on front end load speeds. 

== Installation ==
Installation is quick and easy.

1. Download, install and activate this plugin.
2. Register as a PayThem Customer. [Register here](https://paythem.net/my-account/)
3. Submit a Reseller Application. [Reseller Application](https://paythem.net/reseller-application-form/)
4. Request Sandbox Credentials for testing.
5. Request Production Credentials to go LIVE.

== Changelog ==

### 3.0 - 2024-03-06
Enhancement - Adapted FREE Version for WordPress.org listing
Enhancement - Adapted for WooCommerce High-Performance Order Storage (HPOS)

### 2.10 - 2022-05-23

Fix - Non-PayThem products in the store were being subjected to the plugin order limits.
Fix - The quantity selector in the product order edit page was sticky and has been fixed.
Security - Removed PIN code off WooCommerce thank you page.
Enhancement - Variable API purchase limits introduced
Enhancement - Sold stock will automatically be deleted from the database when order is trashed/deleted by admin. 
Enhancement - Stock view now also includes the option to manually delete up to 250 sold items from the database.
Enhancement - Critical cron based tasks have been migrated from wp_cron to Scheduled Tasks.

### 2.9 - 2021-11-29
Fix - Bug fixes.
Enhancement - Improved front end price automation

### 2.8.1 - 2021-04-15
Enhancement - Improved usability by bolding PIN codes in outgoing order emails
Enhancement - Improved order deliverability by automatically scanning for failed orders
Enhancement - Improved Transaction Report exports

### 2.7 - 2020-07-03
Enhancement - Improved stock management
Enhancement - Added Reserved Stock Report

### 2.0 - 2019-12-18
Enhancement - Improved handling out of stock items.
Enhancement - Improved Order Notes.
Enhancement - Improved Transaction Reports.
Enhancement - Improved security Plugin settings visibility limited to Administrators.
Enhancement - Added ability to purchase stock on demand.
Enhancement - Added links to orders to display list of products attached to orders.

### 1.1 - 2019-09-17
Bug fixes.

### 1.0 - 2019-08-05
Initial Release.
