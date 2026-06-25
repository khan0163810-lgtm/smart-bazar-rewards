<?php
defined('ABSPATH') || exit;

class SBR_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_sbr_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_sbr_manual_points',  array(__CLASS__, 'manual_points'));
    }

    public static function menu() {
        add_menu_page('SB Rewards','SB Rewards','manage_options','sbr-rewards',array(__CLASS__,'page_dashboard'),'dashicons-star-filled',56);
        add_submenu_page('sbr-rewards','Settings','⚙️ Settings','manage_options','sbr-settings',array(__CLASS__,'page_settings'));
        add_submenu_page('sbr-rewards','Users','👥 Users','manage_options','sbr-users',array(__CLASS__,'page_users'));
        add_submenu_page('sbr-rewards','History','📋 History','manage_options','sbr-history',array(__CLASS__,'page_history'));
    }

    public static function page_dashboard() {
        global $wpdb;
        $total_pts   = $wpdb->get_var("SELECT COALESCE(SUM(points),0) FROM {$wpdb->prefix}sbr_points WHERE points>0");
        $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}sbr_points");
        $active_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sbr_subscriptions WHERE status='active' AND (expires_at IS NULL OR expires_at > NOW())");
        echo '<div class="wrap"><h1>🏆 Smart Bazar Rewards Dashboard</h1>';
        echo '<div style="display:grid;grid-template-columns:repeat(3,220px);gap:16px;margin:20px 0">';
        foreach (array(
            array('মোট পয়েন্ট বিতরণ', number_format($total_pts), '#1a7a1a'),
            array('পয়েন্ট ব্যবহারকারী', number_format($total_users), '#d4a017'),
            array('সক্রিয় মেম্বারশিপ',  number_format($active_subs), '#c8080a'),
        ) as $s) {
            echo '<div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px;text-align:center;border-top:4px solid '.$s[2].'">';
            echo '<div style="font-size:30px;font-weight:800;color:'.$s[2].'">'.$s[1].'</div>';
            echo '<div style="font-size:13px;color:#666;margin-top:6px">'.$s[0].'</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    public static function page_settings() {
        if (isset($_GET['saved'])) echo '<div class="updated notice"><p>✅ সেটিংস সেভ হয়েছে।</p></div>';
        ?>
        <div class="wrap"><h1>⚙️ Rewards Settings</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php')?>">
        <?php wp_nonce_field('sbr_settings','sbr_snonce')?>
        <input type="hidden" name="action" value="sbr_save_settings">
        <h2>📋 General</h2>
        <table class="form-table">
            <tr><th>ফ্রি অর্ডার লিমিট</th><td><input type="number" name="sbr_free_limit" value="<?php echo get_option('sbr_free_limit',15)?>" class="small-text"></td></tr>
            <tr><th>সর্বনিম্ন Redeem (টাকা)</th><td>৳<input type="number" name="sbr_min_redeem_tk" value="<?php echo get_option('sbr_min_redeem_tk',5)?>" class="small-text"></td></tr>
        </table>
        <h2>🆓 ফ্রি Member পয়েন্ট</h2>
        <table class="form-table">
            <tr><th>প্রতি কত টাকায় ১ পয়েন্ট</th><td>৳<input type="number" name="sbr_free_rate_spend" value="<?php echo get_option('sbr_free_rate_spend',150)?>" class="small-text"></td></tr>
            <tr><th>Redeem: কত পয়েন্ট = কত টাকা</th><td><input type="number" name="sbr_free_redeem_pts" value="<?php echo get_option('sbr_free_redeem_pts',10)?>" class="small-text"> পয়েন্ট = ৳<input type="number" step="0.01" name="sbr_free_redeem_val" value="<?php echo get_option('sbr_free_redeem_val',0.50)?>" class="small-text"></td></tr>
        </table>
        <h2>🥈 Silver Member</h2>
        <table class="form-table">
            <tr><th>মূল্য (৬ মাস)</th><td>৳<input type="number" name="sbr_silver_price" value="<?php echo get_option('sbr_silver_price',599)?>" class="small-text"></td></tr>
            <tr><th>প্রতি কত টাকায় কত পয়েন্ট</th><td>৳<input type="number" name="sbr_silver_rate_spend" value="<?php echo get_option('sbr_silver_rate_spend',100)?>" class="small-text"> → <input type="number" name="sbr_silver_rate_base" value="<?php echo get_option('sbr_silver_rate_base',1)?>" class="small-text"> পয়েন্ট</td></tr>
            <tr><th>Bonus: কত টাকার উপরে +কত পয়েন্ট</th><td>৳<input type="number" name="sbr_silver_bonus_min" value="<?php echo get_option('sbr_silver_bonus_min',500)?>" class="small-text"> → +<input type="number" name="sbr_silver_bonus_pts" value="<?php echo get_option('sbr_silver_bonus_pts',5)?>" class="small-text"> পয়েন্ট (প্রতি ব্র্যাকেট)</td></tr>
            <tr><th>Redeem রেট</th><td><input type="number" name="sbr_silver_redeem_pts" value="<?php echo get_option('sbr_silver_redeem_pts',5)?>" class="small-text"> পয়েন্ট = ৳<input type="number" step="0.01" name="sbr_silver_redeem_val" value="<?php echo get_option('sbr_silver_redeem_val',1)?>" class="small-text"></td></tr>
            <tr><th>Refer bonus (giver / taker)</th><td><input type="number" name="sbr_silver_refer_giver" value="<?php echo get_option('sbr_silver_refer_giver',20)?>" class="small-text"> / <input type="number" name="sbr_silver_refer_taker" value="<?php echo get_option('sbr_silver_refer_taker',10)?>" class="small-text"> পয়েন্ট</td></tr>
        </table>
        <h2>🥇 Gold Member</h2>
        <table class="form-table">
            <tr><th>মূল্য (৬ মাস)</th><td>৳<input type="number" name="sbr_gold_price" value="<?php echo get_option('sbr_gold_price',1199)?>" class="small-text"></td></tr>
            <tr><th>প্রতি কত টাকায় কত পয়েন্ট</th><td>৳<input type="number" name="sbr_gold_rate_spend" value="<?php echo get_option('sbr_gold_rate_spend',100)?>" class="small-text"> → <input type="number" name="sbr_gold_rate_base" value="<?php echo get_option('sbr_gold_rate_base',3)?>" class="small-text"> পয়েন্ট</td></tr>
            <tr><th>Bonus: কত টাকার উপরে +কত পয়েন্ট</th><td>৳<input type="number" name="sbr_gold_bonus_min" value="<?php echo get_option('sbr_gold_bonus_min',500)?>" class="small-text"> → +<input type="number" name="sbr_gold_bonus_pts" value="<?php echo get_option('sbr_gold_bonus_pts',8)?>" class="small-text"> পয়েন্ট (প্রতি ব্র্যাকেট)</td></tr>
            <tr><th>Redeem রেট</th><td><input type="number" name="sbr_gold_redeem_pts" value="<?php echo get_option('sbr_gold_redeem_pts',5)?>" class="small-text"> পয়েন্ট = ৳<input type="number" step="0.01" name="sbr_gold_redeem_val" value="<?php echo get_option('sbr_gold_redeem_val',1.5)?>" class="small-text"></td></tr>
            <tr><th>Refer bonus (giver / taker)</th><td><input type="number" name="sbr_gold_refer_giver" value="<?php echo get_option('sbr_gold_refer_giver',30)?>" class="small-text"> / <input type="number" name="sbr_gold_refer_taker" value="<?php echo get_option('sbr_gold_refer_taker',10)?>" class="small-text"> পয়েন্ট</td></tr>
        </table>
        <?php submit_button('💾 সেভ করুন')?>
        </form></div>
        <?php
    }

    public static function save_settings() {
        check_admin_referer('sbr_settings','sbr_snonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $fields = array('sbr_free_limit','sbr_min_redeem_tk','sbr_free_rate_spend','sbr_free_redeem_pts','sbr_free_redeem_val','sbr_silver_price','sbr_silver_rate_spend','sbr_silver_rate_base','sbr_silver_bonus_min','sbr_silver_bonus_pts','sbr_silver_redeem_pts','sbr_silver_redeem_val','sbr_silver_refer_giver','sbr_silver_refer_taker','sbr_gold_price','sbr_gold_rate_spend','sbr_gold_rate_base','sbr_gold_bonus_min','sbr_gold_bonus_pts','sbr_gold_redeem_pts','sbr_gold_redeem_val','sbr_gold_refer_giver','sbr_gold_refer_taker');
        foreach ($fields as $f) {
            if (isset($_POST[$f])) update_option($f, sanitize_text_field($_POST[$f]));
        }
        wp_redirect(add_query_arg('saved','1',admin_url('admin.php?page=sbr-settings'))); exit;
    }

    public static function page_users() {
        global $wpdb;
        $users = $wpdb->get_results("SELECT user_id, SUM(points) as balance FROM {$wpdb->prefix}sbr_points GROUP BY user_id ORDER BY balance DESC LIMIT 50");
        if (isset($_GET['msg'])) echo '<div class="updated notice"><p>✅ পয়েন্ট আপডেট হয়েছে।</p></div>';
        echo '<div class="wrap"><h1>👥 Users & Points</h1>';
        echo '<table class="widefat striped"><thead><tr><th>User</th><th>Email</th><th>পয়েন্ট</th><th>Membership</th><th>Manual Add/Deduct</th></tr></thead><tbody>';
        foreach ($users as $u) {
            $user = get_user_by('id',$u->user_id);
            if (!$user) continue;
            $level = SBR_Membership::get_level($u->user_id);
            $tier  = SBR_Membership::get_tier_info($level);
            echo '<tr><td>'.esc_html($user->display_name).'</td><td>'.esc_html($user->user_email).'</td>';
            echo '<td><strong>'.number_format($u->balance).'</strong></td>';
            echo '<td style="color:'.$tier['color'].';font-weight:700">'.$tier['label'].'</td>';
            echo '<td><form method="post" action="'.admin_url('admin-post.php').'" style="display:inline-flex;gap:6px">';
            wp_nonce_field('sbr_mp','sbr_mp_nonce');
            echo '<input type="hidden" name="action" value="sbr_manual_points"><input type="hidden" name="user_id" value="'.$u->user_id.'">';
            echo '<input type="number" name="points" placeholder="পয়েন্ট" style="width:80px;padding:4px 6px">';
            echo '<select name="type"><option value="add">যোগ</option><option value="deduct">কাটো</option></select>';
            echo '<button type="submit" class="button button-small">OK</button></form></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function manual_points() {
        check_admin_referer('sbr_mp','sbr_mp_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $uid  = absint($_POST['user_id']);
        $pts  = absint($_POST['points']);
        $type = sanitize_text_field($_POST['type']);
        if ($type==='add') SBR_Points::add($uid,$pts,'manual',null,'Admin যোগ করেছে');
        else               SBR_Points::deduct($uid,$pts,'manual',null,'Admin কেটেছে');
        wp_redirect(add_query_arg('msg','1',admin_url('admin.php?page=sbr-users'))); exit;
    }

    public static function page_history() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT p.*, u.display_name FROM {$wpdb->prefix}sbr_points p LEFT JOIN {$wpdb->users} u ON u.ID=p.user_id ORDER BY p.created_at DESC LIMIT 100");
        echo '<div class="wrap"><h1>📋 Reward History</h1>';
        echo '<table class="widefat striped"><thead><tr><th>তারিখ</th><th>User</th><th>Type</th><th>পয়েন্ট</th><th>Note</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $c = $r->points>0?'color:#1a7a1a':'color:#b71c1c';
            $s = $r->points>0?'+':'';
            echo '<tr><td>'.date_i18n('j M Y H:i',strtotime($r->created_at)).'</td><td>'.esc_html($r->display_name).'</td><td>'.esc_html($r->type).'</td><td style="'.$c.';font-weight:700">'.$s.number_format($r->points).'</td><td>'.esc_html($r->note).'</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
