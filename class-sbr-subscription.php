<?php
defined('ABSPATH') || exit;

class SBR_Subscription {

    public static function init() {
        add_action('init',                               array(__CLASS__, 'maybe_create_products'));
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'activate_from_order'));
    }

    public static function get_active($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sbr_subscriptions
             WHERE user_id=%d AND status='active' AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY id DESC LIMIT 1",
            $user_id
        ));
    }

    public static function activate($user_id, $plan, $order_id = null) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}sbr_subscriptions",
            array('status' => 'expired'),
            array('user_id' => $user_id, 'status' => 'active'),
            array('%s'), array('%d', '%s')
        );
        // 1 month duration
        $expires = date('Y-m-d H:i:s', strtotime('+1 month'));
        $wpdb->insert("{$wpdb->prefix}sbr_subscriptions", array(
            'user_id'    => absint($user_id),
            'plan'       => sanitize_text_field($plan),
            'status'     => 'active',
            'order_id'   => $order_id ? absint($order_id) : null,
            'starts_at'  => current_time('mysql'),
            'expires_at' => $expires,
        ), array('%d', '%s', '%s', '%d', '%s', '%s'));
        do_action('sbr_subscription_activated', $user_id, $plan);
    }

    public static function activate_from_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        foreach ($order->get_items() as $item) {
            $plan = get_post_meta($item->get_product_id(), '_sbr_subscription_plan', true);
            if ($plan) { self::activate($user_id, $plan, $order_id); break; }
        }
    }

    public static function maybe_create_products() {
        if (get_option('sbr_products_created_v3')) return;
        $plans = array(
            'silver' => array('name' => 'সিলভার মেম্বারশিপ (১ মাস)', 'price' => 150),
            'gold'   => array('name' => 'গোল্ড মেম্বারশিপ (১ মাস)',   'price' => 250),
        );
        foreach ($plans as $plan => $data) {
            // Delete old product
            $old = get_option('sbr_product_' . $plan);
            if ($old) { wp_delete_post($old, true); }

            $product = new WC_Product_Simple();
            $product->set_name($data['name']);
            $product->set_regular_price($data['price']);
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden');
            $product->set_virtual(true);
            $id = $product->save();
            update_post_meta($id, '_sbr_subscription_plan', $plan);
            update_option('sbr_product_' . $plan, $id);
        }
        update_option('sbr_products_created_v3', 1);
    }

    public static function get_checkout_url($plan) {
        $pid = get_option('sbr_product_' . $plan);
        if (!$pid) return wc_get_page_permalink('myaccount') . '#sbr-membership';
        return add_query_arg(array('add-to-cart' => $pid), wc_get_checkout_url());
    }

    public static function get_dashboard_url() {
        return wc_get_page_permalink('myaccount') . '#sbr-membership';
    }
}
