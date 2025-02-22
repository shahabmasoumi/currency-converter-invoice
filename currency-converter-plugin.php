<?php
/**
 * Plugin Name: Currency Converter Invoice
 * Description: A plugin to create dollar-based invoices with live exchange rates.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CurrencyConverterInvoice {
    public function __construct() {
        // Add menu item
        add_action('admin_menu', array($this, 'add_plugin_page'));
        // Register settings
        add_action('admin_init', array($this, 'page_init'));
        // Add shortcode
        add_shortcode('currency_invoice', array($this, 'currency_invoice_shortcode'));
        // Add AJAX action for frontend
        add_action('wp_ajax_get_invoice_amount', array($this, 'get_invoice_amount'));
        add_action('wp_ajax_nopriv_get_invoice_amount', array($this, 'get_invoice_amount'));
    }

    public function add_plugin_page() {
        add_options_page(
            'Currency Converter Settings',
            'Currency Converter',
            'manage_options',
            'currency-converter',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Currency Converter Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('currency_converter_option_group');
                do_settings_sections('currency-converter-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'currency_converter_option_group',
            'dollar_exchange_rate',
            array($this, 'sanitize')
        );

        add_settings_section(
            'currency_converter_setting_section',
            'Exchange Rate Settings',
            array($this, 'print_section_info'),
            'currency-converter-admin'
        );

        add_settings_field(
            'dollar_exchange_rate',
            'Dollar Exchange Rate (in Rials)',
            array($this, 'dollar_exchange_rate_callback'),
            'currency-converter-admin',
            'currency_converter_setting_section'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        if(isset($input['dollar_exchange_rate']))
            $new_input['dollar_exchange_rate'] = absint($input['dollar_exchange_rate']);

        return $new_input;
    }

    public function print_section_info() {
        print 'Enter the current dollar exchange rate below:';
    }

    public function dollar_exchange_rate_callback() {
        printf(
            '<input type="text" id="dollar_exchange_rate" name="dollar_exchange_rate" value="%s" />',
            isset(get_option('dollar_exchange_rate')['dollar_exchange_rate']) ? esc_attr(get_option('dollar_exchange_rate')['dollar_exchange_rate']) : ''
        );
    }

    public function currency_invoice_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'amount' => 0,
            ),
            $atts,
            'currency_invoice'
        );

        $invoice_id = uniqid();
        $amount = intval($atts['amount']);
        $link = add_query_arg('invoice', $invoice_id, home_url('/invoice-display/'));

        update_option('invoice_' . $invoice_id, $amount);

        return '<a href="' . esc_url($link) . '">View Invoice</a>';
    }

    public function get_invoice_amount() {
        $invoice_id = isset($_POST['invoice_id']) ? sanitize_text_field($_POST['invoice_id']) : '';
        $amount = get_option('invoice_' . $invoice_id, 0);
        $exchange_rate = isset(get_option('dollar_exchange_rate')['dollar_exchange_rate']) ? get_option('dollar_exchange_rate')['dollar_exchange_rate'] : 1;

        $dollar_amount = $amount / $exchange_rate;

        wp_send_json(array(
            'rial_amount' => number_format($amount),
            'dollar_amount' => number_format($dollar_amount, 2)
        ));
    }
}

$currency_converter_invoice = new CurrencyConverterInvoice();

// Create a page for displaying invoices
function create_invoice_display_page() {
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
register_activation_hook(__FILE__, 'create_invoice_display_page');

// Shortcode for invoice display page
function invoice_display_shortcode() {
    $invoice_id = isset($_GET['invoice']) ? sanitize_text_field($_GET['invoice']) : '';
    if(!$invoice_id) {
        return 'Invalid invoice.';
    }

    ob_start();
    ?>
    <div id="invoice-display">
        <h2>Invoice Details</h2>
        <p>Amount in Rials: <span id="rial-amount"></span></p>
        <p>Amount in Dollars: <span id="dollar-amount"></span></p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        function updateInvoice() {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_invoice_amount',
                    invoice_id: '<?php echo $invoice_id; ?>'
                },
                success: function(response) {
                    $('#rial-amount').text(response.rial_amount);
                    $('#dollar-amount').text(response.dollar_amount);
                }
            });
        }

        updateInvoice();
        setInterval(updateInvoice, 60000); // Update every minute
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('invoice_display', 'invoice_display_shortcode');

