<?php
/**
 * Admin Notification Center
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();
requirePermission('manage_settings');

$setting = new Setting();
$db = (new Database())->getConnection();

// Fetch settings
$fcm_popup_title = $setting->get('fcm_popup_title') ?: 'আমাদের নোটিফিকেশন সাবস্ক্রাইব করুন';
$fcm_popup_desc = $setting->get('fcm_popup_desc') ?: 'সর্বশেষ খবরের আপডেট পেতে আমাদের পুশ নোটিফিকেশন চালু করুন।';
$fcm_btn_subscribe = $setting->get('fcm_btn_subscribe') ?: 'সাবস্ক্রাইব করুন';
$fcm_btn_later = $setting->get('fcm_btn_later') ?: 'পরে';
$fcm_auto_send_on_publish = $setting->get('fcm_auto_send_on_publish') ?: '0';

$fcm_api_key = $setting->get('fcm_api_key') ?: '';
$fcm_project_id = $setting->get('fcm_project_id') ?: '';
$fcm_messaging_sender_id = $setting->get('fcm_messaging_sender_id') ?: '';
$fcm_app_id = $setting->get('fcm_app_id') ?: '';
$fcm_vapid_key = $setting->get('fcm_vapid_key') ?: '';
$fcm_service_account_json = $setting->get('fcm_service_account_json') ?: '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    requireCSRF();
    
    // Save popup settings
    $setting->update('fcm_popup_title', trim($_POST['fcm_popup_title']));
    $setting->update('fcm_popup_desc', trim($_POST['fcm_popup_desc']));
    $setting->update('fcm_btn_subscribe', trim($_POST['fcm_btn_subscribe']));
    $setting->update('fcm_btn_later', trim($_POST['fcm_btn_later']));
    $setting->update('fcm_auto_send_on_publish', isset($_POST['fcm_auto_send_on_publish']) ? '1' : '0');
    
    // Save API settings
    $setting->update('fcm_api_key', trim($_POST['fcm_api_key']));
    $setting->update('fcm_project_id', trim($_POST['fcm_project_id']));
    $setting->update('fcm_messaging_sender_id', trim($_POST['fcm_messaging_sender_id']));
    $setting->update('fcm_app_id', trim($_POST['fcm_app_id']));
    $setting->update('fcm_vapid_key', trim($_POST['fcm_vapid_key']));
    $setting->update('fcm_service_account_json', trim($_POST['fcm_service_account_json']));
    
    setFlash('success', 'নোটিফিকেশন সেটিংস সফলভাবে আপডেট করা হয়েছে।');
    redirect(ADMIN_URL . '/notifications.php');
}

// Handle Manual Push
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_push') {
    requireCSRF();
    $post_id = (int)$_POST['post_id'];
    
    if ($post_id > 0) {
        // We will call the push logic here
        require_once 'api/send_push.php';
        $result = sendFirebasePushNotification($post_id);
        
        if ($result['success']) {
            setFlash('success', 'পুশ নোটিফিকেশন সফলভাবে পাঠানো হয়েছে! (Success: ' . $result['success_count'] . ', Failed: ' . $result['failure_count'] . ')');
        } else {
            setFlash('error', 'নোটিফিকেশন পাঠাতে সমস্যা হয়েছে: ' . $result['error']);
        }
    } else {
        setFlash('error', 'অনুগ্রহ করে একটি পোস্ট নির্বাচন করুন।');
    }
    
    redirect(ADMIN_URL . '/notifications.php');
}

// Get recent published posts for the dropdown
$posts = [];
try {
    $stmt = $db->query("SELECT id, title FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 20");
    $posts = $stmt->fetchAll();
} catch(PDOException $e) {}

// Get total subscribers
$total_subscribers = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) FROM fcm_subscribers");
    $total_subscribers = $stmt->fetchColumn();
} catch(PDOException $e) {}

$page_title = 'নোটিফিকেশন সেন্টার';
ob_start();
?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">নোটিফিকেশন সেন্টার</h2>
            <p class="text-gray-600 mt-1">ফায়ারবেস পুশ নোটিফিকেশন পরিচালনা করুন</p>
        </div>
        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg font-medium flex items-center shadow-sm border border-blue-100">
            <i class="fas fa-users mr-2"></i> মোট সাবস্ক্রাইবার: <?php echo number_format($total_subscribers); ?>
        </div>
    </div>

    <?php displayFlash(); ?>

    <!-- Tabs Component from uiverse inspiration (sleek modern design) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ tab: 'send' }">
        <div class="flex border-b border-gray-200">
            <button @click="tab = 'send'" :class="{ 'border-blue-500 text-blue-600': tab === 'send', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'send' }" class="flex-1 py-4 px-6 text-center border-b-2 font-medium transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> নোটিফিকেশন পাঠান
            </button>
            <button @click="tab = 'popup'" :class="{ 'border-blue-500 text-blue-600': tab === 'popup', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'popup' }" class="flex-1 py-4 px-6 text-center border-b-2 font-medium transition-colors">
                <i class="fas fa-window-restore mr-2"></i> পপআপ সেটিংস
            </button>
            <button @click="tab = 'api'" :class="{ 'border-blue-500 text-blue-600': tab === 'api', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'api' }" class="flex-1 py-4 px-6 text-center border-b-2 font-medium transition-colors">
                <i class="fas fa-cogs mr-2"></i> ফায়ারবেস এপিআই (API)
            </button>
        </div>

        <div class="p-6">
            <!-- Tab 1: Send Push -->
            <div x-show="tab === 'send'" x-transition.opacity>
                <form action="" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="send_push">
                    
                    <div class="bg-blue-50/50 p-6 rounded-lg border border-blue-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">ম্যানুয়াল পুশ নোটিফিকেশন</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">কোন পোস্টটির নোটিফিকেশন পাঠাবেন?</label>
                                <select name="post_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2.5 bg-white">
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <?php foreach($posts as $post): ?>
                                        <option value="<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" onclick="return confirm('আপনি কি নিশ্চিত যে আপনি সকল সাবস্ক্রাইবারকে এই নোটিফিকেশন পাঠাতে চান?')" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-paper-plane mr-2 mt-1"></i> সেন্ড নোটিফিকেশন (Send Now)
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Settings (Popup & API) -->
            <div x-show="tab === 'popup' || tab === 'api'" style="display: none;">
                <form action="" method="POST" class="space-y-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- Popup Settings Content -->
                    <div x-show="tab === 'popup'" class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2">পপআপ উইন্ডো কাস্টমাইজেশন</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Popup Title (টাইটেল)</label>
                                    <input type="text" name="fcm_popup_title" value="<?php echo escape($fcm_popup_title); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Popup Message (বার্তা)</label>
                                    <textarea name="fcm_popup_desc" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"><?php echo escape($fcm_popup_desc); ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Subscribe Button</label>
                                        <input type="text" name="fcm_btn_subscribe" value="<?php echo escape($fcm_btn_subscribe); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Later Button</label>
                                        <input type="text" name="fcm_btn_later" value="<?php echo escape($fcm_btn_later); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Live Preview -->
                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 flex items-center justify-center">
                                <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full border border-gray-100 transform scale-90">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 mb-4 mx-auto">
                                        <i class="fas fa-bell text-xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-900 text-center mb-2"><?php echo escape($fcm_popup_title); ?></h4>
                                    <p class="text-sm text-gray-500 text-center mb-6"><?php echo escape($fcm_popup_desc); ?></p>
                                    <div class="flex space-x-3">
                                        <button type="button" class="flex-1 py-2 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"><?php echo escape($fcm_btn_later); ?></button>
                                        <button type="button" class="flex-1 py-2 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600"><?php echo escape($fcm_btn_subscribe); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="fcm_auto_send_on_publish" value="1" <?php echo $fcm_auto_send_on_publish ? 'checked' : ''; ?> class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <span class="text-gray-800 font-medium">অটোমেটিক নোটিফিকেশন (Auto-send when a new post is published)</span>
                            </label>
                            <p class="text-sm text-gray-500 ml-8 mt-1">নতুন কোনো পোস্ট পাবলিশ হলে তা স্বয়ংক্রিয়ভাবে সব সাবস্ক্রাইবারের কাছে চলে যাবে।</p>
                        </div>
                    </div>

                    <!-- API Settings Content -->
                    <div x-show="tab === 'api'" class="space-y-6">
                        <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg text-sm border border-yellow-200">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>সতর্কতা:</strong> এই ফিল্ডগুলো ঠিকভাবে না দিলে নোটিফিকেশন কাজ করবে না। আপনার Firebase Console থেকে এই ডেটাগুলো সংগ্রহ করুন।
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                <input type="text" name="fcm_api_key" value="<?php echo escape($fcm_api_key); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Project ID</label>
                                <input type="text" name="fcm_project_id" value="<?php echo escape($fcm_project_id); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Messaging Sender ID</label>
                                <input type="text" name="fcm_messaging_sender_id" value="<?php echo escape($fcm_messaging_sender_id); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">App ID</label>
                                <input type="text" name="fcm_app_id" value="<?php echo escape($fcm_app_id); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">VAPID Public Key (Web Push Certificate)</label>
                                <input type="text" name="fcm_vapid_key" value="<?php echo escape($fcm_vapid_key); ?>" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Service Account JSON (For Backend Authentication)</label>
                                <textarea name="fcm_service_account_json" rows="6" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border font-mono text-sm" placeholder='{"type": "service_account", "project_id": "...", "private_key": "..."}'><?php echo escape($fcm_service_account_json); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            সেটিংস সেভ করুন (Save Settings)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layouts/admin.php';
?>
