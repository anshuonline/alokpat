<?php
/**
 * SEO Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

requirePermission('manage_seo');
// Check if user is SEO Manager or higher
$allowed_roles = ['super_admin', 'admin', 'seo_manager'];
if (!in_array(getCurrentUser()['role'], $allowed_roles)) {
    setFlash('error', 'আপনার এই পৃষ্ঠা দেখার অনুমতি নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$post = new Post();
$category = new Category();
$tag_model = new Tag();
$setting = new Setting();

// Get SEO statistics
$total_posts = $post->getCount();
$posts_with_seo = 0; // You can add logic to count posts with SEO data

$page_title = 'এসইও ব্যবস্থাপনা';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-search text-green-600 mr-2"></i>
            এসইও (SEO) ব্যবস্থাপনা
        </h2>
    </div>
    
    <!-- SEO Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">মোট পোস্ট</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo formatNumberBengali($total_posts); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-newspaper text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">ক্যাটাগরি</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo formatNumberBengali(count($category->getAll())); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-folder text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">ট্যাগ</p>
                    <p class="text-3xl font-bold text-orange-600"><?php echo formatNumberBengali(count($tag_model->getAll())); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-tags text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">SEO স্কোর</p>
                    <p class="text-3xl font-bold text-green-600">85%</p>
                </div>
                <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Quick SEO Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Posts SEO -->
        <a href="<?php echo ADMIN_URL; ?>/posts.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-2 border-transparent hover:border-blue-500">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-edit text-blue-600 text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">পোস্ট এসইও</h3>
                    <p class="text-sm text-gray-500 mt-1">পোস্টের মেটা ট্যাগ সম্পাদনা</p>
                </div>
            </div>
        </a>
        
        <!-- Categories SEO -->
        <a href="<?php echo ADMIN_URL; ?>/categories.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-2 border-transparent hover:border-purple-500">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-folder text-purple-600 text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">ক্যাটাগরি এসইও</h3>
                    <p class="text-sm text-gray-500 mt-1">ক্যাটাগরি মেটা সম্পাদনা</p>
                </div>
            </div>
        </a>
        
        <!-- Tags SEO -->
        <a href="<?php echo ADMIN_URL; ?>/tags.php" class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-2 border-transparent hover:border-orange-500">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-tags text-orange-600 text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">ট্যাগ এসইও</h3>
                    <p class="text-sm text-gray-500 mt-1">ট্যাগ মেটা সম্পাদনা</p>
                </div>
            </div>
        </a>
        
        <!-- Sitemap -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-2 border-transparent hover:border-green-500 flex flex-col justify-between">
            <div class="flex items-center space-x-4 mb-4">
                <div class="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-sitemap text-green-600 text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">XML সাইটম্যাপ</h3>
                    <p class="text-sm text-gray-500 mt-1">সার্চ ইঞ্জিনের জন্য ম্যাপ</p>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>/sitemap.php" target="_blank" class="w-full text-center py-2 px-4 bg-green-50 text-green-700 hover:bg-green-600 hover:text-white rounded-lg transition-colors font-medium text-sm">
                সাইটম্যাপ দেখুন <i class="fas fa-external-link-alt ml-1 text-xs"></i>
            </a>
        </div>
        
    </div>
    
    <!-- SEO Settings -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-cog text-blue-600 mr-2"></i>
            সাধারণ এসইও সেটিংস
        </h3>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        সাইটের নাম
                    </label>
                    <input type="text" 
                           value="<?php echo escape($setting->get('site_name')); ?>"
                           disabled
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        সাইটের ট্যাগলাইন
                    </label>
                    <input type="text" 
                           value="<?php echo escape($setting->get('site_tagline')); ?>"
                           disabled
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Google Analytics ID
                </label>
                <input type="text" 
                       value="<?php echo escape($setting->get('google_analytics_id')); ?>"
                       disabled
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Settings প্যানেলে গিয়ে এডিট করুন</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Facebook Pixel ID
                </label>
                <input type="text" 
                       value="<?php echo escape($setting->get('facebook_pixel_id')); ?>"
                       disabled
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50">
            </div>
        </div>
    </div>
    
    <!-- SEO Tips -->
    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl shadow-md p-6 border border-green-200">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
            এসইও টিপস
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Title Tag</h4>
                <p class="text-sm text-gray-600">৫০-৬০ ক্যারেক্টারের মধ্যে SEO title লিখুন</p>
            </div>
            
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Meta Description</h4>
                <p class="text-sm text-gray-600">১৫০-১৬০ ক্যারেক্টারের মধ্যে description লিখুন</p>
            </div>
            
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Focus Keywords</h4>
                <p class="text-sm text-gray-600">৩-৫টি relevant keywords ব্যবহার করুন</p>
            </div>
            
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Featured Image</h4>
                <p class="text-sm text-gray-600">সব পোস্টে featured image যুক্ত করুন</p>
            </div>
            
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Open Graph Tags</h4>
                <p class="text-sm text-gray-600">Featured Image নিজে থেকেই OG Image হিসেবে কাজ করবে</p>
            </div>
            
            <div class="bg-white rounded-lg p-4">
                <h4 class="font-bold text-green-700 mb-2">✓ Alt Text</h4>
                <p class="text-sm text-gray-600">সব image এ alt text যুক্ত করুন</p>
            </div>
        </div>
    </div>
    
    <!-- Recent Posts SEO Status -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-list text-blue-600 mr-2"></i>
                সাম্প্রতিক পোস্টের এসইও স্ট্যাটাস
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">শিরোনাম</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">SEO Title</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">Meta Description</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">Keywords</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">OG Image</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php
                    $recent_posts = $post->getPublished(10);
                    foreach ($recent_posts as $post_item):
                        $has_seo_title = !empty($post_item['seo_title']);
                        $has_seo_desc = !empty($post_item['seo_description']);
                        $has_keywords = !empty($post_item['seo_keywords']);
                        $has_og_image = (!empty($post_item['meta_og_image']) || !empty($post_item['featured_image']));
                    ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-800">
                                    <?php echo escape($post_item['title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($has_seo_title): ?>
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>✓</span>
                                <?php else: ?>
                                    <span class="text-yellow-600"><i class="fas fa-exclamation-circle mr-1"></i>×</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($has_seo_desc): ?>
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>✓</span>
                                <?php else: ?>
                                    <span class="text-yellow-600"><i class="fas fa-exclamation-circle mr-1"></i>×</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($has_keywords): ?>
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>✓</span>
                                <?php else: ?>
                                    <span class="text-yellow-600"><i class="fas fa-exclamation-circle mr-1"></i>×</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($has_og_image): ?>
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>✓</span>
                                <?php else: ?>
                                    <span class="text-yellow-600"><i class="fas fa-exclamation-circle mr-1"></i>×</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
