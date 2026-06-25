<?php
defined('ABSPATH') || exit;

class SBR_Database {
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sbr_points (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id    BIGINT UNSIGNED NOT NULL,
            points     INT NOT NULL DEFAULT 0,
            type       VARCHAR(30) NOT NULL DEFAULT 'earn',
            source     VARCHAR(50) NOT NULL DEFAULT 'order',
            order_id   BIGINT UNSIGNED DEFAULT NULL,
            note       VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset;");

        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sbr_subscriptions (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id    BIGINT UNSIGNED NOT NULL,
            plan       VARCHAR(20) NOT NULL DEFAULT 'silver',
            status     VARCHAR(20) NOT NULL DEFAULT 'active',
            order_id   BIGINT UNSIGNED DEFAULT NULL,
            starts_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset;");

        // Default options
        $defaults = array(
            'sbr_free_limit'          => 15,
            // Points earn rates
            'sbr_free_rate_base'      => 1,    // free: 1pt per 150tk
            'sbr_free_rate_spend'     => 150,
            'sbr_free_redeem_pts'     => 10,   // free: 10pt = 0.50tk
            'sbr_free_redeem_val'     => 0.50,
            // Silver
            'sbr_silver_price'        => 599,
            'sbr_silver_rate_base'    => 1,    // 1pt per 100tk
            'sbr_silver_rate_spend'   => 100,
            'sbr_silver_bonus_min'    => 500,  // 500-999tk => +5pt
            'sbr_silver_bonus_pts'    => 5,
            'sbr_silver_redeem_pts'   => 5,    // 5pt = 1tk
            'sbr_silver_redeem_val'   => 1,
            'sbr_silver_refer_giver'  => 20,   // referrer gets 20pt
            'sbr_silver_refer_taker'  => 10,   // referee gets 10pt
            // Gold
            'sbr_gold_price'          => 1199,
            'sbr_gold_rate_base'      => 3,    // 3pt per 100tk
            'sbr_gold_rate_spend'     => 100,
            'sbr_gold_bonus_min'      => 500,  // 500-999tk => +8pt
            'sbr_gold_bonus_pts'      => 8,
            'sbr_gold_redeem_pts'     => 5,    // 5pt = 1.5tk
            'sbr_gold_redeem_val'     => 1.5,
            'sbr_gold_refer_giver'    => 30,
            'sbr_gold_refer_taker'    => 10,
            // Redeem minimum
            'sbr_min_redeem_tk'       => 5,    // min 5tk worth
        );
        foreach ($defaults as $k => $v) {
            if (get_option($k) === false) add_option($k, $v);
        }
        update_option('sbr_db_version', SBR_VERSION);
    }
}
