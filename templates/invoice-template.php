<?php
if (! defined('ABSPATH')) {
    exit; // Prevent direct access
}
?>
<html>

<head>
    <meta charset="UTF-8">
    <!-- We are not directly outputting <link> tags; styles should be enqueued -->
    <?php wp_head(); ?>
</head>

<body class="yapacdev-invoice-page" style="--theme-color: <?php echo esc_attr($theme_color); ?>;">
    <div class="invoice-container">
        <h2>
            <?php
            // Translators: %d is the order ID.
            echo sprintf(esc_html__('Invoice for Order #%d', 'whatsorder-instant-checkout-for-woocommerce'), esc_html($order->get_id()));
            ?>
        </h2>
        <div class="customer-details">
            <p>
                <strong><?php echo esc_html($customer_label); ?>:</strong>
                <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
            </p>
            <p>
                <strong><?php echo esc_html($email_label); ?>:</strong>
                <?php echo esc_html($order->get_billing_email()); ?>
            </p>
            <p>
                <strong><?php echo esc_html($phone_label); ?>:</strong>
                <?php echo esc_html($order->get_billing_phone()); ?>
            </p>
            <p>
                <strong><?php echo esc_html($address_label); ?>:</strong>
                <?php echo esc_html($order->get_billing_address_1() . ', ' . $order->get_billing_city()); ?>
            </p>
        </div>

        <div class="order-items">
            <h3><?php esc_html_e('Order Items', 'whatsorder-instant-checkout-for-woocommerce'); ?></h3>
            <table>
                <tr>
                    <th><?php esc_html_e('Product', 'whatsorder-instant-checkout-for-woocommerce'); ?></th>
                    <th><?php esc_html_e('Qty', 'whatsorder-instant-checkout-for-woocommerce'); ?></th>
                    <th><?php esc_html_e('Price', 'whatsorder-instant-checkout-for-woocommerce'); ?></th>
                </tr>
                <?php foreach ($order->get_items() as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo esc_html($item->get_quantity()); ?></td>
                        <td><?php echo wp_kses_post(wc_price($item->get_total())); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if ($order->get_used_coupons()) : ?>
            <div class="order-summary">
                <h3><?php esc_html_e('Applied Coupons', 'whatsorder-instant-checkout-for-woocommerce'); ?></h3>
                <p>
                    <?php
                    $coupons = $order->get_used_coupons();
                    echo esc_html(implode(', ', $coupons));
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <p>
                <strong><?php esc_html_e('Shipping Method', 'whatsorder-instant-checkout-for-woocommerce'); ?>:</strong>
                <?php echo esc_html($order->get_shipping_method()); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Shipping Cost', 'whatsorder-instant-checkout-for-woocommerce'); ?>:</strong>
                <?php echo wp_kses_post(wc_price($order->get_shipping_total())); ?>
            </p>
            <p class="total">
                <strong><?php esc_html_e('Total', 'whatsorder-instant-checkout-for-woocommerce'); ?>:</strong>
                <?php echo wp_kses_post(wc_price($order->get_total())); ?>
            </p>
        </div>

        <div class="footer">
            <?php esc_html_e('Thank you for your order!', 'whatsorder-instant-checkout-for-woocommerce'); ?><br>
            <?php esc_html_e('Powered by', 'whatsorder-instant-checkout-for-woocommerce'); ?>
            <a href="<?php echo esc_url('https://wordpress.org/plugins/whatsorder-instant-checkout-for-woocommerce/'); ?>"><?php esc_html_e('WhatsOrder – Instant Checkout for WooCommerce', 'whatsorder-instant-checkout-for-woocommerce'); ?></a>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>

</html>