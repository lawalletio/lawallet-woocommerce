# WooCommerce Plugin for Bitcoin Lightning

Plugin to accept Bitcoin Lightning payments at [WooCommerce](https://woocommerce.com) stores,
using [LND](https://github.com/lightningnetwork/lnd).

## Installation

Requires PHP >= 5.6, Woocommerce and the `php-gmp` extension.

1. Install Woocommerce in your Wordpresss [Woocommerce Plugin](https://wordpress.org/plugins/woocommerce).

2. Install this and enable the plugin on your WordPress installation.

3. Under the WordPress administration panel, go to `WooCommerce -> Settings -> Checkout -> Lightning` and set your LUD16 handle.

The payment option should now be available in your checkout page.

## License

GPLv2
