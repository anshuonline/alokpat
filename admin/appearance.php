<?php
/**
 * Admin Appearance/Theme Settings
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Only super_admin or admin can change theme
$user = getCurrentUser();
if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin') {
    setFlash('error', 'আপনার এই পেজটি দেখার অনুমতি নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$setting = new Setting();

// Define available themes
$themes = [
    'default' => [
        'name' => 'ডিফল্ট (নীল)',
        'description' => 'পেশাদার এবং বিশ্বস্ত নীল থিম',
        'primary' => '#1a56db',
        'primary_dark' => '#1e40af',
        'primary_light' => '#3b82f6',
    ],
    'ruby' => [
        'name' => 'রুবি (লাল)',
        'description' => 'ব্রেকিং নিউজ এবং এনার্জেটিক লাল থিম',
        'primary' => '#dc2626',
        'primary_dark' => '#991b1b',
        'primary_light' => '#ef4444',
    ],
    'emerald' => [
        'name' => 'পান্না (সবুজ)',
        'description' => 'সতেজ এবং পরিষ্কার সবুজ থিম',
        'primary' => '#059669',
        'primary_dark' => '#065f46',
        'primary_light' => '#10b981',
    ],
    'amber' => [
        'name' => 'অ্যাম্বার (কমলা)',
        'description' => 'উষ্ণ এবং আধুনিক কমলা থিম',
        'primary' => '#d97706',
        'primary_dark' => '#92400e',
        'primary_light' => '#f59e0b',
    ],
    'violet' => [
        'name' => 'ভায়োলেট (বেগুনী)',
        'description' => 'মার্জিত এবং আধুনিক বেগুনী থিম',
        'primary' => '#7c3aed',
        'primary_dark' => '#5b21b6',
        'primary_light' => '#8b5cf6',
    ],
    'teal' => [
        'name' => 'টিল (সবুজাভ নীল)',
        'description' => 'স্নিগ্ধ এবং পেশাদার টিল থিম',
        'primary' => '#0d9488',
        'primary_dark' => '#115e59',
        'primary_light' => '#14b8a6',
    ],
    'rose' => [
        'name' => 'রোজ (গোলাপী)',
        'description' => 'উজ্জ্বল এবং সুন্দর গোলাপী থিম',
        'primary' => '#e11d48',
        'primary_dark' => '#9f1239',
        'primary_light' => '#f43f5e',
    ],
    'fuchsia' => [
        'name' => 'ফুসিয়া (উজ্জ্বল বেগুনী-গোলাপী)',
        'description' => 'আকর্ষণীয় ফুসিয়া থিম',
        'primary' => '#c026d3',
        'primary_dark' => '#86198f',
        'primary_light' => '#d946ef',
    ],
    'indigo' => [
        'name' => 'ইন্ডিগো (গাঢ় নীল)',
        'description' => 'গভীর এবং পেশাদার নীল থিম',
        'primary' => '#4f46e5',
        'primary_dark' => '#3730a3',
        'primary_light' => '#6366f1',
    ],
    'sky' => [
        'name' => 'স্কাই (আকাশী)',
        'description' => 'হালকা এবং পরিষ্কার আকাশী থিম',
        'primary' => '#0284c7',
        'primary_dark' => '#075985',
        'primary_light' => '#0ea5e9',
    ],
    'cyan' => [
        'name' => 'সায়ান (উজ্জ্বল নীল-সবুজ)',
        'description' => 'আধুনিক এবং সতেজ সায়ান থিম',
        'primary' => '#0891b2',
        'primary_dark' => '#155e75',
        'primary_light' => '#06b6d4',
    ],
    'lime' => [
        'name' => 'লাইম (হলদেটে সবুজ)',
        'description' => 'উজ্জ্বল এবং প্রাণবন্ত লাইম থিম',
        'primary' => '#65a30d',
        'primary_dark' => '#3f6212',
        'primary_light' => '#84cc16',
    ],
    'yellow' => [
        'name' => 'ইয়েলো (হলুদ)',
        'description' => 'উষ্ণ এবং উজ্জ্বল হলুদ থিম',
        'primary' => '#ca8a04',
        'primary_dark' => '#854d0e',
        'primary_light' => '#eab308',
    ],
    'orange' => [
        'name' => 'অরেঞ্জ (কমলা)',
        'description' => 'ক্লাসিক কমলা থিম',
        'primary' => '#ea580c',
        'primary_dark' => '#9a3412',
        'primary_light' => '#f97316',
    ],
    'stone' => [
        'name' => 'স্টোন (পাথর রঙ)',
        'description' => 'মার্জিত এবং ক্লাসিক স্টোন থিম',
        'primary' => '#57534e',
        'primary_dark' => '#292524',
        'primary_light' => '#78716c',
    ],
    'slate' => [
        'name' => 'স্লেট (ধূসর নীল)',
        'description' => 'পেশাদার এবং শান্ত স্লেট থিম',
        'primary' => '#475569',
        'primary_dark' => '#1e293b',
        'primary_light' => '#64748b',
    ],
    'neelambari' => [
        'name' => 'নীলাম্বরী (Neelambari)',
        'description' => 'গভীর এবং উজ্জ্বল নীল (Deep Radiant Blue)',
        'primary' => '#1a32d4',
        'primary_dark' => '#1022a8',
        'primary_light' => '#2d4bf3',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $theme_color = trim($_POST['theme_color'] ?? 'default');
    
    if (array_key_exists($theme_color, $themes)) {
        if ($setting->get('theme_color') === false) {
            $success = $setting->create('theme_color', $theme_color, 'text', 'Website Theme Color');
        } else {
            $success = $setting->update('theme_color', $theme_color);
        }
        
        if ($success !== false) {
            setFlash('success', 'থিম সফলভাবে আপডেট করা হয়েছে');
        } else {
            setFlash('error', 'থিম আপডেট করতে সমস্যা হয়েছে');
        }
    } else {
        setFlash('error', 'অবৈধ থিম নির্বাচন');
    }
    
    redirect(ADMIN_URL . '/appearance.php');
}

$current_theme = $setting->get('theme_color');
if (!$current_theme || !array_key_exists($current_theme, $themes)) {
    $current_theme = 'default';
}

$page_title = 'এপিয়ারেন্স (Appearance)';

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">এপিয়ারেন্স (Appearance)</h2>
            <p class="text-sm text-gray-500">আপনার ওয়েবসাইটের থিম এবং রঙ পরিবর্তন করুন</p>
        </div>
    </div>

    <form action="" method="POST" class="bg-white rounded-xl shadow-md p-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">থিম নির্বাচন করুন</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <?php foreach ($themes as $key => $theme): ?>
                <label class="relative cursor-pointer">
                    <input type="radio" name="theme_color" value="<?php echo $key; ?>" class="peer sr-only" <?php echo $current_theme === $key ? 'checked' : ''; ?>>
                    
                    <div class="rounded-lg border-2 <?php echo $current_theme === $key ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'; ?> p-4 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-sm flex items-start space-x-4">
                        
                        <!-- Color Palette Preview -->
                        <div class="flex-shrink-0 flex flex-col space-y-1 w-12 h-12 rounded overflow-hidden shadow-sm border border-gray-200">
                            <div class="flex-1" style="background-color: <?php echo $theme['primary']; ?>;"></div>
                            <div class="flex-1" style="background-color: <?php echo $theme['primary_dark']; ?>;"></div>
                            <div class="flex-1" style="background-color: <?php echo $theme['primary_light']; ?>;"></div>
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900"><?php echo $theme['name']; ?></h4>
                            <p class="text-sm text-gray-500 mt-1"><?php echo $theme['description']; ?></p>
                        </div>
                        
                        <!-- Checkmark -->
                        <div class="absolute top-4 right-4 text-blue-500 opacity-0 peer-checked:opacity-100 transition-opacity">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="border-t pt-6 flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium shadow-sm flex items-center">
                <i class="fas fa-save mr-2"></i> পরিবর্তন সেভ করুন
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require 'layouts/admin.php';
