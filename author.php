<?php
/**
 * Author Profile Page
 * 
 * @package Alokpath
 */

require_once 'config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect(SITE_URL);
}

$userModel = new User();
$postModel = new Post();

$author = $userModel->getById($id);

if (!$author) {
    redirect(SITE_URL);
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = POSTS_PER_PAGE ?? 15; // default 15
$offset = ($page - 1) * $limit;

$posts = $postModel->getByAuthor($author['id'], $limit, $offset);
$total = $postModel->getCountByAuthor($author['id']);
$total_pages = ceil($total / $limit);

$page_title = $author['full_name'] . ' এর লেখা সংবাদ';

// Get categories for header
$category = new Category();
$categories = $category->getActive();

$author_url = SITE_URL . '/author.php?id=' . $author['id'] . '&name=' . urlencode(str_replace(' ', '-', $author['full_name']));
$breadcrumb_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'প্রচ্ছদ', 'item' => SITE_URL],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'লেখক', 'item' => SITE_URL . '/author.php'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $author['full_name'], 'item' => $author_url]
    ]
];

$json_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'ProfilePage',
    'mainEntity' => [
        '@type' => 'Person',
        '@id' => SITE_URL . '/#author-' . $author['id'],
        'name' => $author['full_name'],
        'jobTitle' => 'Journalist',
        'description' => !empty($author['bio']) ? $author['bio'] : ($author['full_name'] . ' আলোকপাত এর একজন নিয়মিত লেখক ও সাংবাদিক।'),
        'url' => $author_url,
    ]
];
// Only add sameAs if author has social links
$_author_social = array_values(array_filter([
    $author['facebook_url'] ?? null,
    $author['twitter_url'] ?? null,
    $author['youtube_url'] ?? null
]));
if (!empty($_author_social)) {
    $json_ld['mainEntity']['sameAs'] = $_author_social;
}
if (!empty($author['avatar'])) {
    $json_ld['mainEntity']['image'] = $author['avatar'];
}

ob_start();
component('header', ['categories' => $categories]);
?>

<!-- SEO Meta -->
<meta name="description" content="<?php echo escape($author['bio'] ?? $author['full_name'] . ' এর প্রোফাইল এবং সংবাদ তালিকা'); ?>">

<!-- Main Content -->
<main class="max-w-4xl mx-auto px-4 py-8">
    
    <!-- Breadcrumb -->
    <nav class="mb-6 text-sm text-center">
        <ol class="flex items-center justify-center space-x-2 text-primary-600 font-medium">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:underline">প্রচ্ছদ</a></li>
            <li class="text-gray-400">/</li>
            <li>লেখক</li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-800"><?php echo escape($author['full_name']); ?></li>
        </ol>
    </nav>
    
    <!-- Author Profile Header -->
    <div class="text-center mb-10 pb-6 border-b border-gray-200">
        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-r from-primary-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold shadow-md mb-4">
            <?php if (!empty($author['avatar'])): ?>
                <img src="<?php echo escape($author['avatar']); ?>" class="w-full h-full rounded-full object-cover">
            <?php else: ?>
                <?php echo mb_substr($author['full_name'], 0, 1, 'UTF-8'); ?>
            <?php endif; ?>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo escape($author['full_name']); ?></h1>
        <div class="text-sm font-semibold text-primary-600 mb-4 bg-primary-50 inline-block px-3 py-1 rounded-full border border-primary-100">
            <i class="fas fa-newspaper mr-1"></i> সর্বমোট <?php echo formatNumberBengali($total); ?> টি সংবাদ
        </div>
        <?php if (!empty($author['bio'])): ?>
            <p class="text-gray-600 max-w-2xl mx-auto mb-4"><?php echo escape($author['bio']); ?></p>
        <?php endif; ?>
        
        <div class="flex items-center justify-center gap-4 mt-2">
            <?php if (!empty($author['facebook_url'])): ?>
                <a href="<?php echo escape($author['facebook_url']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm" title="Facebook">
                    <i class="fab fa-facebook-f text-lg"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($author['twitter_url'])): ?>
                <a href="<?php echo escape($author['twitter_url']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-gray-50 text-gray-900 flex items-center justify-center hover:bg-black hover:text-white transition shadow-sm" title="X (Twitter)">
                    <i class="fa-brands fa-x-twitter text-lg"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($author['youtube_url'])): ?>
                <a href="<?php echo escape($author['youtube_url']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition shadow-sm" title="YouTube">
                    <i class="fab fa-youtube text-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Posts List -->
    <?php if (!empty($posts)): ?>
        <div class="space-y-4">
            <h2 class="text-xl font-bold mb-6 text-gray-800"><i class="fas fa-edit mr-2 text-primary-600"></i>প্রকাশিত সংবাদসমূহ</h2>
            <div class="border-t border-dashed border-gray-300">
                <?php foreach ($posts as $post_item): ?>
                    <?php component('news-card', ['post' => $post_item, 'variant' => 'classic-list', 'theme' => 'light']); ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-10 flex justify-center w-full">
                <nav class="flex flex-wrap justify-center gap-2 max-w-full">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $author['id']; ?>&name=<?php echo urlencode($author['full_name']); ?>&page=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-white border rounded-lg hover:bg-primary-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?id=<?php echo $author['id']; ?>&name=<?php echo urlencode($author['full_name']); ?>&page=<?php echo $i; ?>" 
                           class="px-4 py-2 <?php echo $i == $page ? 'bg-primary-600 text-white' : 'bg-white border hover:bg-primary-50'; ?> rounded-lg">
                            <?php echo formatNumberBengali($i); ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?id=<?php echo $author['id']; ?>&name=<?php echo urlencode($author['full_name']); ?>&page=<?php echo $page + 1; ?>" 
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
            <p class="text-gray-500">এই লেখকের কোনো সংবাদ প্রকাশিত হয়নি</p>
        </div>
    <?php endif; ?>
    
</main>

<?php
component('footer');
$content = ob_get_clean();
include 'layouts/main.php';
?>
