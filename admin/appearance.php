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
