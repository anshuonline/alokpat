<?php
/**
 * Site Settings Page
 */

require_once '../config/config.php';
requireAuth();

// Permission check
$allowed_roles = ['super_admin', 'admin'];
if (!in_array(getCurrentUser()['role'], $allowed_roles)) {
    setFlash('error', 'আপনার এই পৃষ্ঠা দেখার অনুমতি নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$setting = new Setting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = sanitize($_POST['site_name'] ?? '');
    $site_tagline = sanitize($_POST['site_tagline'] ?? '');
    $site_logo = sanitize($_POST['site_logo'] ?? '');
    $footer_logo = sanitize($_POST['footer_logo'] ?? '');
    $header_html = trim($_POST['header_html'] ?? '');
    $custom_login_slug = sanitize($_POST['custom_login_slug'] ?? '');
    $seo_title_format = trim($_POST['seo_title_format'] ?? '');
    
    // Homepage SEO
    $home_seo_title = trim($_POST['home_seo_title'] ?? '');
    $home_seo_description = trim($_POST['home_seo_description'] ?? '');
    $home_seo_keywords = trim($_POST['home_seo_keywords'] ?? '');
    
    // Tracking & Verification
    $google_analytics_code = trim($_POST['google_analytics_code'] ?? '');
    $google_search_console = trim($_POST['google_search_console'] ?? '');

    // Persist settings (create if missing)
    $pairs = [
        'site_name' => $site_name,
        'site_tagline' => $site_tagline,
        'site_logo' => $site_logo,
        'footer_logo' => $footer_logo,
        'site_header_html' => $header_html,
        'seo_title_format' => $seo_title_format,
        'home_seo_title' => $home_seo_title,
        'home_seo_description' => $home_seo_description,
        'home_seo_keywords' => $home_seo_keywords,
        'google_analytics_code' => $google_analytics_code,
        'google_search_console' => $google_search_console,
        'custom_login_slug' => $custom_login_slug,
    ];

    foreach ($pairs as $k => $v) {
        if ($setting->get($k) === false) {
            $setting->create($k, $v, 'text', 'Site setting ' . $k);
        } else {
            $setting->update($k, $v);
        }
    }

    setFlash('success', 'সাইট সেটিংস সফলভাবে আপডেট হয়েছে');
    redirect(ADMIN_URL . '/settings.php');
}

// Load current settings
$site_name = $setting->get('site_name') ?: 'আলোকপাত';
$site_tagline = $setting->get('site_tagline') ?: '';
$site_logo = $setting->get('site_logo') ?: '';
$footer_logo = $setting->get('footer_logo') ?: '';
$site_header_html = $setting->get('site_header_html') ?: '';
$seo_title_format = $setting->get('seo_title_format') ?: '%pagetitle% - %sitename%';

$home_seo_title = $setting->get('home_seo_title') ?: '';
$home_seo_description = $setting->get('home_seo_description') ?: '';
$home_seo_keywords = $setting->get('home_seo_keywords') ?: '';

$google_analytics_code = $setting->get('google_analytics_code') ?: '';
$google_search_console = $setting->get('google_search_console') ?: '';

$custom_login_slug = $setting->get('custom_login_slug') ?: '';

$page_title = 'সাইট সেটিংস';

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">সাইট সেটিংস</h2>
            <p class="text-sm text-gray-500 mt-1">সাইটের হেডার, ফেভিকন, ফুটার ইত্যাদি কনফিগার করুন</p>
        </div>
        <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg">ড্যাশবোর্ডে ফিরুন</a>
    </div>

    <form method="POST" class="bg-white rounded-xl shadow-md p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">সাইট নাম</label>
            <input type="text" name="site_name" value="<?php echo escape($site_name); ?>" class="w-full px-4 py-3 border rounded" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ট্যাগলাইন</label>
            <input type="text" name="site_tagline" value="<?php echo escape($site_tagline); ?>" class="w-full px-4 py-3 border rounded" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">SEO Title Format</label>
            <input type="text" name="seo_title_format" value="<?php echo escape($seo_title_format); ?>" class="w-full px-4 py-3 border rounded" />
            <p class="text-xs text-gray-500 mt-1">ব্যবহারযোগ্য ভেরিয়েবল: %pagetitle%, %sitename%, %sitetagline% (উদাহরণ: %pagetitle% | %sitename%)</p>
        </div>
        
        <hr class="my-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">হোমপেজ এসইও (Homepage SEO)</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Homepage SEO Title</label>
            <input type="text" name="home_seo_title" value="<?php echo escape($home_seo_title); ?>" class="w-full px-4 py-3 border rounded border-gray-300" placeholder="e.g. Alokpath - Breaking Bengali News" />
            <p class="text-xs text-gray-500 mt-1">হোমপেজের জন্য বিশেষ টাইটেল (খালি রাখলে ডিফল্ট 'প্রচ্ছদ' ব্যবহার হবে)</p>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Homepage SEO Description</label>
            <textarea name="home_seo_description" class="w-full px-4 py-3 border rounded border-gray-300" rows="2" placeholder="Search engine description for homepage..."><?php echo escape($home_seo_description); ?></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Homepage SEO Keywords</label>
            <input type="text" name="home_seo_keywords" value="<?php echo escape($home_seo_keywords); ?>" class="w-full px-4 py-3 border rounded border-gray-300" placeholder="news, bangla, updates" />
        </div>
        
        <hr class="my-6">
        <h3 class="text-xl font-bold text-gray-800 mb-2">ট্র্যাকিং এবং ভেরিফিকেশন (Tracking & Verification)</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Google Search Console Verification HTML Tag</label>
            <input type="text" name="google_search_console" value="<?php echo escape($google_search_console); ?>" class="w-full px-4 py-3 border rounded border-gray-300" placeholder="<meta name='google-site-verification' content='...' />" />
            <p class="text-xs text-gray-500 mt-1">Search Console থেকে পাওয়া সম্পূর্ণ মেটা ট্যাগ এখানে দিন।</p>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Google Analytics (GA4) Tracking Code</label>
            <textarea name="google_analytics_code" class="w-full px-4 py-3 border rounded border-gray-300" rows="5" placeholder="<!-- Google tag (gtag.js) -->..."><?php echo escape($google_analytics_code); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Google Analytics থেকে পাওয়া সম্পূর্ণ &lt;script&gt; ট্যাগটি এখানে পেস্ট করুন।</p>
        </div>
        
        <hr class="my-6">

        <div>
            <label class="block text-sm font-medium text-gray-700">সাইট লোগো URL</label>
            <div class="flex space-x-2">
                <input type="text" name="site_logo" id="site_logo" value="<?php echo escape($site_logo); ?>" class="flex-1 px-4 py-3 border rounded" />
                <button type="button" onclick="copyToClipboard(document.getElementById('site_logo').value)" class="px-4 py-2 bg-gray-200 rounded">কপি</button>
            </div>
            <p class="text-xs text-gray-500 mt-1">লগো আপলোড করতে মিডিয়া ব্যবহার করুন এবং URL এ পেস্ট করুন</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ফুটার লোগো URL</label>
            <div class="mt-1 flex rounded-md shadow-sm">
                <input type="text" name="footer_logo" id="footer_logo" value="<?php echo escape($footer_logo); ?>" class="flex-1 min-w-0 block w-full px-4 py-3 rounded-none rounded-l-md border border-gray-300">
                <button type="button" onclick="window.open('media.php', '_blank', 'width=800,height=600')" class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-700 hover:bg-gray-100">
                    ব্রাউজ
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">লোগো না থাকলে সাইটের নাম টেক্সট হিসেবে দেখাবে</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">কাস্টম হেডার HTML (ঐচ্ছিক)</label>
            <textarea name="header_html" class="w-full px-4 py-3 border rounded" rows="4"><?php echo escape($site_header_html); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">যদি আপনি হেডারে বিশেষ আইটেম যোগ করতে চান (কমার্শিয়াল কোড সতর্কতার সঙ্গে যুক্ত করুন)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">কাস্টম লগইন URL স্লাগ (ঐচ্ছিক)</label>
            <div class="flex items-center space-x-2 mt-1">
                <span class="text-gray-500"><?php echo SITE_URL; ?>/admin/</span>
                <input type="text" name="custom_login_slug" value="<?php echo escape($custom_login_slug); ?>" placeholder="administrator-login" class="flex-1 px-4 py-3 border rounded border-gray-300" />
            </div>
            <p class="text-xs text-red-500 mt-1 font-bold">সতর্কতা: এটি পরিবর্তন করলে আপনাকে এই নতুন URL ব্যবহার করে লগইন করতে হবে (যেমন /admin/আপনার-স্লাগ)। ভুলে গেলে লগইন করা যাবে না!</p>
        </div>

        <div class="flex items-center justify-between">
            <button class="px-6 py-3 bg-indigo-600 text-white rounded">সেভ করুন</button>
            <a href="<?php echo ADMIN_URL; ?>/settings.php" class="px-4 py-2 bg-gray-200 rounded">রিসেট</a>
        </div>
    </form>
</div>

<script>
function copyToClipboard(v) {
    navigator.clipboard.writeText(v).then(function(){ alert('Copied'); });
}
</script>

<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
