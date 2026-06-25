<?php
defined('ABSPATH') || exit;

class SBR_Membership {

    public static function tiers() {
        return array(
            'free' => array(
                'label' => '🆓 ফ্রি',
                'color' => '#888888',
                'bg'    => '#f5f5f5',
            ),
            'silver' => array(
                'label'           => '🥈 সিলভার',
                'color'           => '#2e8b2e',
                'bg'              => '#f0fff0',
                'price'           => 150,
                'duration_label'  => '/ মাস',
                'benefits_have'   => array(
                    'ডেলিভারি চার্জ ফ্রি (মাসে ৪ বার)',
                    'প্রতি ১০০ টাকা কেনাকাটায় ১ পয়েন্ট',
                    '৫ পয়েন্ট = ১ টাকা ডিসকাউন্ট',
                    'সফল রেফারে ২০ পয়েন্ট',
                    'নির্দিষ্ট কেনাকাটায় বিশেষ উপহার',
                    'নতুন পণ্যের আপডেট ও নোটিফিকেশন',
                    'সীমিত স্টকে অগ্রাধিকার',
                ),
                'benefits_locked' => array(
                    'দিনে একাধিক অর্ডারের সুবিধা',
                    'Member Only এক্সক্লুসিভ অফার',
                    'বাজার লিস্ট রিমাইন্ডার সুবিধা',
                    'এক্সট্রা পয়েন্ট বুস্টার সুবিধা',
                ),
            ),
            'gold' => array(
                'label'           => '🏅 গোল্ড',
                'color'           => '#c8080a',
                'bg'              => '#fff8e1',
                'price'           => 250,
                'duration_label'  => '/ মাস',
                'benefits_have'   => array(
                    'ডেলিভারি চার্জ ফ্রি (আনলিমিটেড)',
                    'দিনে একাধিক অর্ডারের সুবিধা',
                    'প্রতি ১০০ টাকা কেনাকাটায় ৩ পয়েন্ট',
                    '৫ পয়েন্ট = ১.৫ টাকা ডিসকাউন্ট',
                    'সফল রেফারে ৩০ পয়েন্ট',
                    'Member Only এক্সক্লুসিভ অফার',
                    'বাজার লিস্ট রিমাইন্ডার',
                    'নির্দিষ্ট কেনাকাটায় বিশেষ উপহার',
                    'নতুন পণ্যের আপডেট ও নোটিফিকেশন',
                    'সীমিত স্টকে অগ্রাধিকার',
                    'এক্সট্রা পয়েন্ট বুস্টার সুবিধা',
                ),
                'benefits_locked' => array(),
            ),
        );
    }

    public static function init() {}

    private static $order_count_cache = array();
    private static $level_cache       = array();

    public static function get_level($user_id) {
        if (isset(self::$level_cache[$user_id])) return self::$level_cache[$user_id];
        $sub = SBR_Subscription::get_active($user_id);
        if ($sub) { self::$level_cache[$user_id] = $sub->plan; return $sub->plan; }
        $count = self::get_completed_orders($user_id);
        $limit = (int)get_option('sbr_free_limit', SBR_FREE_LIMIT);
        $level = ($count < $limit) ? 'free' : 'needs_sub';
        self::$level_cache[$user_id] = $level;
        return $level;
    }

    public static function get_completed_orders($user_id) {
        if (isset(self::$order_count_cache[$user_id])) return self::$order_count_cache[$user_id];
        global $wpdb;
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
             WHERE p.post_type IN ('shop_order','wc_order')
               AND p.post_status = 'wc-completed'
               AND pm.meta_key = '_customer_user'
               AND pm.meta_value = %s",
            $user_id
        ));
        self::$order_count_cache[$user_id] = $count;
        return $count;
    }

    public static function get_tier_info($level) {
        $tiers = self::tiers();
        return $tiers[$level] ?? $tiers['free'];
    }
}
