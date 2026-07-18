<?php
/**
 * Admin Dashboard
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Get statistics
$user = new User();
$post = new Post();
$category = new Category();
$media = new Media();

$total_posts = $post->getCount();
$published_posts = $post->getCount('published');
$draft_posts = $post->getCount('draft');
$total_categories = count($category->getAll());
$total_media = $media->getCount();

// Get recent posts
$recent_posts = $post->getPublished(10);

// Get breaking news
$breaking_news = $post->getBreakingNews(5);

$page_title = 'ড্যাশবোর্ড';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">ড্যাশবোর্ড</h2>
        <div class="text-sm text-gray-500">
            <?php echo formatDateBengali(date('Y-m-d H:i:s'), 'l, d F Y'); ?>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Posts -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">মোট সংবাদ</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo formatNumberBengali($total_posts); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-newspaper text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Published Posts -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">প্রকাশিত</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo formatNumberBengali($published_posts); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Draft Posts -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">খসড়া</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo formatNumberBengali($draft_posts); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-yellow-100 flex items-center justify-center">
                    <i class="fas fa-file-alt text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Categories -->
        <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">ক্যাটাগরি</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo formatNumberBengali($total_categories); ?></p>
                </div>
                <div class="h-14 w-14 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-folder text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">দ্রুত কাজ</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="<?php echo ADMIN_URL; ?>/post-create.php" 
               class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition">
                <i class="fas fa-plus-circle text-blue-600 text-2xl mr-3"></i>
                <span class="font-medium">নতুন সংবাদ লিখুন</span>
            </a>
            <a href="<?php echo ADMIN_URL; ?>/posts.php" 
               class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition">
                <i class="fas fa-list text-green-600 text-2xl mr-3"></i>
                <span class="font-medium">সকল সংবাদ দেখুন</span>
            </a>
            <a href="<?php echo ADMIN_URL; ?>/media.php" 
               class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition">
                <i class="fas fa-upload text-purple-600 text-2xl mr-3"></i>
                <span class="font-medium">মিডিয়া আপলোড</span>
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Posts -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">সাম্প্রতিক সংবাদ</h3>
                <a href="<?php echo ADMIN_URL; ?>/posts.php" class="text-sm text-blue-600 hover:underline">সব দেখুন</a>
            </div>
            
            <div class="space-y-4">
                <?php if (empty($recent_posts)): ?>
                    <p class="text-center text-gray-500 py-8">কোনো সংবাদ পাওয়া যায়নি</p>
                <?php else: ?>
                    <?php foreach ($recent_posts as $index => $post_item): ?>
                        <div class="flex items-start space-x-4 p-4 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                <?php echo formatNumberBengali($index + 1); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800 truncate">
                                    <?php echo escape($post_item['title']); ?>
                                </h4>
                                <div class="flex items-center space-x-3 mt-1 text-sm text-gray-500">
                                    <span>
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo escape($post_item['author_name']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo timeAgoBengali($post_item['published_at'] ?? $post_item['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $post_item['status'] == 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo $post_item['status'] == 'published' ? 'প্রকাশিত' : 'খসড়া'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Breaking News -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">ব্রেকিং নিউজ</h3>
                <a href="<?php echo ADMIN_URL; ?>/posts.php?filter=breaking" class="text-sm text-blue-600 hover:underline">সব দেখুন</a>
            </div>
            
            <div class="space-y-4">
                <?php if (empty($breaking_news)): ?>
                    <p class="text-center text-gray-500 py-8">কোনো ব্রেকিং নিউজ নেই</p>
                <?php else: ?>
                    <?php foreach ($breaking_news as $news): ?>
                        <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-red-50 transition border-l-4 border-red-500">
                            <div class="flex-shrink-0">
                                <span class="inline-block px-2 py-1 bg-red-600 text-white text-xs font-bold rounded">
                                    <i class="fas fa-bolt mr-1"></i>
                                    ব্রেকিং
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800">
                                    <?php echo escape($news['title']); ?>
                                </h4>
                                <p class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-eye mr-1"></i>
                                    <?php echo formatNumberBengali($news['view_count']); ?> ভিউ
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
</div>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
