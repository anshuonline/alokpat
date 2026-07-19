<?php
/**
 * Article Detail Page
 * 
 * @package Alokpath
 */
require_once 'config/config.php';
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(SITE_URL);
}

$post = new Post();
$article = $post->getBySlug($slug);

if (!$article) {
    redirect(SITE_URL);
}

// Get related posts
$related_posts = $post->getRelated($article['id'], $article['category_id'], 5);

$page_title = $article['seo_title'] ?? $article['title'];

// Get categories for header
$category = new Category();
$categories = $category->getActive();

ob_start();
?>

<!-- SEO Meta Tags -->
<meta name="description" content="<?php echo escape($article['seo_description'] ?? $article['excerpt'] ?? ''); ?>">
<meta name="keywords" content="<?php echo escape($article['seo_keywords'] ?? ''); ?>">
<link rel="canonical" href="<?php echo url_for_post($article); ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?php echo escape($article['meta_og_title'] ?? $article['title']); ?>">
<meta property="og:description" content="<?php echo escape($article['meta_og_description'] ?? $article['excerpt'] ?? ''); ?>">
<?php 
$og_image = !empty($article['meta_og_image']) ? $article['meta_og_image'] : (!empty($article['featured_image']) ? $article['featured_image'] : '');
if (!empty($og_image) && !preg_match('~^(?:f|ht)tps?://~i', $og_image)) {
    $og_image = rtrim(SITE_URL, '/') . '/' . ltrim($og_image, '/');
}
if (!empty($og_image)): 
?>
<meta property="og:image" content="<?php echo escape($og_image); ?>">
<?php endif; ?>
<meta property="og:type" content="article">
<meta property="og:url" content="<?php echo url_for_post($article); ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="<?php echo escape($article['meta_twitter_card'] ?? 'summary_large_image'); ?>">
<meta name="twitter:title" content="<?php echo escape($article['meta_og_title'] ?? $article['title']); ?>">
<meta name="twitter:description" content="<?php echo escape($article['meta_og_description'] ?? $article['excerpt'] ?? ''); ?>">
<?php if (!empty($og_image)): ?>
<meta name="twitter:image" content="<?php echo escape($og_image); ?>">
<?php endif; ?>

<?php
component('header', ['categories' => $categories]);
?>

<main class="bg-white min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        
        <!-- Breadcrumb (Centered) -->
        <nav class="mb-6 text-sm text-center">
            <ol class="flex items-center justify-center space-x-2 text-primary-600 font-medium">
                <li><a href="<?php echo SITE_URL; ?>" class="hover:underline">প্রচ্ছদ</a></li>
                <?php if (!empty($article['category_name'])): ?>
                    <li class="text-gray-400">/</li>
                    <li><a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($article['category_slug']); ?>" class="hover:underline"><?php echo escape($article['category_name']); ?></a></li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <!-- Title & Excerpt (Centered) -->
        <div class="mb-8 text-center w-full">
            <h1 class="text-3xl md:text-5xl font-bold text-gray-900 leading-snug mb-4">
                <?php echo escape($article['title']); ?>
            </h1>
            
            <?php 
            // Display excerpt/meta_description below title as a deck
            $excerpt = $article['meta_description'] ?? $article['excerpt'] ?? $article['meta_og_description'] ?? '';
            if (!empty($excerpt)): 
            ?>
                <p class="text-lg md:text-xl text-gray-600 leading-relaxed font-medium">
                    <?php echo escape($excerpt); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Featured Image -->
        <?php if (!empty($article['featured_image'])): ?>
            <div class="mb-6 aspect-video">
                <img src="<?php echo escape($article['featured_image']); ?>" 
                     alt="<?php echo escape($article['featured_image_alt'] ?? $article['title']); ?>" 
                     class="w-full h-full object-cover rounded-lg shadow-sm">
            </div>
        <?php endif; ?>
        
        <!-- Meta Row (Date on Left, Socials on Right) -->
        <div class="flex flex-col md:flex-row items-center justify-between border-b border-gray-200 pb-4 mb-8">
            <div class="text-gray-500 text-sm mb-4 md:mb-0 font-medium flex items-center">
                <span><i class="far fa-clock mr-1"></i> <?php echo formatDateBengali($article['published_at'] ?? $article['created_at']); ?></span>
                <?php 
                if (!empty($article['updated_at'])) {
                    $pub_date = formatDateBengali($article['published_at'] ?? $article['created_at']);
                    $upd_date = formatDateBengali($article['updated_at']);
                    
                    if ($upd_date !== $pub_date && strtotime($article['updated_at']) > strtotime($article['published_at'] ?? $article['created_at'])): 
                ?>
                    <span class="ml-4 pl-4 border-l border-gray-300 text-gray-500">
                        <i class="fas fa-history mr-1"></i> আপডেট: <?php echo $upd_date; ?>
                    </span>
                <?php 
                    endif; 
                } 
                ?>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url_for_post($article)); ?>" 
                   target="_blank"
                   class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition" title="Share on Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' ' . url_for_post($article)); ?>" 
                   target="_blank"
                   class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition" title="Share on WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(url_for_post($article)); ?>&text=<?php echo urlencode($article['title']); ?>" 
                   target="_blank"
                   class="w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center hover:bg-blue-500 transition" title="Share on Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <button onclick="navigator.clipboard.writeText('<?php echo url_for_post($article); ?>'); alert('Link copied!');"
                   class="w-8 h-8 rounded-full bg-gray-600 text-white flex items-center justify-center hover:bg-gray-700 transition" title="Copy Link">
                    <i class="fas fa-link"></i>
                </button>
            </div>
        </div>
        
        <style>
            :root { --btn-primary: #2563eb; --btn-primary-hover: #1d4ed8; }
            .article-content p { margin: 0 0 1.2em 0; font-size: 1.25rem; line-height: 1.9; }
            .article-content p:empty { display: none; }
            .article-content figure { display: block; width: 100%; margin: 1.5em 0; text-align: center; clear: both; }
            .article-content figure img { display: inline-block; max-width: 100%; height: auto; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
            .article-content figcaption { display: block; font-size: 0.85em; color: #6b7280; margin-top: 0.5em; font-style: italic; text-align: center; }
            .article-content img { max-width: 100%; height: auto; }
            .article-content iframe { max-width: 100%; border-radius: 8px; }
            .article-content iframe[src*="youtube.com"], .article-content iframe[src*="youtu.be"] { width: 100%; aspect-ratio: 16 / 9; height: auto; }
            .article-content table { border-collapse: collapse; width: 100%; margin: 1.2em 0; overflow-x: auto; display: block; }
            .article-content table td, .article-content table th { border: 1px solid #d1d5db; padding: 8px 14px; }
            .article-content table th { background: #f3f4f6; font-weight: 700; }
            .article-content blockquote { border-left: 4px solid #2563eb; padding: 12px 20px; margin: 1.2em 0; background: #eff6ff; color: #1e40af; border-radius: 0 6px 6px 0; }
            .article-content h1, .article-content h2, .article-content h3 { font-weight: 700; margin: 1.4em 0 0.6em; }
            .article-content h2 { font-size: 1.5em; }
            .article-content h3 { font-size: 1.25em; }
            .article-content ul, .article-content ol { padding-left: 1.5em; margin: 0.8em 0; }
            .article-content li { margin-bottom: 0.3em; }
            .article-content .custom-cta-btn { display: inline-block; padding: 10px 24px; background-color: var(--btn-primary); color: #ffffff !important; text-decoration: none !important; border-radius: 6px; font-weight: 600; text-align: center; transition: all 0.3s ease; margin: 10px 0; }
            .article-content .custom-cta-btn:hover { background-color: var(--btn-primary-hover); transform: translateY(-2px); }
            
            /* Accordion */
            .article-content details { border: 1px solid #e5e7eb; border-radius: 6px; margin: 1em 0; background: #ffffff; }
            .article-content summary { padding: 12px 16px; font-weight: 600; cursor: pointer; background: #f9fafb; border-radius: 6px; list-style: none; display: flex; align-items: center; justify-content: space-between; }
            .article-content summary::-webkit-details-marker { display: none; }
            .article-content details[open] summary { border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom: 1px solid #e5e7eb; }
            .article-content details > div { padding: 16px; }
        </style>
        <article class="article-content max-w-none text-gray-800 leading-relaxed mb-12">
            <?php
            // Inject inline ads into article content based on settings
            echo inject_ads_into_content($article['content'], $article['id']);
            ?>
        </article>
        
        <!-- Tags -->
        <?php if (!empty($article['tags'])): ?>
            <div class="flex flex-wrap gap-2 mb-12 border-t border-b border-gray-100 py-6">
                <span class="font-bold text-gray-700 py-1 mr-2"><i class="fas fa-tags mr-1"></i> ট্যাগ:</span>
                <?php foreach ($article['tags'] as $tag): ?>
                    <a href="<?php echo SITE_URL; ?>/tag.php?slug=<?php echo escape($tag['slug']); ?>" 
                       class="px-4 py-1 bg-gray-100 text-gray-700 rounded-full hover:bg-primary-100 hover:text-primary-600 transition text-sm">
                        <?php echo escape($tag['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Author Bio Box -->
        <div class="mb-12 bg-primary-50 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6 border border-primary-100">
            <div class="flex-shrink-0">
                <?php if (!empty($article['author_avatar'])): ?>
                    <img src="<?php echo escape($article['author_avatar']); ?>" class="w-24 h-24 md:w-32 md:h-32 rounded-full object-cover shadow-md border-4 border-white" alt="<?php echo escape($article['author_name'] ?? 'Author'); ?>">
                <?php else: ?>
                    <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-primary-200 text-primary-600 flex items-center justify-center text-4xl shadow-md border-4 border-white">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h3 class="text-2xl font-bold text-primary-900 mb-2">
                    <a href="<?php echo SITE_URL; ?>/author.php?id=<?php echo (int)($article['author_id'] ?? 0); ?>&name=<?php echo urlencode(str_replace(' ', '-', $article['author_name'] ?? '')); ?>" class="hover:text-primary-700 transition">
                        <?php echo escape($article['author_name'] ?? 'অপরিচিত'); ?>
                    </a>
                </h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    <?php echo !empty($article['author_bio']) ? nl2br(escape($article['author_bio'])) : 'এই লেখকের কোন বায়ো বা তথ্য দেওয়া নেই।'; ?>
                </p>
                <a href="<?php echo SITE_URL; ?>/author.php?id=<?php echo (int)($article['author_id'] ?? 0); ?>&name=<?php echo urlencode(str_replace(' ', '-', $article['author_name'] ?? '')); ?>" class="inline-flex items-center justify-center px-5 py-2 border border-primary-300 text-sm font-medium rounded-md text-primary-700 bg-white hover:bg-primary-50 hover:text-primary-800 transition-colors shadow-sm">
                    সব লেখা দেখুন <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </a>
            </div>
        </div>
        
        <!-- Related Posts (Bottom) -->
        <?php if (!empty($related_posts)): ?>
            <div class="mt-12 bg-gray-50 rounded-2xl p-8">
                <h3 class="text-2xl font-bold mb-6 text-gray-800 text-center">
                    সম্পর্কিত সংবাদ
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach (array_slice($related_posts, 0, 3) as $related): ?>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                            <?php if (!empty($related['featured_image'])): ?>
                                <a href="<?php echo url_for_post($related); ?>" class="block aspect-video">
                                    <img src="<?php echo escape($related['featured_image']); ?>" 
                                         alt="<?php echo escape($related['title']); ?>" 
                                         class="w-full h-full object-cover">
                                </a>
                            <?php endif; ?>
                            <div class="p-4">
                                <h4 class="font-bold text-gray-800 hover:text-primary-600 transition line-clamp-2">
                                    <a href="<?php echo url_for_post($related); ?>">
                                        <?php echo escape($related['title']); ?>
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-500 mt-2">
                                    <?php echo timeAgoBengali($related['published_at'] ?? $related['created_at']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</main>

<?php
component('footer');
$content = ob_get_clean();
include 'layouts/main.php';
?>

