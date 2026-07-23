<?php
/**
 * Category Page
 * 
 * @package Alokpath
 */

require_once 'config/config.php';

$slug = $_GET['slug'] ?? '';
$page = $_GET['page'] ?? 1;

if (empty($slug)) {
    redirect(SITE_URL);
}

// === PAGE CACHING ===
$cache_dir = __DIR__ . '/cache/categories';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}
$cache_file = $cache_dir . '/' . md5($slug . '_' . $page) . '.html';

// Try to serve from cache first
if (file_exists($cache_file)) {
    $cache_setting = (new Setting())->get('category_cache_time');
    $cache_time = ($cache_setting !== false) ? (int)$cache_setting : 300;
    
    if ((time() - filemtime($cache_file)) < $cache_time) {
        readfile($cache_file);
        echo "<!-- Cached: " . date('Y-m-d H:i:s', filemtime($cache_file)) . " -->";
        exit;
    }
}

$category = new Category();
$post = new Post();

$cat = $category->getBySlug($slug);

if (!$cat) {
    redirect(SITE_URL);
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = POSTS_PER_PAGE ?? 15; // default 15
$offset = ($page - 1) * $limit;

$posts = $post->getPublished($limit, $offset, $cat['id']);
$total = $category->getPostCount($cat['id']);
$total_pages = ceil($total / $limit);

$page_title = $cat['seo_title'] ?? $cat['name'];
$meta_description = $cat['seo_description'] ?? $cat['description'] ?? '';
$meta_keywords = $cat['seo_keywords'] ?? '';

// Get categories for header
$categories = $category->getActive();

ob_start();
component('header', ['categories' => $categories]);
?>

<!-- Main Content -->
<main class="max-w-4xl mx-auto px-4 py-8">
    
    <!-- Breadcrumb -->
    <nav class="mb-6 text-sm text-center">
        <ol class="flex items-center justify-center space-x-2">
            <li><a href="<?php echo SITE_URL; ?>" class="text-primary-600 hover:underline">প্রচ্ছদ</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-600 font-semibold"><?php echo escape($cat['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Category Header -->
    <div class="text-center mb-8 pb-4">
        <h1 class="text-4xl font-bold text-primary-800"><?php echo escape($cat['name']); ?></h1>
        <?php if (!empty($cat['description'])): ?>
            <p class="text-gray-600 mt-2"><?php echo escape($cat['description']); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Posts List -->
    <?php if (!empty($posts)): ?>
        <div class="space-y-2 border-t border-dashed border-gray-300">
            <?php foreach ($posts as $post_item): ?>
                <?php component('news-card', ['post' => $post_item, 'variant' => 'classic-list', 'theme' => 'light']); ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-10 flex justify-center w-full">
                <nav class="flex flex-wrap justify-center gap-2 max-w-full">
                    <?php if ($page > 1): ?>
                        <a href="?slug=<?php echo escape($cat['slug']); ?>&page=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-primary-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?slug=<?php echo escape($cat['slug']); ?>&page=<?php echo $i; ?>" 
                           class="px-4 py-2 <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white border hover:bg-primary-50'; ?> rounded-lg">
                            <?php echo formatNumberBengali($i); ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?slug=<?php echo escape($cat['slug']); ?>&page=<?php echo $page + 1; ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-primary-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-600 mb-2">কোনো সংবাদ পাওয়া যায়নি</h3>
            <p class="text-gray-500">এই ক্যাটাগরিতে এখনো কোনো সংবাদ প্রকাশিত হয়নি</p>
        </div>
    <?php endif; ?>
    
</main>

<?php
component('footer');
$content = ob_get_clean();

// Start output buffering for the final HTML containing the layout
ob_start();
include 'layouts/main.php';
$final_html = ob_get_clean();

// Minify the complete HTML before caching
$final_html = minify_html_safe($final_html);

// Save the completely rendered HTML to cache
@file_put_contents($cache_file, $final_html);

// Output the HTML
echo $final_html;
?>
