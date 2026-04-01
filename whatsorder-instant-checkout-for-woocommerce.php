<?php

/**
 * Plugin Name:  WhatsOrder – Instant Checkout for WooCommerce
 * Description: Allows customers to complete their checkout via WhatsApp, generating a downloadable invoice link.
 * Version: 1.0.1
 * Author: YapacDev
 * Author URI: https://yapacdev.com/
 * License: GPL2
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) {
    exit;
}
// Declare HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});


// Check if WooCommerce is installed and active
function yapacdev_check_woocommerce()
{
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', 'yapacdev_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
add_action('admin_init', 'yapacdev_check_woocommerce');

// Conditionally enqueue invoice stylesheet for invoice pages
function yapacdev_enqueue_invoice_styles()
{
    wp_enqueue_style('yapacdev-invoice-style', plugin_dir_url(__FILE__) . 'assets/css/invoice-style.css', array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'yapacdev_enqueue_invoice_styles');

// Display admin notice if WooCommerce is missing
function yapacdev_woocommerce_missing_notice()
{
    echo '<div class="notice notice-error"><p>';
    echo esc_html__('Additional plugins are required.', 'whatsorder-instant-checkout-for-woocommerce');
    echo '<br><strong>WooCommerce</strong> - <a href="' . esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')) . '" target="_blank">';
    echo esc_html__('More details', 'whatsorder-instant-checkout-for-woocommerce') . '</a>';
    echo '</p></div>';
}


// Register WhatsOrder as a payment gateway
add_filter('woocommerce_payment_gateways', 'yapacdev_add_whatsorder_gateway');
function yapacdev_add_whatsorder_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_YapacDev_WhatsOrder';
    return $gateways;
}

// Define the WhatsOrder payment gateway class
add_action('plugins_loaded', 'yapacdev_init_whatsorder_gateway', 11);
function yapacdev_init_whatsorder_gateway()
{
    class WC_Gateway_YapacDev_WhatsOrder extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id                 = 'yapacdev_whatsorder';
            $this->method_title       = __('WhatsOrder Checkout', 'whatsorder-instant-checkout-for-woocommerce');
            $this->method_description = __('Allows customers to complete their orders via WhatsApp.', 'whatsorder-instant-checkout-for-woocommerce');
            $this->supports           = array('products');
            $this->has_fields         = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled         = $this->get_option('enabled');
            $this->title           = $this->get_option('title');
            $this->description     = $this->get_option('description');
            $this->phone_number    = $this->get_option('phone_number');
            $this->custom_message  = $this->get_option('custom_message');
            $this->invoice_color   = $this->get_option('invoice_color', '#2469a0');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __('Enable/Disable', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable WhatsApp Payment', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => __('Payment Method Title', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Title displayed during checkout.', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default'     => __('Pay via WhatsApp', 'whatsorder-instant-checkout-for-woocommerce'),
                ),
                'description' => array(
                    'title'       => __('Description', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Short description shown to the customer.', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default'     => __('Complete your order via WhatsApp.', 'whatsorder-instant-checkout-for-woocommerce'),
                ),
                'phone_number' => array(
                    'title'       => __('WhatsApp Number', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'        => 'text',
                    'description' => __('Your WhatsApp phone number for receiving orders.', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default'     => '1234567890',
                ),
                'custom_message' => array(
                    'title'       => __('Custom WhatsApp Message', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Message sent to the customer including the invoice link.', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default'     => 'Hello, I want to place an order. Here is my invoice:',
                ),
                'invoice_color' => array(
                    'title'       => __('Invoice Theme Color', 'whatsorder-instant-checkout-for-woocommerce'),
                    'type'        => 'color',
                    'description' => __('Select the main color for the invoice.', 'whatsorder-instant-checkout-for-woocommerce'),
                    'default'     => '#2469a0',
                ),
            );
        }

        // Add nonce field
        public function admin_options()
        {
            $nonce = wp_create_nonce('yapacdev_whatsorder_nonce_action');
            echo '<input type="hidden" name="yapacdev_whatsorder_nonce" value="' . esc_attr($nonce) . '" />';
            parent::admin_options();
        }

        public function process_admin_options()
        {
            if (isset($_POST['yapacdev_whatsorder_nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['yapacdev_whatsorder_nonce']));

                if (wp_verify_nonce($nonce, 'yapacdev_whatsorder_nonce_action')) {
                    parent::process_admin_options();
                } else {
                    wc_add_notice(__('Security check failed. Settings not saved.', 'whatsorder-instant-checkout-for-woocommerce'), 'error');
                }
            }
        }

        public function process_payment($order_id)
        {

            $order    = wc_get_order($order_id);
            $pdf_url  = yapacdev_generate_order_pdf($order_id);
            $message  = urlencode($this->custom_message . ' ' . $pdf_url);
            $whatsapp = "https://wa.me/" . $this->phone_number . "?text=" . $message;

            // Reduce stock and mark order as on-hold
            $order->update_status('on-hold', __('Awaiting WhatsApp payment confirmation.', 'whatsorder-instant-checkout-for-woocommerce'));
            wc_reduce_stock_levels($order_id);

            return array(
                'result'   => 'success',
                'redirect' => $whatsapp
            );
        }
    }
}

// Generate invoice
function yapacdev_generate_order_pdf($order_id)
{
    $upload_dir   = wp_upload_dir();
    $invoice_dir  = $upload_dir['basedir'] . '/whatsorder_invoices/';
    $invoice_base = $upload_dir['baseurl'] . '/whatsorder_invoices/';

    if (! file_exists($invoice_dir)) {
        wp_mkdir_p($invoice_dir);
    }

    $order = wc_get_order($order_id);
    if (! $order) {
        return '';
    }

    // Get selected color or default
    $payment_gateway = new WC_Gateway_YapacDev_WhatsOrder();
    $theme_color     = $payment_gateway->get_option('invoice_color', '#2469a0');

    // Ensure proper UTF-8 encoding for special characters
    mb_internal_encoding("UTF-8");

    // Get localized checkout labels dynamically
    $checkout_labels = WC()->countries->get_address_fields($order->get_billing_country(), 'billing');
    $customer_label  = $checkout_labels['billing_first_name']['label'] ?? __('Customer', 'whatsorder-instant-checkout-for-woocommerce');
    $email_label     = $checkout_labels['billing_email']['label'] ?? __('Email', 'whatsorder-instant-checkout-for-woocommerce');
    $phone_label     = $checkout_labels['billing_phone']['label'] ?? __('Phone', 'whatsorder-instant-checkout-for-woocommerce');
    $address_label   = $checkout_labels['billing_address_1']['label'] ?? __('Billing Address', 'whatsorder-instant-checkout-for-woocommerce');

    // Set variables for the invoice template
    $invoice_vars = array(
        'order'          => $order,
        'theme_color'    => $theme_color,
        'customer_label' => $customer_label,
        'email_label'    => $email_label,
        'phone_label'    => $phone_label,
        'address_label'  => $address_label,
    );

    // Start output buffering and include the invoice template
    ob_start();
    // Make the variables available to the template file:
    extract($invoice_vars);
    include(plugin_dir_path(__FILE__) . 'templates/invoice-template.php');
    $html = ob_get_clean();

    $invoice_file = 'order-' . absint($order_id) . '.html';
    $pdf_path     = $invoice_dir . $invoice_file;

    file_put_contents($pdf_path, $html);

    return esc_url($invoice_base . $invoice_file);
}
