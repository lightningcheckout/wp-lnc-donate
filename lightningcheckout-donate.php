<?php
/*
Plugin Name: Lightning Checkout - Bitcoin Donate
Description: Accept Bitcoin Donations on your Wordpress website.
Version: 0.3
Author: Lightning Checkout
*/

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/lightningcheckout/wp-lnc-donate/',
	__FILE__,
	'wp-lnc-donate'
);


//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
//$myUpdateChecker->setAuthentication('your-token-here');

//If you want to use release assets, call the enableReleaseAssets() method after creating the update checker instance:
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

include_once(plugin_dir_path(__FILE__) . 'includes/settings.php');
include_once(plugin_dir_path(__FILE__) . 'includes/custom-post-type.php');
include_once(plugin_dir_path(__FILE__) . 'includes/webhook.php');
include_once(plugin_dir_path(__FILE__) . 'includes/shortcode.php');
include_once(plugin_dir_path(__FILE__) . 'includes/styles.php');

// Function for menu page
function lnc_btcdonate_menu_page()
{
    add_menu_page("LN Checkout", "LN Checkout", "manage_options", "lnc_btcdonate_settings", "lnc_btcdonate_render_main_page", "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IS0tIFVwbG9hZGVkIHRvOiBTVkcgUmVwbywgd3d3LnN2Z3JlcG8uY29tLCBHZW5lcmF0b3I6IFNWRyBSZXBvIE1peGVyIFRvb2xzIC0tPgo8c3ZnIGZpbGw9IiMwMDAwMDAiIGhlaWdodD0iODAwcHgiIHdpZHRoPSI4MDBweCIgaWQ9IkxheWVyXzEiIGRhdGEtbmFtZT0iTGF5ZXIgMSINCiAgICB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+DQogICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNNC43MzIsNy45NTMzNSw2LjkwOTA4LDJoMy42MzYzOUw4LjM2MzY0LDcuMDEzMTZoMi45MDkxMUw0LjcyNzI1LDE0LDYuOTM2NTYsNy45NTEzNVoiLz4NCjwvc3ZnPg==", 30);
    add_submenu_page("lnc_btcdonate_main", "BTC Donate", "BTC Donate", "manage_options", "lnc_btcdonate_settings", "lnc_btcdonate_render_settings_page");
}

// Add action for menu page after including files
add_action("admin_menu", "lnc_btcdonate_menu_page");

// Hook into admin_init to register settings
add_action("admin_init", "lnc_btcdonate_register_settings");
