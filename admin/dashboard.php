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
    <div class="flex items-center justify-between border-b-2 border-black pb-4 mb-6">
        <h2 class="text-3xl font-black text-black uppercase tracking-widest">ড্যাশবোর্ড</h2>
        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest">
            <i class="far fa-calendar-alt mr-1 text-black"></i>
            <?php echo formatDateBengali(date('Y-m-d H:i:s'), 'l, d F Y'); ?>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Posts -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:shadow-[0_8px_30px_rgba(59,130,246,0.4)] hover:-translate-y-1 transition-all duration-300 group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-blue-100 mb-1 font-medium uppercase tracking-wider text-sm">মোট সংবাদ</p>
                    <p class="text-4xl font-black drop-shadow-sm"><?php echo formatNumberBengali($total_posts); ?></p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-newspaper text-white text-3xl drop-shadow-md"></i>
                </div>
            </div>
        </div>
        
        <!-- Published Posts -->
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl p-6 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:shadow-[0_8px_30px_rgba(16,185,129,0.4)] hover:-translate-y-1 transition-all duration-300 group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-emerald-50 mb-1 font-medium uppercase tracking-wider text-sm">প্রকাশিত</p>
                    <p class="text-4xl font-black drop-shadow-sm"><?php echo formatNumberBengali($published_posts); ?></p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-check-circle text-white text-3xl drop-shadow-md"></i>
                </div>
            </div>
        </div>
        
        <!-- Draft Posts -->
        <div class="relative overflow-hidden bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-6 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:shadow-[0_8px_30px_rgba(245,158,11,0.4)] hover:-translate-y-1 transition-all duration-300 group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-amber-50 mb-1 font-medium uppercase tracking-wider text-sm">খসড়া</p>
                    <p class="text-4xl font-black drop-shadow-sm"><?php echo formatNumberBengali($draft_posts); ?></p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-file-alt text-white text-3xl drop-shadow-md"></i>
                </div>
            </div>
        </div>
        
        <!-- Categories -->
        <div class="relative overflow-hidden bg-gradient-to-br from-fuchsia-500 to-purple-600 rounded-2xl p-6 text-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:shadow-[0_8px_30px_rgba(192,38,211,0.4)] hover:-translate-y-1 transition-all duration-300 group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-fuchsia-100 mb-1 font-medium uppercase tracking-wider text-sm">ক্যাটাগরি</p>
                    <p class="text-4xl font-black drop-shadow-sm"><?php echo formatNumberBengali($total_categories); ?></p>
                </div>
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-folder text-white text-3xl drop-shadow-md"></i>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-black text-black mb-4 uppercase tracking-widest border-b border-gray-100 pb-2">দ্রুত কাজ</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="<?php echo ADMIN_URL; ?>/post-create.php" 
               class="relative overflow-hidden bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-[0_8px_30px_rgba(16,185,129,0.15)] hover:border-emerald-200 hover:-translate-y-1 transition-all duration-300 group flex items-center">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-50 to-teal-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="h-12 w-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mr-4 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 z-10 shadow-sm">
                    <i class="fas fa-plus-circle text-xl"></i>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-emerald-800 transition-colors z-10">নতুন সংবাদ লিখুন</span>
                <i class="fas fa-arrow-right ml-auto text-emerald-300 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300 z-10"></i>
            </a>
            <a href="<?php echo ADMIN_URL; ?>/posts.php" 
               class="relative overflow-hidden bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-[0_8px_30px_rgba(59,130,246,0.15)] hover:border-blue-200 hover:-translate-y-1 transition-all duration-300 group flex items-center">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="h-12 w-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-4 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 z-10 shadow-sm">
                    <i class="fas fa-list text-xl"></i>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-blue-800 transition-colors z-10">সকল সংবাদ দেখুন</span>
                <i class="fas fa-arrow-right ml-auto text-blue-300 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300 z-10"></i>
            </a>
            <a href="<?php echo ADMIN_URL; ?>/media.php" 
               class="relative overflow-hidden bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-[0_8px_30px_rgba(168,85,247,0.15)] hover:border-purple-200 hover:-translate-y-1 transition-all duration-300 group flex items-center">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-50 to-fuchsia-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="h-12 w-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-4 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 z-10 shadow-sm">
                    <i class="fas fa-upload text-xl"></i>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-purple-800 transition-colors z-10">মিডিয়া আপলোড</span>
                <i class="fas fa-arrow-right ml-auto text-purple-300 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all duration-300 z-10"></i>
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Posts -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2">
                <h3 class="text-lg font-black text-black uppercase tracking-widest">সাম্প্রতিক সংবাদ</h3>
                <a href="<?php echo ADMIN_URL; ?>/posts.php" class="text-sm font-bold text-gray-500 hover:text-black transition-colors hover:underline">সব দেখুন</a>
            </div>
            
            <div class="space-y-4">
                <?php if (empty($recent_posts)): ?>
                    <p class="text-center text-gray-500 py-8 font-medium">কোনো সংবাদ পাওয়া যায়নি</p>
                <?php else: ?>
                    <?php foreach ($recent_posts as $index => $post_item): ?>
                        <div class="flex items-start space-x-4 p-4 rounded-lg border border-transparent hover:border-gray-200 hover:bg-gray-50 transition">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-black flex items-center justify-center text-white font-bold text-sm">
                                <?php echo formatNumberBengali($index + 1); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-black truncate">
                                    <?php echo escape($post_item['title']); ?>
                                </h4>
                                <div class="flex items-center space-x-3 mt-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                            <span class="px-2 py-1 text-xs font-bold rounded-full border <?php echo $post_item['status'] == 'published' ? 'bg-white text-black border-black' : 'bg-gray-100 text-gray-600 border-gray-300'; ?>">
                                <?php echo $post_item['status'] == 'published' ? 'প্রকাশিত' : 'খসড়া'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Breaking News -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2">
                <h3 class="text-lg font-black text-black uppercase tracking-widest">ব্রেকিং নিউজ</h3>
                <a href="<?php echo ADMIN_URL; ?>/posts.php?filter=breaking" class="text-sm font-bold text-gray-500 hover:text-black transition-colors hover:underline">সব দেখুন</a>
            </div>
            
            <div class="space-y-4">
                <?php if (empty($breaking_news)): ?>
                    <p class="text-center text-gray-500 py-8 font-medium">কোনো ব্রেকিং নিউজ নেই</p>
                <?php else: ?>
                    <?php foreach ($breaking_news as $news): ?>
                        <div class="flex items-start space-x-3 p-4 rounded-lg border border-transparent hover:border-gray-200 hover:bg-gray-50 transition border-l-4 hover:border-l-black">
                            <div class="flex-shrink-0">
                                <span class="inline-block px-2 py-1 bg-black text-white text-xs font-bold rounded uppercase tracking-wider">
                                    <i class="fas fa-bolt mr-1"></i>
                                    ব্রেকিং
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-black">
                                    <?php echo escape($news['title']); ?>
                                </h4>
                                <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-wider">
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
