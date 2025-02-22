<?php
/**
 * Plugin Name: Currency Converter Invoice
 * Plugin URI: https://netsam.ir
 * Description: A plugin to create dollar-based invoices with live exchange rates and currency conversion tools.
 * Version: 1.0
 * Author: Shahab Masoumi
 * Author URI: https://netsam.ir
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the main plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-currency-converter-invoice.php';

// Initialize the plugin
function run_currency_converter_invoice() {
    $plugin = new Currency_Converter_Invoice();
    $plugin->run();
}
run_currency_converter_invoice();

// Activation hook
register_activation_hook(__FILE__, 'currency_converter_invoice_activate');
function currency_converter_invoice_activate() {
    // Create a page for displaying invoices
    $page_title = 'Invoice Display';
    $page_content = '[invoice_display]';
    $page_check = get_page_by_title($page_title);
    if(!$page_check) {
        $page = array(
            'post_type' => 'page',
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_author' => 1,
        );
        wp_insert_post($page);
    }
}

