<?php
/*
Plugin Name: Lightning Checkout - Bitcoin Donate
Description: Accept Bitcoin Donations on your Wordpress instance.
Version: 0.1
Author: Lightning Checkout
*/


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