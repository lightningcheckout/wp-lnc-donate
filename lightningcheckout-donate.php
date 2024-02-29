<?php
/*
Plugin Name: Lightning Checkout - Bitcoin Donate
Description: Accept Bitcoin Donations on your Wordpress website.
Version: 0.1
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
    add_menu_page("LN Checkout", "LN Checkout", "manage_options", "lnc_btcdonate_main", "lnc_btcdonate_render_main_page", "dashicons-lightning", 30);
    add_submenu_page("lnc_btcdonate_main", "BTC Donate", "BTC Donate", "manage_options", "lnc_btcdonate_settings", "lnc_btcdonate_render_settings_page");
}

// Add action for menu page after including files
add_action("admin_menu", "lnc_btcdonate_menu_page");

// Hook into admin_init to register settings
add_action("admin_init", "lnc_btcdonate_register_settings");
