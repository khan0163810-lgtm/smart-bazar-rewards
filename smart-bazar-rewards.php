<?php
/**
 * Plugin Name: Smart Bazar Rewards & Membership
 * Description: Complete Rewards, Points & Membership system for Smart Bazar
 * Version:     2.0.0
 * Author:      Smart Bazar
 * Text Domain: sb-rewards
 */
defined('ABSPATH') || exit;

define('SBR_VERSION',     '2.0.0');
define('SBR_FILE',        __FILE__);
define('SBR_PATH',        plugin_dir_path(__FILE__));
define('SBR_URL',         plugin_dir_url(__FILE__));
define('SBR_FREE_LIMIT',  15); // Free order limit

require_once SBR_PATH . 'includes/class-sbr-database.php';
require_once SBR_PATH . 'includes/class-sbr-points.php';
require_once SBR_PATH . 'includes/class-sbr-membership.php';
require_once SBR_PATH . 'includes/class-sbr-subscription.php';
require_once SBR_PATH . 'includes/class-sbr-checkout.php';
require_once SBR_PATH . 'includes/class-sbr-dashboard.php';
require_once SBR_PATH . 'admin/class-sbr-admin.php';

register_activation_hook(__FILE__, array('SBR_Database', 'install'));

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>Smart Bazar Rewards:</strong> WooCommerce প্রয়োজন।</p></div>';
        });
        return;
    }
    SBR_Points::init();
    SBR_Membership::init();
    SBR_Subscription::init();
    SBR_Checkout::init();
    SBR_Dashboard::init();
    SBR_Admin::init();
});
