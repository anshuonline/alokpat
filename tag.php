<?php
/**
 * Tag Page
 * 
 * @package Alokpath
 */

require_once 'config/config.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(SITE_URL);
}

$tagModel = new Tag();
$post = new Post();

$tag_data = $tagModel->getBySlug($slug);

if (!$tag_data) {
    redirect(SITE_URL);
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = POSTS_PER_PAGE ?? 15; // default 15
$offset = ($page - 1) * $limit;

$posts = $post->getPublishedByTag($limit, $offset, $tag_data['id']);
$total = $tagModel->getPostCount($tag_data['id']);
$total_pages = ceil($total / $limit);

$page_title = $tag_data['seo_title'] ?? 'ট্যাগ: ' . $tag_data['name'];
$meta_description = $tag_data['seo_description'] ?? $tag_data['description'] ?? '';
$meta_keywords = $tag_data['seo_keywords'] ?? '';

// Get categories for header
$category = new Category();
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
            <li class="text-gray-600 font-semibold">ট্যাগ: <?php echo escape($tag_data['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Tag Header -->
    <div class="text-center mb-8 pb-4">
        <h1 class="text-4xl font-bold text-primary-800"><i class="fas fa-tags text-primary-500 mr-2"></i><?php echo escape($tag_data['name']); ?></h1>
        <?php if (!empty($tag_data['description'])): ?>
            <p class="text-gray-600 mt-2"><?php echo escape($tag_data['description']); ?></p>
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
                        <a href="?slug=<?php echo escape($tag_data['slug']); ?>&page=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-primary-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?slug=<?php echo escape($tag_data['slug']); ?>&page=<?php echo $i; ?>" 
                           class="px-4 py-2 <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white border hover:bg-primary-50'; ?> rounded-lg">
                            <?php echo formatNumberBengali($i); ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?slug=<?php echo escape($tag_data['slug']); ?>&page=<?php echo $page + 1; ?>" 
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
include 'layouts/main.php';
?>
