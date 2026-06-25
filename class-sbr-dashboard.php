<?php
defined('ABSPATH') || exit;

class SBR_Dashboard {

    public static function init() {
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard', 10);
        add_action('woocommerce_account_dashboard', array(__CLASS__, 'render'), 10);

        add_action('init', array(__CLASS__, 'endpoints'));
        add_filter('woocommerce_account_menu_items', array(__CLASS__, 'menu_items'));
        add_action('woocommerce_account_sbr-rewards_endpoint',    array(__CLASS__, 'tab_rewards'));
        add_action('woocommerce_account_sbr-history_endpoint',    array(__CLASS__, 'tab_history'));
        add_action('woocommerce_account_sbr-membership_endpoint', array(__CLASS__, 'tab_membership'));

        add_action('woocommerce_before_account_navigation', array(__CLASS__, 'nav_start'), 1);
        add_action('woocommerce_after_account_navigation',  array(__CLASS__, 'nav_end'),   999);

        add_action('wp_head', array(__CLASS__, 'global_css'));
    }

    public static function endpoints() {
        add_rewrite_endpoint('sbr-rewards',    EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('sbr-history',    EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('sbr-membership', EP_ROOT | EP_PAGES);
    }

    // ── Menu items — remove edit-address, add password, add membership ────────
    public static function menu_items($items) {
        unset($items['downloads']);
        unset($items['edit-address']); // Remove আমার ঠিকানা

        $new = array();
        foreach ($items as $k => $v) {
            $new[$k] = $v;
            if ($k === 'orders') {
                $new['sbr-rewards']    = '🪙 রিওয়ার্ড';
                $new['sbr-history']    = '📋 পেয়েন্টসমূহ';
                $new['sbr-membership'] = '👑 মেম্বারশিপ';
            }
        }
        return $new;
    }

    public static function global_css() {
        if (!is_account_page()) return;
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
        body.woocommerce-account { background:#f4f6f8 }
        .woocommerce-account * { font-family:'Hind Siliguri',sans-serif !important }

        /* Hide WC default nav + English welcome */
        .woocommerce-MyAccount-navigation { display:none !important }
        .woocommerce-MyAccount-content > p:first-child,
        .woocommerce-MyAccount-content > p:nth-child(2),
        .woocommerce-message.woocommerce-message--info { display:none !important }

        /* Layout */
        .sbr-wrap { display:flex !important; flex-direction:row !important; align-items:flex-start !important; gap:16px !important }
        .sbr-sidebar {
            width:210px !important; flex-shrink:0 !important;
            background:#fff; border-radius:14px;
            box-shadow:0 2px 12px rgba(0,0,0,.08);
            overflow:hidden; position:sticky; top:80px; align-self:flex-start;
        }
        .sbr-content { flex:1 !important; min-width:0 !important }

        .sbr-sidebar a {
            display:flex; align-items:center; gap:10px;
            padding:13px 16px; font-size:13px; font-weight:600;
            color:#444; text-decoration:none; border-bottom:1px solid #f0f0f0;
            transition:all .15s; font-family:'Hind Siliguri',sans-serif !important;
        }
        .sbr-sidebar a:last-child { border-bottom:none }
        .sbr-sidebar a:hover { background:#f0fff0; color:#1a7a1a; padding-left:20px }
        .sbr-sidebar a.sbr-active { background:#1a5c1a !important; color:#fff !important }
        .sbr-sidebar a.sbr-active:hover { padding-left:16px }
        .sbr-sidebar .sbr-icon { font-size:15px; flex-shrink:0 }

        /* Mobile sticky */
        @media(max-width:768px){
            .sbr-wrap { flex-direction:column !important }
            .sbr-sidebar {
                width:100% !important; position:sticky; top:0; z-index:999;
                border-radius:0; display:flex; flex-wrap:nowrap; overflow-x:auto;
            }
            .sbr-sidebar a {
                flex-direction:column; gap:4px; padding:10px 12px; font-size:11px;
                border-bottom:none; border-right:1px solid #f0f0f0;
                white-space:nowrap; flex-shrink:0;
            }
            .sbr-sidebar a:hover { padding-left:12px }
            .sbr-sidebar .sbr-icon { font-size:18px }
        }

        .sbr-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden }

        /* Form fields */
        .woocommerce-account .woocommerce-MyAccount-content input[type=text],
        .woocommerce-account .woocommerce-MyAccount-content input[type=email],
        .woocommerce-account .woocommerce-MyAccount-content input[type=password],
        .woocommerce-account .woocommerce-MyAccount-content input[type=tel],
        .woocommerce-account .woocommerce-MyAccount-content select,
        .woocommerce-account .woocommerce-MyAccount-content textarea {
            border:2px solid #e0e0e0 !important; border-radius:8px !important;
            padding:10px 14px !important; font-size:14px !important;
        }
        .woocommerce-account .woocommerce-MyAccount-content input:focus { border-color:#1a7a1a !important; outline:none !important }
        .woocommerce-account .woocommerce-MyAccount-content button[type=submit],
        .woocommerce-account .woocommerce-MyAccount-content input[type=submit] {
            background:#1a7a1a !important; color:#fff !important; border:none !important;
            border-radius:8px !important; padding:11px 24px !important;
            font-size:14px !important; font-weight:700 !important; cursor:pointer !important;
        }

        /* Orders table */
        .woocommerce-orders-table thead th { background:#1a5c1a !important; color:#fff !important }
        .woocommerce-orders-table { border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.06) }
        .woocommerce-orders-table tbody tr:hover { background:#f0fff0 !important }

        /* Dashboard inner */
        .sbd { font-family:'Hind Siliguri',sans-serif }
        .sbd-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px }
        .sbd-sc { background:#fff; border-radius:14px; padding:16px 12px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.06) }
        .sbd-sc .sl { font-size:12px; color:#888; margin-bottom:4px; font-weight:600 }
        .sbd-sc .sn { font-size:26px; font-weight:800; color:#1a7a1a; line-height:1.1 }
        .sbd-sc .ss { font-size:11px; color:#bbb; margin-top:3px }
        .sbd-sh { display:flex; align-items:center; justify-content:space-between; margin:16px 0 10px }
        .sbd-sh h3 { font-size:14px; font-weight:700; margin:0; color:#1a1a1a }
        .sbd-sh a { font-size:13px; color:#1a7a1a; text-decoration:none; font-weight:700 }
        .sbd-ow { background:#fff; border-radius:14px; overflow:hidden; margin-bottom:18px; box-shadow:0 2px 10px rgba(0,0,0,.06) }
        .sbd-ot { width:100%; border-collapse:collapse; font-size:12px }
        .sbd-ot thead tr { background:#1a5c1a }
        .sbd-ot th { padding:11px 12px; text-align:left; font-weight:700; color:#fff; font-size:12px }
        .sbd-ot td { padding:10px 12px; border-bottom:1px solid #f5f5f5; vertical-align:middle }
        .sbd-ot tbody tr:last-child td { border-bottom:none }
        .sbd-ot tbody tr:hover { background:#f9fff9 }
        .sbdg { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700 }
        .sbd-ol { color:#1a7a1a; font-weight:700; text-decoration:none }
        .sbd-empty { text-align:center; padding:36px; color:#bbb; font-size:14px }

        /* Subscription plans */
        .sbd-plans { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px }
        .sbd-plan { border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.08); border:2px solid transparent; transition:transform .2s,box-shadow .2s }
        .sbd-plan:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.12) }
        .sbd-plan.sbr-active-plan { border-color:#1a7a1a }
        .sbd-ph { padding:16px; text-align:center; color:#fff }
        .sbd-ph h4 { font-size:16px; font-weight:800; margin:0 0 8px; font-family:'Hind Siliguri',sans-serif; color:#fff !important }
        .sbd-ph .pr { font-size:26px; font-weight:800; font-family:'Hind Siliguri',sans-serif; color:#fff !important }
        .sbd-ph .pr-sub { font-size:13px; color:rgba(255,255,255,.85); margin-top:2px; font-family:'Hind Siliguri',sans-serif }
        .sbd-ph .du { font-size:11px; opacity:.8 }
        .sbd-pb { background:#fff; padding:12px 14px }
        .sbd-pb ul { list-style:none; padding:0; margin:0 }
        .sbd-pb ul li { font-size:12px; padding:5px 0; display:flex; gap:7px; border-bottom:1px solid #f5f5f5; align-items:flex-start; line-height:1.4 }
        .sbd-pb ul li:last-child { border-bottom:none }
        .sbd-pb ul li.locked { color:#ccc }
        .sbd-pf { padding:12px 14px; background:#fff; border-top:1px solid #f0f0f0 }
        .sbd-pbtn { display:block; width:100%; padding:10px; text-align:center; border-radius:10px; font-size:14px; font-weight:700; text-decoration:none; border:none; cursor:pointer; color:#fff; font-family:'Hind Siliguri',sans-serif; transition:opacity .15s }
        .sbd-pbtn:hover { opacity:.88; color:#fff !important }
        .sbd-pbtn.cur { background:#e8f5e9 !important; color:#1a7a1a !important; cursor:default; border:1px solid #c8e6c9; pointer-events:none }
        .sbd-warn { background:linear-gradient(135deg,#fff3e0,#ffe8cc); border-left:4px solid #e65100; border-radius:10px; padding:12px 16px; margin-bottom:14px; font-size:13px; color:#e65100; font-weight:600 }
        @media(max-width:600px){
            .sbd-stats { grid-template-columns:1fr 1fr }
            .sbd-plans { grid-template-columns:1fr }
            .sbd-ot th:nth-child(2),.sbd-ot td:nth-child(2) { display:none }
        }
        </style>
        <?php
    }

    // ── Sidebar nav ───────────────────────────────────────────────────────────
    public static function nav_start() {
        if (!is_account_page()) return;
        $current = self::current_endpoint();

        // Check if Simple Points & Rewards is active
        $has_spr = class_exists('WC_Points_Rewards') || function_exists('wc_points_rewards_get_points_balance');

        $nav = array(
            ''               => array('icon'=>'👤', 'label'=>'ড্যাশবোর্ড'),
            'orders'         => array('icon'=>'📦', 'label'=>'অর্ডার সমূহ'),
            'edit-account'   => array('icon'=>'ℹ️',  'label'=>'একাউন্ট'),
            'sbr-rewards'    => array('icon'=>'🪙', 'label'=>'রিওয়ার্ড'),
            'sbr-history'    => array('icon'=>'📋', 'label'=>'পেয়েন্টসমূহ'),
            'sbr-membership' => array('icon'=>'👑', 'label'=>'মেম্বারশিপ'),
            'customer-logout'=> array('icon'=>'🚪', 'label'=>'লগআউট'),
        );

        echo '<div class="sbr-wrap">';
        echo '<nav class="sbr-sidebar">';
        foreach ($nav as $ep => $data) {
            if ($ep === 'customer-logout') $url = wp_logout_url(home_url());
            elseif ($ep === '') $url = wc_get_page_permalink('myaccount');
            else $url = wc_get_account_endpoint_url($ep);
            $active = ($current === $ep) ? ' sbr-active' : '';
            echo '<a href="'.esc_url($url).'" class="'.$active.'">';
            echo '<span class="sbr-icon">'.$data['icon'].'</span>';
            echo '<span>'.$data['label'].'</span>';
            echo '</a>';
        }
        echo '</nav>';
        echo '<div class="sbr-content">';
    }

    public static function nav_end() {
        if (!is_account_page()) return;
        echo '</div></div>';
    }

    private static function current_endpoint() {
        global $wp;
        $eps = array('orders','edit-address','edit-account','downloads','sbr-rewards','sbr-history','sbr-membership','customer-logout');
        foreach ($eps as $ep) { if (isset($wp->query_vars[$ep])) return $ep; }
        return '';
    }

    // ── Get points — from external plugin or fallback ─────────────────────────
    private static function get_points($uid) {
        if (function_exists('wc_points_rewards_get_points_balance')) {
            return (int)wc_points_rewards_get_points_balance($uid);
        }
        if (class_exists('WC_Points_Rewards_Manager')) {
            return (int)WC_Points_Rewards_Manager::get_users_points($uid);
        }
        $pts = get_user_meta($uid, '_wc_points_balance', true);
        if ($pts !== '') return (int)$pts;
        return SBR_Points::get_balance($uid);
    }

    // ── MAIN DASHBOARD ────────────────────────────────────────────────────────
    public static function render() {
        $user       = wp_get_current_user();
        $uid        = $user->ID;
        $orders     = SBR_Membership::get_completed_orders($uid);
        $points     = self::get_points($uid);
        $level      = SBR_Membership::get_level($uid);
        $tier       = SBR_Membership::get_tier_info($level);
        $sub        = SBR_Subscription::get_active($uid);
        $free_limit = (int)get_option('sbr_free_limit', SBR_FREE_LIMIT);
        $free_left  = max(0, $free_limit - $orders);
        $recent     = wc_get_orders(array('customer'=>$uid,'limit'=>5,'orderby'=>'date','order'=>'DESC'));
        $points_words = SBR_Points::to_bengali_words($points);
        $tiers = SBR_Membership::tiers();
        $plan_colors = array('silver'=>'#2e8b2e','gold'=>'#c8080a');
        $status_map = array(
            'completed'  => array('সম্পন্ন',   '#e6f4ea','#1a7a1a'),
            'processing' => array('প্রসেসিং',  '#fff3e0','#e65100'),
            'on-hold'    => array('অপেক্ষমাণ', '#e3f2fd','#1565c0'),
            'pending'    => array('পেন্ডিং',   '#f3e5f5','#6a1b9a'),
            'cancelled'  => array('বাতিল',     '#fce4ec','#b71c1c'),
            'refunded'   => array('রিফান্ড',   '#f5f5f5','#616161'),
        );
        ?>
        <div class="sbd">

        <!-- Welcome Banner — FIX: badge has no box, just text -->
        <div style="background:linear-gradient(135deg,#1a5c1a,#2e8b2e);border-radius:14px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 4px 16px rgba(26,92,26,.2)">
            <div style="line-height:1.3">
                <div style="font-size:11px;color:rgba(255,255,255,.7);font-weight:600;margin-bottom:2px">স্বাগতম 👋</div>
                <div style="font-size:18px;font-weight:800;color:#fff;margin-bottom:2px"><?php echo esc_html($user->display_name)?></div>
                <div style="font-size:11px;color:rgba(255,255,255,.8)">
                <?php if ($level === 'needs_sub'): ?>মেম্বারশিপ নিন, অর্ডার করুন
                <?php elseif ($level === 'free'): ?>আরো <?php echo $free_left?> অর্ডারে মেম্বারশিপ পাবেন
                <?php elseif ($sub): ?>মেয়াদ: <?php echo date_i18n('j M Y', strtotime($sub->expires_at))?>
                <?php endif?>
                </div>
            </div>
            <!-- FIX: No box/border — just clean text badge -->
            <div style="text-align:center;flex-shrink:0">
                <div style="font-size:24px;line-height:1.1">
                <?php echo $level==='needs_sub'?'🚫':($level==='free'?'🆓':($level==='silver'?'🥈':'🏅'))?>
                </div>
                <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,.9);margin-top:3px;white-space:nowrap">
                <?php echo $level==='needs_sub'?'সদস্য নন':($level==='free'?'ফ্রি':($level==='silver'?'সিলভার':'গোল্ড'))?>
                </div>
            </div>
        </div>

        <?php if ($level === 'needs_sub'): ?>
        <div class="sbd-warn">⚠️ আপনার <?php echo $free_limit?>টি বিনামূল্যে অর্ডার শেষ। <a href="<?php echo wc_get_account_endpoint_url('sbr-membership')?>" style="color:#1a7a1a;font-weight:700">মেম্বারশিপ নিন →</a></div>
        <?php elseif ($sub && strtotime($sub->expires_at) < strtotime('+3 days')): ?>
        <div class="sbd-warn">⚠️ আপনার মেম্বারশিপ <?php echo date_i18n('j M Y', strtotime($sub->expires_at))?> তারিখে শেষ হবে।</div>
        <?php endif?>

        <!-- Stats — মোট পয়েন্ট, অর্ডার, মেম্বারশিপ -->
        <div class="sbd-stats">
            <div class="sbd-sc">
                <div class="sl">মোট পয়েন্ট</div>
                <div class="sn"><?php echo number_format($points)?></div>
                <div class="ss"><?php echo $points_words?></div>
            </div>
            <div class="sbd-sc">
                <div class="sl">সম্পন্ন অর্ডার</div>
                <div class="sn"><?php echo $orders?></div>
                <div class="ss">মোট অর্ডার</div>
            </div>
            <div class="sbd-sc">
                <div class="sl">মেম্বারশিপ</div>
                <?php if ($level === 'needs_sub'): ?>
                <div class="sn" style="font-size:13px;color:#c8080a">সদস্য নন</div>
                <div class="ss"><a href="<?php echo wc_get_account_endpoint_url('sbr-membership')?>" style="color:#1a7a1a;font-weight:700;font-size:12px">নিন →</a></div>
                <?php elseif ($level === 'free'): ?>
                <div class="sn" style="font-size:13px;color:#888">ফ্রি</div>
                <div class="ss"><?php echo $free_left?> অর্ডার বাকি</div>
                <?php else: ?>
                <div class="sn" style="font-size:13px;color:<?php echo $plan_colors[$level]?>"><?php echo $tier['label']?></div>
                <div class="ss"><?php echo $sub ? date_i18n('j M Y', strtotime($sub->expires_at)) : ''?></div>
                <?php endif?>
            </div>
        </div>

        <?php if ($level === 'free'): ?>
        <div class="sbr-card" style="padding:14px 18px;margin-bottom:16px">
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;color:#1a1a1a">আমার লেভেল</div>
            <div style="text-align:center;font-size:12px;color:#555;margin-bottom:8px">
                <?php echo $orders?> / <?php echo $free_limit?> অর্ডার সম্পন্ন
            </div>
            <div style="background:#f0f0f0;border-radius:20px;height:8px;overflow:hidden">
                <div style="height:100%;background:linear-gradient(90deg,#1a7a1a,#3aad3a);border-radius:20px;width:<?php echo min(100,round($orders/$free_limit*100))?>%"></div>
            </div>
        </div>
        <?php endif?>

        <!-- Recent Orders -->
        <div class="sbd-sh">
            <h3>📋 সাম্প্রতিক অর্ডার</h3>
            <a href="<?php echo wc_get_account_endpoint_url('orders')?>">সব দেখুন →</a>
        </div>
        <div class="sbd-ow">
            <?php if (!empty($recent)): ?>
            <table class="sbd-ot">
                <thead><tr><th>অর্ডার ID</th><th>তারিখ</th><th>মোট</th><th>পেমেন্টস</th><th>পয়েন্ট</th><th>অবস্থা</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $o):
                    $st=$o->get_status(); $si=$status_map[$st]??array($st,'#f5f5f5','#555');
                    $ep=(int)$o->get_meta('_sbr_points_earned'); $ic=$o->get_item_count();
                ?>
                <tr>
                    <td><a class="sbd-ol" href="<?php echo $o->get_view_order_url()?>">#ORD<?php echo $o->get_id()?></a></td>
                    <td><?php echo $o->get_date_created()->date_i18n('j M, Y')?></td>
                    <td>৳<?php echo number_format($o->get_total(),0)?> x <?php echo $ic?> আইটেম</td>
                    <td><span class="sbdg" style="background:#e6f4ea;color:#1a7a1a">সম্পন্ন</span></td>
                    <td><?php if($ep>0):?><span class="sbdg" style="background:#fff8e1;color:#d4a017">+<?php echo $ep?></span><?php else:?>—<?php endif?></td>
                    <td><span class="sbdg" style="background:<?php echo $si[1]?>;color:<?php echo $si[2]?>"><?php echo $si[0]?></span></td>
                </tr>
                <?php endforeach?>
                </tbody>
            </table>
            <?php else:?>
            <div class="sbd-empty">🛒 এখনো কোনো অর্ডার নেই। <a href="<?php echo home_url('/shop')?>" style="color:#1a7a1a">কেনাকাটা শুরু করুন →</a></div>
            <?php endif?>
        </div>

        <!-- Membership Plans — Gold first (সবার্োচ্চ), Silver second — image style -->
        <div id="sbr-membership" style="font-size:14px;font-weight:700;margin-bottom:12px;color:#1a1a1a">মেম্বারশিপ নিন এবং আরো সুবিধা উপভোগ করুন</div>
        <div class="sbd-plans">
        <?php foreach (['silver','gold'] as $plan):
            $t=$tiers[$plan]; $is_active=($level===$plan);
            $url=SBR_Subscription::get_checkout_url($plan);
            $hdr=$plan_colors[$plan];
        ?>
        <div class="sbd-plan <?php echo $is_active?'sbr-active-plan':''?>">
            <div class="sbd-ph" style="background:<?php echo $hdr?>">
                <h4><?php echo $plan==='gold'?'GOLD (সর্বোচ্চ)':'SILVER'?></h4>
                <!-- Image-style: big price then / মাস label -->
                <div class="pr">৳<?php echo number_format($t['price'])?> / ১ মাস</div>
            </div>
            <div class="sbd-pb">
                <ul>
                <?php foreach($t['benefits_have'] as $b):?>
                <li><span style="color:#1a7a1a;font-weight:700;flex-shrink:0;margin-top:1px">✓</span><span><?php echo $b?></span></li>
                <?php endforeach?>
                <?php if(!empty($t['benefits_locked'])):?>
                <?php foreach($t['benefits_locked'] as $b):?>
                <li class="locked"><span style="color:#ddd;font-weight:700;flex-shrink:0;margin-top:1px">✕</span><span style="text-decoration:line-through;color:#ccc"><?php echo $b?></span></li>
                <?php endforeach?>
                <?php endif?>
                </ul>
            </div>
            <div class="sbd-pf">
                <?php if($is_active):?>
                <button class="sbd-pbtn cur">✓ বর্তমান প্ল্যান</button>
                <?php else:?>
                <a href="<?php echo esc_url($url)?>" class="sbd-pbtn" style="background:<?php echo $hdr?>">নিন</a>
                <?php endif?>
            </div>
        </div>
        <?php endforeach?>
        </div>

        </div><!-- .sbd -->
        <?php
    }

    // ── Rewards Tab ───────────────────────────────────────────────────────────
    public static function tab_rewards() {
        $uid     = get_current_user_id();
        $balance = SBR_Checkout::get_points_balance($uid);
        $words   = SBR_Points::to_bengali_words($balance);
        $history = SBR_Points::get_history($uid, 10);
        echo '<div class="sbr-card" style="padding:24px;text-align:center;margin-bottom:18px">';
        echo '<div style="font-size:12px;color:#888;font-weight:600;margin-bottom:8px">বর্তমান পয়েন্ট ব্যালেন্স</div>';
        echo '<div style="font-size:52px;font-weight:800;color:#d4a017;line-height:1">'.number_format($balance).'</div>';
        echo '<div style="font-size:12px;color:#aaa;margin-top:6px">('.esc_html($words).') পয়েন্ট</div>';
        echo '</div>';
        // Plugin notice
        if (!function_exists('wc_points_rewards_get_points_balance') && !class_exists('WC_Points_Rewards_Manager')) {
            echo '<div style="background:#e8f5e9;border:1px solid #c8e6c9;border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:#2e7d32">';
            echo '💡 <strong>পয়েন্ট সিস্টেম সেটআপ করতে:</strong> WordPress Plugins থেকে <strong>"WooCommerce Points and Rewards"</strong> বা <strong>"Simple Points and Rewards for WooCommerce"</strong> plugin install করুন।';
            echo '</div>';
        }
        echo '<h3 style="font-size:14px;font-weight:700;margin:0 0 10px">📋 সাম্প্রতিক লেনদেন</h3>';
        self::history_table($history);
    }

    public static function tab_history() {
        $history = SBR_Points::get_history(get_current_user_id(), 50);
        echo '<h3 style="font-size:14px;font-weight:700;margin:0 0 10px">📋 পয়েন্ট ইতিহাস</h3>';
        self::history_table($history);
    }

    private static function history_table($history) {
        if (empty($history)) {
            echo '<div class="sbr-card" style="padding:36px;text-align:center;color:#bbb">কোনো লেনদেন নেই।</div>'; return;
        }
        echo '<div class="sbr-card"><table style="width:100%;border-collapse:collapse;font-size:13px">';
        echo '<thead><tr style="background:#1a5c1a"><th style="padding:11px 13px;text-align:left;color:#fff;font-weight:700">তারিখ</th><th style="padding:11px 13px;text-align:left;color:#fff;font-weight:700">বিবরণ</th><th style="padding:11px 13px;text-align:right;color:#fff;font-weight:700">পয়েন্ট</th></tr></thead><tbody>';
        foreach ($history as $h) {
            $c=$h->points>0?'#1a7a1a':'#c8080a'; $s=$h->points>0?'+':'';
            echo '<tr style="border-bottom:1px solid #f5f5f5">';
            echo '<td style="padding:10px 13px;color:#777">'.date_i18n('j M, Y',strtotime($h->created_at)).'</td>';
            echo '<td style="padding:10px 13px">'.esc_html($h->note?:ucfirst($h->source)).'</td>';
            echo '<td style="padding:10px 13px;text-align:right;font-weight:800;color:'.$c.'">'.$s.number_format($h->points).'</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    // ── Membership Tab ────────────────────────────────────────────────────────
    public static function tab_membership() {
        $uid   = get_current_user_id();
        $level = SBR_Membership::get_level($uid);
        $sub   = SBR_Subscription::get_active($uid);
        $tiers = SBR_Membership::tiers();
        $plan_colors = array('silver'=>'#2e8b2e','gold'=>'#c8080a');

        echo '<h3 style="font-size:14px;font-weight:700;margin:0 0 14px">👑 মেম্বারশিপ প্ল্যান</h3>';
        if ($sub) {
            $t = SBR_Membership::get_tier_info($level);
            echo '<div class="sbr-card" style="padding:18px;text-align:center;margin-bottom:16px;border:2px solid #1a7a1a">';
            echo '<div style="font-size:22px;font-weight:800;color:#1a7a1a">'.$t['label'].'</div>';
            echo '<div style="font-size:13px;color:#555;margin-top:4px">মেয়াদ শেষ: <strong>'.date_i18n('j M Y',strtotime($sub->expires_at)).'</strong></div>';
            echo '</div>';
        }
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">';
        foreach (['silver','gold'] as $plan) {
            $t=$tiers[$plan]; $hdr=$plan_colors[$plan]; $is_active=($level===$plan);
            $url=SBR_Subscription::get_checkout_url($plan);
            echo '<div class="sbd-plan '.($is_active?'sbr-active-plan':'').'">';
            echo '<div class="sbd-ph" style="background:'.$hdr.'"><h4>'.($plan==='gold'?'GOLD (সর্বোচ্চ)':'SILVER').'</h4><div class="pr">৳'.number_format($t['price']).' / ১ মাস</div></div>';
            echo '<div class="sbd-pb"><ul>';
            foreach($t['benefits_have'] as $b) echo '<li><span style="color:#1a7a1a;font-weight:700;flex-shrink:0;margin-top:1px">✓</span><span style="font-size:12px">'.$b.'</span></li>';
            if(!empty($t['benefits_locked'])) foreach($t['benefits_locked'] as $b) echo '<li class="locked"><span style="color:#ddd;font-weight:700;flex-shrink:0;margin-top:1px">✕</span><span style="text-decoration:line-through;color:#ccc;font-size:12px">'.$b.'</span></li>';
            echo '</ul></div>';
            echo '<div class="sbd-pf">';
            if($is_active) echo '<button class="sbd-pbtn cur">✓ বর্তমান প্ল্যান</button>';
            else echo '<a href="'.esc_url($url).'" class="sbd-pbtn" style="background:'.$hdr.'">নিন</a>';
            echo '</div></div>';
        }
        echo '</div>';
    }
}
