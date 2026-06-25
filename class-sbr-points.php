<?php
defined('ABSPATH') || exit;

class SBR_Points {

    public static function init() {
        add_action('woocommerce_order_status_completed',   array(__CLASS__, 'earn_on_complete'));
        add_action('woocommerce_order_status_refunded',    array(__CLASS__, 'deduct_on_refund'));
        add_action('woocommerce_order_status_cancelled',   array(__CLASS__, 'deduct_on_refund'));
        add_action('woocommerce_checkout_order_processed', array(__CLASS__, 'deduct_redeemed'), 10, 3);
    }

    public static function get_balance($user_id) {
        global $wpdb;
        $b = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(points),0) FROM {$wpdb->prefix}sbr_points WHERE user_id=%d", $user_id
        ));
        return max(0, (int)$b);
    }

    public static function add($user_id, $points, $source='manual', $order_id=null, $note='') {
        if ($points <= 0) return false;
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sbr_points", array(
            'user_id'  => absint($user_id),
            'points'   => absint($points),
            'type'     => 'earn',
            'source'   => sanitize_text_field($source),
            'order_id' => $order_id ? absint($order_id) : null,
            'note'     => sanitize_text_field($note),
        ), array('%d','%d','%s','%s','%d','%s'));
    }

    public static function deduct($user_id, $points, $source='redeem', $order_id=null, $note='') {
        if ($points <= 0) return false;
        // BUG FIX #3: Never allow negative balance — always check before deducting
        $current_balance = self::get_balance($user_id);
        if ($current_balance <= 0) return false; // nothing to deduct
        // Clamp: deduct only what they have
        $points = min($points, $current_balance);
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sbr_points", array(
            'user_id'  => absint($user_id),
            'points'   => -absint($points),
            'type'     => 'deduct',
            'source'   => sanitize_text_field($source),
            'order_id' => $order_id ? absint($order_id) : null,
            'note'     => sanitize_text_field($note),
        ), array('%d','%d','%s','%s','%d','%s'));
    }

    public static function calculate($order_total, $membership) {
        $total = (float)$order_total;
        if ($membership === 'gold') {
            $base  = floor($total / (float)get_option('sbr_gold_rate_spend', 100)) * (int)get_option('sbr_gold_rate_base', 3);
            $bonus_min = (float)get_option('sbr_gold_bonus_min', 500);
            $bonus_pts = (int)get_option('sbr_gold_bonus_pts', 8);
            $bonus = ($total >= $bonus_min) ? floor($total / $bonus_min) * $bonus_pts : 0;
            return (int)($base + $bonus);
        } elseif ($membership === 'silver') {
            $base  = floor($total / (float)get_option('sbr_silver_rate_spend', 100)) * (int)get_option('sbr_silver_rate_base', 1);
            $bonus_min = (float)get_option('sbr_silver_bonus_min', 500);
            $bonus_pts = (int)get_option('sbr_silver_bonus_pts', 5);
            $bonus = ($total >= $bonus_min) ? floor($total / $bonus_min) * $bonus_pts : 0;
            return (int)($base + $bonus);
        } else {
            $spend = (float)get_option('sbr_free_rate_spend', 150);
            $base  = (int)get_option('sbr_free_rate_base', 1);
            return (int)floor($total / $spend) * $base;
        }
    }

    public static function points_to_taka($points, $membership) {
        $points = (int)$points;
        if ($membership === 'gold') {
            $rate_pts = (int)get_option('sbr_gold_redeem_pts', 5);
            $rate_val = (float)get_option('sbr_gold_redeem_val', 1.5);
        } elseif ($membership === 'silver') {
            $rate_pts = (int)get_option('sbr_silver_redeem_pts', 5);
            $rate_val = (float)get_option('sbr_silver_redeem_val', 1);
        } else {
            $rate_pts = (int)get_option('sbr_free_redeem_pts', 10);
            $rate_val = (float)get_option('sbr_free_redeem_val', 0.50);
        }
        if ($rate_pts <= 0) return 0;
        return round(($points / $rate_pts) * $rate_val, 2);
    }

    public static function min_redeem_points($membership) {
        $min_tk = (float)get_option('sbr_min_redeem_tk', 5);
        if ($membership === 'gold') {
            $rate_pts = (int)get_option('sbr_gold_redeem_pts', 5);
            $rate_val = (float)get_option('sbr_gold_redeem_val', 1.5);
        } elseif ($membership === 'silver') {
            $rate_pts = (int)get_option('sbr_silver_redeem_pts', 5);
            $rate_val = (float)get_option('sbr_silver_redeem_val', 1);
        } else {
            $rate_pts = (int)get_option('sbr_free_redeem_pts', 10);
            $rate_val = (float)get_option('sbr_free_redeem_val', 0.50);
        }
        if ($rate_val <= 0) return 9999;
        return (int)ceil(($min_tk / $rate_val) * $rate_pts);
    }

    // Points earned in Bengali words (e.g. 255 => দুইশত পঞ্চান্ন)
    public static function to_bengali_words($number) {
        $number = (int)$number;
        if ($number === 0) return 'শূন্য';
        $ones = ['', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়',
                 'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোলো', 'সতেরো', 'আঠারো', 'উনিশ'];
        $tens = ['', '', 'বিশ', 'ত্রিশ', 'চল্লিশ', 'পঞ্চাশ', 'ষাট', 'সত্তর', 'আশি', 'নব্বই'];
        $result = '';
        if ($number >= 1000) {
            $result .= self::to_bengali_words((int)($number / 1000)) . ' হাজার ';
            $number %= 1000;
        }
        if ($number >= 100) {
            $hundreds = ['', 'একশত', 'দুইশত', 'তিনশত', 'চারশত', 'পাঁচশত', 'ছয়শত', 'সাতশত', 'আটশত', 'নয়শত'];
            $result .= $hundreds[(int)($number / 100)] . ' ';
            $number %= 100;
        }
        if ($number >= 20) {
            $result .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }
        if ($number > 0) {
            $result .= $ones[$number];
        }
        return trim($result);
    }

    public static function earn_on_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        if ($order->get_meta('_sbr_points_earned')) return;
        $membership = SBR_Membership::get_level($user_id);
        if ($membership === 'none') return;
        $points = self::calculate($order->get_total(), $membership);
        if ($points <= 0) return;
        self::add($user_id, $points, 'order', $order_id, 'Order #'.$order_id.' সম্পন্ন');
        $order->update_meta_data('_sbr_points_earned', $points);
        $order->save();
    }

    public static function deduct_on_refund($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        $earned = (int)$order->get_meta('_sbr_points_earned');
        if ($earned > 0) {
            // BUG FIX #3: deduct() now internally clamps to available balance — no negative possible
            self::deduct($user_id, $earned, 'refund', $order_id, 'Refund/Cancel Order #'.$order_id);
            $order->update_meta_data('_sbr_points_earned', 0);
            $order->save();
        }
    }

    public static function deduct_redeemed($order_id, $posted, $order) {
        $user_id  = $order->get_customer_id();
        $redeemed = (int)WC()->session->get('sbr_redeem_points');
        if ($user_id && $redeemed > 0) {
            self::deduct($user_id, $redeemed, 'redeem', $order_id, 'Checkout Redeem Order #'.$order_id);
            $order->update_meta_data('_sbr_points_redeemed', $redeemed);
            $order->save();
            // BUG FIX #1: Always clear session after order is placed
            WC()->session->set('sbr_redeem_points', 0);
            WC()->session->__unset('sbr_redeem_points');
        }
    }

    public static function get_history($user_id, $limit=20, $offset=0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sbr_points WHERE user_id=%d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        ));
    }
}
