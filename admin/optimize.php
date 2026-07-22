<?php
/**
 * Optimization & Cache Settings
 * 
 * @package Alokpath\Admin
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
    if (isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
        if (function_exists('clear_page_caches')) {
            clear_page_caches();
            setFlash('success', 'পুরো ওয়েবসাইটের ক্যাশে (Cache) সফলভাবে ক্লিয়ার করা হয়েছে!');
        } else {
            setFlash('error', 'ক্যাশে ক্লিয়ার ফাংশন পাওয়া যায়নি।');
        }
    } else {
        $home_cache = (int)($_POST['home_cache_time'] ?? 300);
        $article_cache = (int)($_POST['article_cache_time'] ?? 3600);
        $category_cache = (int)($_POST['category_cache_time'] ?? 300);
        
        $pairs = [
            'home_cache_time' => $home_cache,
            'article_cache_time' => $article_cache,
            'category_cache_time' => $category_cache,
        ];
        
        foreach ($pairs as $k => $v) {
            if ($setting->get($k) === false) {
                $setting->create($k, $v, 'text', 'Cache duration for ' . $k);
            } else {
                $setting->update($k, $v);
            }
        }
        
        if (function_exists('clear_page_caches')) {
            clear_page_caches();
        }
        
        setFlash('success', 'ক্যাশে টাইম সেটিংস সফলভাবে সেভ করা হয়েছে!');
    }
    
    redirect(ADMIN_URL . '/optimize.php');
}

// Load current cache settings
$home_cache_time = $setting->get('home_cache_time') ?: 300;
$article_cache_time = $setting->get('article_cache_time') ?: 3600;
$category_cache_time = $setting->get('category_cache_time') ?: 300;

$page_title = 'অপ্টিমাইজ ও ক্যাশে';

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">অপ্টিমাইজ ও ক্যাশে</h2>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Cache Clear Box -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 col-span-1">
            <h3 class="text-xl font-bold text-gray-800 mb-2">ক্যাশে ক্লিয়ার করুন</h3>
            <p class="text-gray-500 text-sm mb-6">যদি ওয়েবসাইটে কোনো লেটেস্ট নিউজ, সেটিংস বা প্রোফাইলের পরিবর্তন সাথে সাথে দেখা না যায়, তবে নিচের বাটনে ক্লিক করে ম্যানুয়ালি ওয়েবসাইটের সম্পূর্ণ ক্যাশে মুছে ফেলতে পারেন।</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="w-full bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition shadow-sm flex items-center justify-center">
                    <i class="fas fa-trash-alt mr-2"></i> সমস্ত ক্যাশে মুছুন
                </button>
            </form>
        </div>
        
        <!-- Cache Time Settings Box -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 col-span-1 md:col-span-2">
            <h3 class="text-xl font-bold text-gray-800 mb-6">ক্যাশে টাইম ম্যানেজমেন্ট</h3>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">হোমপেজ ক্যাশে টাইম (সেকেন্ড)</label>
                        <input type="number" name="home_cache_time" value="<?php echo escape($home_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-500 mt-1">ডিফল্ট: 300 (৫ মিনিট)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">ক্যাটাগরি পেজ ক্যাশে টাইম (সেকেন্ড)</label>
                        <input type="number" name="category_cache_time" value="<?php echo escape($category_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-500 mt-1">ডিফল্ট: 300 (৫ মিনিট)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">আর্টিকেল/নিউজ ক্যাশে টাইম (সেকেন্ড)</label>
                        <input type="number" name="article_cache_time" value="<?php echo escape($article_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-500 mt-1">ডিফল্ট: 3600 (১ ঘণ্টা)</p>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-gray-100">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-sm flex items-center">
                        <i class="fas fa-save mr-2"></i> সেভ সেটিংস
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
