<?php
defined('ABSPATH') || exit;

class SBR_Checkout {

    public static function init() {
        // CHANGED: No blocking checkout — only popup on every page load
        add_action('wp_footer',            array(__CLASS__, 'subscription_popup'));
        add_action('wp_enqueue_scripts',   array(__CLASS__, 'enqueue'));
        // Session clear on thankyou
        add_action('woocommerce_thankyou', array(__CLASS__, 'clear_redeem_session'));
        // Points redeem AJAX
        add_action('wp_ajax_sbr_redeem_points',  array(__CLASS__, 'ajax_redeem'));
        add_action('wp_ajax_sbr_remove_redeem',  array(__CLASS__, 'ajax_remove_redeem'));
        // Apply redeem discount in cart
        add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'apply_redeem_discount'));
        // Show redeem box on checkout
        add_action('woocommerce_before_order_notes', array(__CLASS__, 'redeem_box'));
    }

    public static function clear_redeem_session($order_id = null) {
        if (WC()->session) {
            WC()->session->set('sbr_redeem_points', 0);
            WC()->session->__unset('sbr_redeem_points');
        }
    }

    // ── Popup — shows on EVERY page load for users who need subscription ──────
    public static function subscription_popup() {
        if (!is_user_logged_in()) return;
        $uid   = get_current_user_id();
        $level = SBR_Membership::get_level($uid);
        // Show popup if free limit reached (needs_sub) — every page load
        if ($level !== 'needs_sub') return;

        $tiers       = SBR_Membership::tiers();
        $silver_url  = SBR_Subscription::get_checkout_url('silver');
        $gold_url    = SBR_Subscription::get_checkout_url('gold');
        $dash_url    = wc_get_page_permalink('myaccount') . '#sbr-membership';
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
        #sbr-popup-overlay {
            display:flex; position:fixed; inset:0;
            background:rgba(0,0,0,.6); z-index:999999;
            align-items:center; justify-content:center; padding:16px;
            animation:sbrFadeIn .3s ease;
        }
        @keyframes sbrFadeIn { from{opacity:0} to{opacity:1} }
        #sbr-popup-box {
            background:#fff; border-radius:20px;
            max-width:560px; width:100%; position:relative;
            max-height:92vh; overflow-y:auto;
            font-family:'Hind Siliguri',sans-serif;
            box-shadow:0 20px 60px rgba(0,0,0,.3);
            animation:sbrSlideUp .3s ease;
        }
        @keyframes sbrSlideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
        #sbr-popup-close {
            position:absolute; top:12px; right:14px;
            background:rgba(0,0,0,.08); border:none; border-radius:50%;
            width:30px; height:30px; font-size:16px; cursor:pointer;
            color:#555; display:flex; align-items:center; justify-content:center;
            transition:background .15s;
        }
        #sbr-popup-close:hover { background:rgba(0,0,0,.15) }
        .sbr-popup-header {
            background:linear-gradient(135deg,#1a5c1a,#2e8b2e);
            border-radius:20px 20px 0 0; padding:28px 24px 24px;
            text-align:center; color:#fff;
        }
        .sbr-popup-header .sbr-ph-emoji { font-size:40px; margin-bottom:10px }
        .sbr-popup-header h2 {
            font-size:18px; font-weight:800; margin:0 0 10px;
            font-family:'Hind Siliguri',sans-serif;
        }
        .sbr-popup-header p {
            font-size:13px; color:rgba(255,255,255,.85);
            line-height:1.7; margin:0; font-family:'Hind Siliguri',sans-serif;
        }
        .sbr-popup-body { padding:20px }
        .sbr-plan-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px }
        .sbr-pcard {
            border-radius:14px; overflow:hidden;
            border:2px solid #eee; transition:transform .2s, box-shadow .2s;
        }
        .sbr-pcard:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.12) }
        .sbr-pcard-head {
            padding:14px; text-align:center; color:#fff;
        }
        .sbr-pcard-head h3 {
            font-size:15px; font-weight:800; margin:0 0 4px;
            font-family:'Hind Siliguri',sans-serif; color:#fff !important;
        }
        .sbr-pcard-head .sbr-price {
            font-size:26px; font-weight:800; font-family:'Hind Siliguri',sans-serif;
        }
        .sbr-pcard-head .sbr-dur {
            font-size:11px; opacity:.8; font-family:'Hind Siliguri',sans-serif;
        }
        .sbr-pcard-body { background:#fff; padding:12px }
        .sbr-pcard-body ul { list-style:none; margin:0 0 12px; padding:0 }
        .sbr-pcard-body ul li {
            font-size:12px; padding:4px 0; color:#444;
            display:flex; gap:6px; align-items:flex-start;
            border-bottom:1px solid #f5f5f5; font-family:'Hind Siliguri',sans-serif;
        }
        .sbr-pcard-body ul li:last-child { border-bottom:none }
        .sbr-pcard-body ul li.locked { color:#ccc }
        .sbr-pcard-btn {
            display:block; width:100%; padding:10px;
            text-align:center; border-radius:10px;
            font-size:14px; font-weight:700; text-decoration:none;
            color:#fff; border:none; cursor:pointer;
            font-family:'Hind Siliguri',sans-serif; transition:opacity .15s;
        }
        .sbr-pcard-btn:hover { opacity:.88; color:#fff }
        .sbr-popup-skip {
            text-align:center; padding-top:4px;
        }
        .sbr-popup-skip button {
            background:none; border:none; color:#aaa; font-size:12px;
            cursor:pointer; font-family:'Hind Siliguri',sans-serif;
            text-decoration:underline; padding:0;
        }
        .sbr-popup-skip button:hover { color:#555 }
        @media(max-width:480px){
            .sbr-plan-grid { grid-template-columns:1fr }
            #sbr-popup-box { border-radius:14px }
            .sbr-popup-header { border-radius:14px 14px 0 0 }
        }
        </style>

        <div id="sbr-popup-overlay">
          <div id="sbr-popup-box">
            <button id="sbr-popup-close" onclick="document.getElementById('sbr-popup-overlay').style.display='none'">✕</button>

            <div class="sbr-popup-header">
              <div class="sbr-ph-emoji">🛒</div>
              <h2>স্মার্ট বাজার মেম্বারশিপ নিন!</h2>
              <p>
                প্রতি মাস মাত্র <strong>১৫০–২৫০ টাকায়</strong> আপনার বাজার করার সকল ঝামেলা চিরতরে শেষ করুন —<br>
                ফ্রি ডেলিভারি, এক্সক্লুসিভ অফার, পয়েন্ট বোনাস এবং আরো অনেক সুবিধা উপভোগ করুন।
              </p>
            </div>

            <div class="sbr-popup-body">
              <div class="sbr-plan-grid">
                <?php
                $popup_plans = array(
                    'silver' => array(
                        'title'   => '🥈 সিলভার',
                        'price'   => '৳১৫০',
                        'color'   => '#2e8b2e',
                        'url'     => $silver_url,
                        'have'    => array('ডেলিভারি ফ্রি (মাসে ৪ বার)','১০০ টাকায় ১ পয়েন্ট','৫ পয়েন্ট = ১ টাকা','রেফারে ২০ পয়েন্ট','বিশেষ উপহার'),
                        'locked'  => array('Member Only অফার','বাজার লিস্ট রিমাইন্ডার','পয়েন্ট বুস্টার'),
                    ),
                    'gold' => array(
                        'title'   => '🏅 গোল্ড',
                        'price'   => '৳২৫০',
                        'color'   => '#c8080a',
                        'url'     => $gold_url,
                        'have'    => array('আনলিমিটেড ফ্রি ডেলিভারি','১০০ টাকায় ৩ পয়েন্ট','৫ পয়েন্ট = ১.৫ টাকা','রেফারে ৩০ পয়েন্ট','Member Only অফার','বাজার লিস্ট রিমাইন্ডার','পয়েন্ট বুস্টার'),
                        'locked'  => array(),
                    ),
                );
                foreach ($popup_plans as $pk => $pp): ?>
                <div class="sbr-pcard">
                  <div class="sbr-pcard-head" style="background:<?php echo $pp['color']?>">
                    <h3><?php echo $pp['title']?></h3>
                    <div class="sbr-price"><?php echo $pp['price']?></div>
                    <div class="sbr-dur">/ মাস</div>
                  </div>
                  <div class="sbr-pcard-body">
                    <ul>
                    <?php foreach($pp['have'] as $f): ?>
                    <li><span style="color:#1a7a1a;font-weight:700;flex-shrink:0">✓</span><?php echo $f?></li>
                    <?php endforeach?>
                    <?php foreach($pp['locked'] as $f): ?>
                    <li class="locked"><span style="color:#ddd;font-weight:700;flex-shrink:0">✕</span><span style="text-decoration:line-through"><?php echo $f?></span></li>
                    <?php endforeach?>
                    </ul>
                    <a href="<?php echo esc_url($pp['url'])?>" class="sbr-pcard-btn" style="background:<?php echo $pp['color']?>">নিন</a>
                  </div>
                </div>
                <?php endforeach?>
              </div>

              <div class="sbr-popup-skip">
                <button onclick="document.getElementById('sbr-popup-overlay').style.display='none'">এখন না, পরে নেব</button>
              </div>
            </div>
          </div>
        </div>
        <?php
    }

    // ── Redeem Box on Checkout ────────────────────────────────────────────────
    public static function redeem_box() {
        $uid = get_current_user_id();
        if (!$uid) return;

        // Check for recommended points plugin balance
        $balance = self::get_points_balance($uid);
        if ($balance <= 0) return;

        $membership = SBR_Membership::get_level($uid);
        $min_pts    = SBR_Points::min_redeem_points($membership);
        $redeemed   = (int)WC()->session->get('sbr_redeem_points');
        if ($balance < $min_pts) return;
        ?>
        <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:16px;margin-bottom:20px;font-family:'Hind Siliguri',sans-serif">
          <h4 style="margin:0 0 8px;color:#b8860b;font-size:15px;font-family:'Hind Siliguri',sans-serif">🪙 পয়েন্ট দিয়ে ছাড় নিন</h4>
          <p style="font-size:13px;color:#666;margin:0 0 10px">ব্যালেন্স: <strong><?php echo number_format($balance)?> পয়েন্ট</strong></p>
          <?php if ($redeemed > 0):
            $disc = SBR_Points::points_to_taka($redeemed, $membership);?>
          <p style="color:#1a7a1a;font-size:13px;font-weight:700;margin:0 0 8px">✅ <?php echo $redeemed?> পয়েন্ট ব্যবহার হচ্ছে (৳<?php echo $disc?> ছাড়)</p>
          <button type="button" id="sbr-remove" style="background:#fce4ec;color:#b71c1c;border:none;padding:6px 14px;border-radius:6px;font-size:12px;cursor:pointer;font-family:'Hind Siliguri',sans-serif">বাতিল করুন</button>
          <?php else: ?>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="number" id="sbr-pts-input" min="<?php echo $min_pts?>" max="<?php echo $balance?>" value="<?php echo $balance?>" style="width:110px;padding:7px;border:1px solid #ddd;border-radius:6px;font-size:13px">
            <button type="button" id="sbr-apply" style="background:#1a7a1a;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Hind Siliguri',sans-serif">ব্যবহার করুন</button>
          </div>
          <?php endif?>
        </div>
        <?php
    }

    // ── Get points balance — supports both our system and SWPR plugin ─────────
    public static function get_points_balance($user_id) {
        // Try Simple WooCommerce Points & Rewards (free plugin)
        if (function_exists('wc_points_rewards_get_points_balance')) {
            return (int)wc_points_rewards_get_points_balance($user_id);
        }
        // Try WooCommerce Points and Rewards (official)
        if (class_exists('WC_Points_Rewards_Manager')) {
            return (int)WC_Points_Rewards_Manager::get_users_points($user_id);
        }
        // Try WLPR plugin meta key
        $pts = get_user_meta($user_id, '_wc_points_balance', true);
        if ($pts !== '') return (int)$pts;
        // Fallback: our own system
        return SBR_Points::get_balance($user_id);
    }

    public static function apply_redeem_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        $uid      = get_current_user_id();
        $redeemed = (int)WC()->session->get('sbr_redeem_points');
        if ($redeemed > 0) {
            $membership = SBR_Membership::get_level($uid);
            $discount   = SBR_Points::points_to_taka($redeemed, $membership);
            $cart->add_fee('🪙 পয়েন্ট ছাড় (' . $redeemed . ' pts)', -$discount);
        }
    }

    public static function ajax_redeem() {
        if (function_exists('wc_load_cart')) wc_load_cart();
        check_ajax_referer('sbr_nonce', 'nonce');
        $uid     = get_current_user_id();
        $points  = absint($_POST['points'] ?? 0);
        $memship = SBR_Membership::get_level($uid);
        $min     = SBR_Points::min_redeem_points($memship);
        $balance = self::get_points_balance($uid);
        if ($points < $min)     wp_send_json_error(array('message' => 'সর্বনিম্ন ' . $min . ' পয়েন্ট ব্যবহার করুন।'));
        if ($points > $balance) wp_send_json_error(array('message' => 'পর্যাপ্ত পয়েন্ট নেই।'));
        WC()->session->set('sbr_redeem_points', $points);
        wp_send_json_success();
    }

    public static function ajax_remove_redeem() {
        if (function_exists('wc_load_cart')) wc_load_cart();
        check_ajax_referer('sbr_nonce', 'nonce');
        self::clear_redeem_session();
        wp_send_json_success();
    }

    public static function enqueue() {
        $ajax_url = admin_url('admin-ajax.php');
        if (is_ssl() || strpos(get_option('siteurl'), 'https') === 0) {
            $ajax_url = set_url_scheme($ajax_url, 'https');
        }
        wp_localize_script('jquery', 'sbr_vars', array(
            'nonce'    => wp_create_nonce('sbr_nonce'),
            'ajax_url' => $ajax_url,
        ));
        wp_add_inline_script('jquery', "
        document.addEventListener('DOMContentLoaded', function(){
            var applyBtn  = document.getElementById('sbr-apply');
            var removeBtn = document.getElementById('sbr-remove');
            if(applyBtn) applyBtn.addEventListener('click', function(){
                var pts = document.getElementById('sbr-pts-input').value;
                fetch(sbr_vars.ajax_url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=sbr_redeem_points&nonce='+sbr_vars.nonce+'&points='+pts})
                .then(r=>r.json()).then(function(d){
                    if(d.success) location.reload();
                    else alert(d.data&&d.data.message?d.data.message:'Error');
                });
            });
            if(removeBtn) removeBtn.addEventListener('click', function(){
                fetch(sbr_vars.ajax_url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=sbr_remove_redeem&nonce='+sbr_vars.nonce})
                .then(r=>r.json()).then(function(d){ if(d.success) location.reload(); });
            });
        });
        ");
    }
}
