<?php
/**
 * Search Page
 * 
 * @package Alokpath
 */

require_once 'config/config.php';

$query = trim($_GET['q'] ?? '');
$post = new Post();

$posts = [];
$total_results = 0;
if (!empty($query)) {
    $posts = $post->search($query, POSTS_PER_PAGE);
    $total_results = count($posts);
}

$page_title = !empty($query) ? 'অনুসন্ধান: ' . escape($query) : 'অনুসন্ধান';

// Get categories for header
$category = new Category();
$categories = $category->getActive();

// Get site info for logo fallback
$settingModel = new Setting();
$site_info = $settingModel->getSiteInfo();

ob_start();
component('header', ['categories' => $categories]);
?>

<!-- Search Hero -->
<div class="bg-gradient-to-br from-primary-800 via-primary-700 to-primary-900 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-40 h-40 border border-white rounded-full"></div>
        <div class="absolute bottom-10 right-20 w-60 h-60 border border-white rounded-full"></div>
        <div class="absolute top-20 right-40 w-20 h-20 border border-white rounded-full"></div>
    </div>
    
    <div class="container mx-auto px-4 py-12 md:py-16 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">
                <i class="fas fa-search mr-3 opacity-80"></i>অনুসন্ধান করুন
            </h1>
            <p class="text-primary-200 text-lg mb-8">আপনার পছন্দের খবর, বিষয় বা কীওয়ার্ড দিয়ে খুঁজুন</p>
            
            <!-- Search Form -->
            <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="max-w-2xl mx-auto">
                <div class="flex rounded-xl overflow-hidden shadow-2xl bg-white">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" 
                               name="q" 
                               value="<?php echo escape($query); ?>"
                               placeholder="আপনার অনুসন্ধান লিখুন..." 
                               class="w-full pl-12 pr-4 py-4 text-lg focus:outline-none text-gray-800 placeholder-gray-400"
                               autofocus>
                    </div>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-500 text-white px-8 py-4 font-bold transition-colors text-lg flex items-center gap-2">
                        <i class="fas fa-search"></i>
                        <span class="hidden sm:inline">অনুসন্ধান</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<main class="container mx-auto px-4 py-8">
    
    <?php if (!empty($query)): ?>
        <!-- Results Summary -->
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    "<span class="text-primary-600"><?php echo escape($query); ?></span>" এর জন্য ফলাফল
                </h2>
                <p class="text-gray-500 text-sm mt-1">
                    মোট <?php echo formatNumberBengali($total_results); ?> টি ফলাফল পাওয়া গেছে
                </p>
            </div>
            <?php if ($total_results > 0): ?>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-primary-100 text-primary-700">
                    <i class="fas fa-check-circle mr-2"></i><?php echo formatNumberBengali($total_results); ?> টি মিল পাওয়া গেছে
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($posts)): ?>
            <!-- Search Results -->
            <div class="space-y-5">
                <?php foreach ($posts as $index => $post_item): ?>
                    <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow duration-300 group">
                        <a href="<?php echo url_for_post($post_item); ?>" class="flex flex-col md:flex-row">
                            
                            <!-- Thumbnail -->
                            <div class="md:w-72 flex-shrink-0 overflow-hidden bg-gray-50 flex items-center justify-center">
                                <?php 
                                $hasImage = !empty($post_item['featured_image']);
                                $imgSrc = $hasImage ? escape($post_item['featured_image']) : escape($site_info['site_logo'] ?? '');
                                $imgClass = $hasImage ? 'object-cover' : 'object-contain p-4 animate-pulse opacity-30';
                                
                                if (!empty($imgSrc)): ?>
                                    <img src="<?php echo $imgSrc; ?>" 
                                         alt="<?php echo escape($post_item['title']); ?>"
                                         class="w-full h-48 md:h-full <?php echo $imgClass; ?> group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center min-h-[160px]">
                                        <i class="fas fa-newspaper text-5xl text-primary-300"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 p-5 md:p-6 flex flex-col justify-between">
                                <div>
                                    <!-- Category & Date -->
                                    <div class="flex items-center gap-3 mb-3">
                                        <?php if (!empty($post_item['category_name'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 bg-primary-100 text-primary-700 text-xs font-semibold rounded-full">
                                                <?php echo escape($post_item['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-xs text-gray-400">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php 
                                            $post_date = $post_item['published_at'] ?? $post_item['created_at'];
                                            echo date('d M Y', strtotime($post_date));
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Title -->
                                    <h3 class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-primary-600 transition-colors mb-2 line-clamp-2">
                                        <?php echo escape($post_item['title']); ?>
                                    </h3>
                                    
                                    <!-- Excerpt -->
                                    <?php if (!empty($post_item['excerpt'])): ?>
                                        <p class="text-gray-500 text-sm leading-relaxed line-clamp-2 mb-3">
                                            <?php echo escape($post_item['excerpt']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Read More -->
                                <div class="flex items-center justify-end mt-auto pt-3 border-t border-gray-100">
                                    <span class="text-primary-600 text-sm font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                                        বিস্তারিত <i class="fas fa-arrow-right text-xs"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Results -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 md:p-16 text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-primary-50 flex items-center justify-center">
                    <i class="fas fa-search text-4xl text-primary-300"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-700 mb-3">কোনো ফলাফল পাওয়া যায়নি</h3>
                <p class="text-gray-500 mb-6 max-w-md mx-auto">
                    "<strong><?php echo escape($query); ?></strong>" এর জন্য কোনো খবর পাওয়া যায়নি। অন্য কোনো শব্দ দিয়ে আবার চেষ্টা করুন।
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <span class="text-sm text-gray-400">চেষ্টা করুন:</span>
                    <?php 
                    $suggestions = ['রাজনীতি', 'খেলা', 'বিনোদন', 'প্রযুক্তি', 'স্বাস্থ্য'];
                    foreach ($suggestions as $suggestion): ?>
                        <a href="<?php echo SITE_URL; ?>/search.php?q=<?php echo urlencode($suggestion); ?>" 
                           class="px-3 py-1.5 bg-primary-50 text-primary-600 rounded-full text-sm font-medium hover:bg-primary-100 transition">
                            <?php echo $suggestion; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Default State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 md:p-16 text-center">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-primary-50 flex items-center justify-center animate-pulse">
                <i class="fas fa-search text-4xl text-primary-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-700 mb-3">কিছু খুঁজুন</h3>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                উপরের সার্চ বক্সে আপনার প্রশ্ন বা কীওয়ার্ড লিখে অনুসন্ধান করুন
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <span class="text-sm text-gray-400 self-center">জনপ্রিয়:</span>
                <?php 
                $popular_terms = ['রাজনীতি', 'খেলা', 'বিনোদন', 'প্রযুক্তি', 'আন্তর্জাতিক'];
                foreach ($popular_terms as $term): ?>
                    <a href="<?php echo SITE_URL; ?>/search.php?q=<?php echo urlencode($term); ?>" 
                       class="px-4 py-2 bg-primary-50 text-primary-600 rounded-full text-sm font-medium hover:bg-primary-100 hover:shadow-sm transition">
                        <i class="fas fa-hashtag text-xs mr-1 opacity-60"></i><?php echo $term; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
</main>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php
component('footer');
$content = ob_get_clean();
include 'layouts/main.php';
?>
