# OCTI for WooCommerce

* Contributors: 33themes, gabrielperezs
* Tags: octi.io, octi, one click to install
* Requires at least: 4.3
* Tested up to: 4.3
* Stable tag: 1.0.1
* License: GPLv2
* License URI: http://www.apache.org/licenses/LICENSE-2.0


WordPress plugin to connect your WooCommerce shop with OCTI.io

## Description

Offer a simple way to install your theme in a hosting ready to use. No setup, no
complex instalations, less hours on support.

For code contributions please go to https://github.com/33themes/octi-for-woocommerce

## How to use it

To follow all this examples the name of your theme is: *myfirsttheme*

Exists to ways to do this integration.

### 1) To generate your internal links.

Are links to your domain, with nonce protection. The links has more live time.

#### Print the standard button

`
<?php
  echo OCTI_FOR_SELLER::button('myfirsttheme');
?>
`

The result:

#### Crate your own button

`
<?php
  echo sprintf('<a href="%s">Install My First Theme</a>', OCTI_FOR_SELLER::link('myfirsttheme'));
?>
`

### 2) Generate directly link to octi.io

This links has a live time of 30 seconds. Is not recomened to use in my-account
screen because could get invalid answer if the user don't use it in this 30
seconds time frame.

`
<?php
echo sprintf(
        '<a href="%s">Install My First Theme</a>',
        OCTI_OTP::generate($octi_key, 'myfirsttheme')
    );
?>
`

## Changelog

### 1.0.1
Add button at woocommerce_order_item_meta_end hook. Shows in email, review, thankyou screens
### 1.0
First public version

## Installation

This section describes how to install the plugin and get it working.

1. Upload `ttt-loadmore` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
