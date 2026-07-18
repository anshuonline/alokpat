<?php
/**
 * Search Page
 * 
 * @package Alokpath
 */

require_once 'config/config.php';

$query = sanitize($_GET['q'] ?? '');
$post = new Post();

$posts = [];
if (!empty($query)) {
    $posts = $post->search($query, POSTS_PER_PAGE);
}

$page_title = 'অনুসন্ধান';

// Get categories for header
$category = new Category();
$categories = $category->getActive();

ob_start();
component('header', ['categories' => $categories]);
?>

<main class="container mx-auto px-4 py-8">
    
    <!-- Search Header -->
    <div class="bg-white rounded-xl shadow-md p-8 mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">অনুসন্ধান ফলাফল</h1>
        <?php if (!empty($query)): ?>
            <p class="text-gray-600">
                "<?php echo escape($query); ?>" এর জন্য <?php echo formatNumberBengali(count($posts)); ?> টি ফলাফল পাওয়া গেছে
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Search Form -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="max-w-2xl">
            <div class="flex">
                <input type="text" 
                       name="q" 
                       value="<?php echo escape($query); ?>"
                       placeholder="আপনার অনুসন্ধান লিখুন..." 
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="bg-primary-600 text-white px-6 py-3 rounded-r-lg hover:bg-primary-700 transition">
                    <i class="fas fa-search mr-2"></i>
                    অনুসন্ধান
                </button>
            </div>
        </form>
    </div>
    
    <!-- Results -->
    <?php if (!empty($query)): ?>
        <?php if (!empty($posts)): ?>
            <div class="space-y-6">
                <?php foreach ($posts as $post_item): ?>
                    <?php component('news-card', ['post' => $post_item, 'variant' => 'horizontal']); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-600 mb-2">কোনো ফলাফল পাওয়া যায়নি</h3>
                <p class="text-gray-500">অন্য কোনো শব্দ দিয়ে অনুসন্ধান করুন</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
</main>

<?php
component('footer');
$content = ob_get_clean();
include 'layouts/main.php';
?>
