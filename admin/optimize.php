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
            setFlash('success', 'পুরো ওয়েবসাইটের ক্যাশে (Cache) সফলভাবে ক্লিয়ার করা হয়েছে! (হোমপেজ + ক্যাটাগরি + মেনু)');
        } else {
            setFlash('error', 'ক্যাশে ক্লিয়ার ফাংশন পাওয়া যায়নি।');
        }
    } else {
        $home_cache     = (int)($_POST['home_cache_time']     ?? 300);
        $article_cache  = (int)($_POST['article_cache_time']  ?? 3600);
        $category_cache = (int)($_POST['category_cache_time'] ?? 300);
        $menu_cache     = (int)($_POST['menu_cache_time']     ?? 600);
        
        $pairs = [
            'home_cache_time'     => $home_cache,
            'article_cache_time'  => $article_cache,
            'category_cache_time' => $category_cache,
            'menu_cache_time'     => $menu_cache,
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
        
        setFlash('success', 'ক্যাশে টাইম সেটিংস সফলভাবে সেভ করা হয়েছে!');
    }
    
    redirect(ADMIN_URL . '/optimize.php');
}

// Load current cache settings
$home_cache_time     = $setting->get('home_cache_time')     ?: 300;
$article_cache_time  = $setting->get('article_cache_time')  ?: 3600;
$category_cache_time = $setting->get('category_cache_time') ?: 300;
$menu_cache_time     = $setting->get('menu_cache_time')     ?: 600;

// Cache file stats
$cache_base = __DIR__ . '/../cache';
$stats = [
    'homepage'   => file_exists($cache_base . '/index.html') ? 1 : 0,
    'categories' => (is_dir($cache_base . '/categories') ? count(glob($cache_base . '/categories/*.html') ?: []) : 0),
    'menus'      => (is_dir($cache_base . '/menus')      ? count(glob($cache_base . '/menus/*.json')      ?: []) : 0),
];
$total_cached = array_sum($stats);

$page_title = 'অপ্টিমাইজ ও ক্যাশে';

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">অপ্টিমাইজ ও ক্যাশে</h2>
    </div>

    <!-- Cache Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_cached; ?></p>
                <p class="text-xs text-gray-500">মোট ক্যাশড ফাইল</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                <i class="fas fa-home"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['homepage']; ?></p>
                <p class="text-xs text-gray-500">হোমপেজ ক্যাশে</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                <i class="fas fa-th-large"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['categories']; ?></p>
                <p class="text-xs text-gray-500">ক্যাটাগরি ক্যাশে</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                <i class="fas fa-bars"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['menus']; ?></p>
                <p class="text-xs text-gray-500">মেনু ক্যাশে</p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Cache Clear Box -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 col-span-1">
            <h3 class="text-xl font-bold text-gray-800 mb-2">ক্যাশে ক্লিয়ার করুন</h3>
            <p class="text-gray-500 text-sm mb-6">যদি ওয়েবসাইটে কোনো লেটেস্ট নিউজ, সেটিংস বা মেনু পরিবর্তন সাথে সাথে দেখা না যায়, তবে নিচের বাটনে ক্লিক করে সম্পূর্ণ ক্যাশে মুছে ফেলুন।</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="w-full bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition shadow-sm flex items-center justify-center">
                    <i class="fas fa-trash-alt mr-2"></i> সমস্ত ক্যাশে মুছুন
                </button>
            </form>

            <!-- Quick Tips -->
            <div class="mt-6 bg-blue-50 border border-blue-100 rounded-lg p-4">
                <p class="text-xs font-semibold text-blue-700 mb-2"><i class="fas fa-lightbulb mr-1"></i> কখন ক্যাশে ক্লিয়ার করবেন?</p>
                <ul class="text-xs text-blue-600 space-y-1 list-disc list-inside">
                    <li>নতুন নিউজ পাবলিশ করার পর</li>
                    <li>মেনু পরিবর্তনের পর</li>
                    <li>সাইটের সেটিংস বদলানোর পর</li>
                </ul>
            </div>
        </div>
        
        <!-- Cache Time Settings Box -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 col-span-1 md:col-span-2">
            <h3 class="text-xl font-bold text-gray-800 mb-1">ক্যাশে টাইম ম্যানেজমেন্ট</h3>
            <p class="text-sm text-gray-500 mb-5">বেশি সময় = বেশি স্পিড। কিন্তু নতুন কন্টেন্ট দেরিতে দেখাবে।</p>
            
            <form method="POST" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <i class="fas fa-home text-green-500 mr-1"></i> হোমপেজ ক্যাশে টাইম (সেকেন্ড)
                        </label>
                        <input type="number" name="home_cache_time" value="<?php echo escape($home_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-400 mt-1">ডিফল্ট: 300 (৫ মিনিট) &bull; সুপারিশ: 1800 (৩০ মিনিট)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <i class="fas fa-th-large text-purple-500 mr-1"></i> ক্যাটাগরি পেজ ক্যাশে (সেকেন্ড)
                        </label>
                        <input type="number" name="category_cache_time" value="<?php echo escape($category_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-400 mt-1">ডিফল্ট: 300 (৫ মিনিট) &bull; সুপারিশ: 1800 (৩০ মিনিট)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <i class="fas fa-newspaper text-blue-500 mr-1"></i> আর্টিকেল ক্যাশে টাইম (সেকেন্ড)
                        </label>
                        <input type="number" name="article_cache_time" value="<?php echo escape($article_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-400 mt-1">ডিফল্ট: 3600 (১ ঘণ্টা) &bull; সুপারিশ: 3600</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <i class="fas fa-bars text-orange-500 mr-1"></i> মেনু ক্যাশে টাইম (সেকেন্ড)
                        </label>
                        <input type="number" name="menu_cache_time" value="<?php echo escape($menu_cache_time); ?>" 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required min="0">
                        <p class="text-xs text-gray-400 mt-1">ডিফল্ট: 600 (১০ মিনিট) &bull; মেনু সেভ করলে অটো ক্লিয়ার হয়</p>
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
