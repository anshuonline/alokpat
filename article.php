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

// Load live updates if it is a live blog
$live_updates = [];
if (($article['post_type'] ?? 'standard') === 'live_blog') {
    require_once 'models/PostUpdate.php';
    $postUpdateModel = new PostUpdate();
    $live_updates = $postUpdateModel->getByPostId($article['id']);
}

// Get categories for header
$category = new Category();
$categories = $category->getActive();

ob_start();
?>

<!-- SEO Meta Tags -->
<?php if ($article['status'] === 'unlisted'): ?>
<meta name="robots" content="noindex,nofollow">
<?php else: ?>
<meta name="robots" content="<?php echo escape($article['robots_meta'] ?? 'index,follow'); ?>">
<?php endif; ?>
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
        <nav class="mb-6 text-sm text-center no-print">
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
            <?php
            $showFlags = true;
            if (!empty($article['flags_expiry']) && strtotime($article['flags_expiry']) <= time()) {
                $showFlags = false;
            }
            if ($showFlags):
                if (!empty($article['is_live'])): ?>
                    <span class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-sm font-bold rounded-full mb-3 animate-pulse shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-white mr-1.5"></span>লাইভ (Live)
                    </span>
                <?php elseif (!empty($article['is_breaking'])): ?>
                    <span class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-sm font-bold rounded-full mb-3 animate-pulse shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-white mr-1.5"></span>ব্রেকিং (Breaking)
                    </span>
                <?php endif; 
            endif;
            ?>
            <h1 class="text-3xl md:text-[3.8rem]" style="line-height: 1.1; font-weight: 700; color: #111827; margin-bottom: 1rem;">
                <?php echo escape($article['title']); ?>
            </h1>
            <?php 
            // Display excerpt/meta_description below title as a deck
            $excerpt = $article['meta_description'] ?? $article['excerpt'] ?? $article['meta_og_description'] ?? '';
            if (!empty($excerpt)): 
            ?>
                <p class="text-base md:text-lg text-gray-600 leading-relaxed font-medium mb-6">
                    <?php echo escape($excerpt); ?>
                </p>
            <?php endif; ?>
            
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="text-sm md:text-base font-bold text-gray-800 flex items-center">
                    <i class="fas fa-pen-nib mr-2 text-gray-500"></i>
                    <?php echo escape($article['author_name']); ?>
                </div>
                <div class="flex items-center gap-2 no-print">
                    <button onclick="window.print()" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition" title="Print Article">
                        <i class="fas fa-print"></i>
                    </button>
                    <button onclick="toggleFontSize()" class="w-8 h-8 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition font-bold text-sm tracking-tighter" title="Change Font Size">
                        AA
                    </button>
                </div>
            </div>
            
            <script>
                function toggleFontSize() {
                    const article = document.getElementById('article-content-inner');
                    if (article.classList.contains('large-text')) {
                        article.classList.remove('large-text');
                    } else {
                        article.classList.add('large-text');
                    }
                }
            </script>
            <style>
                .large-text p { font-size: 1.5rem !important; line-height: 2 !important; }
            </style>
            <?php 
            $setting_model = new Setting();
            $fb_url = $setting_model->get('facebook_url');
            $wa_url = $setting_model->get('whatsapp_channel_url');
            $tw_url = $setting_model->get('twitter_url');
            $yt_url = $setting_model->get('youtube_url');
            
            if (!empty($fb_url) || !empty($wa_url) || !empty($tw_url) || !empty($yt_url)):
            ?>
            <div class="flex flex-col sm:flex-row items-center sm:justify-between mt-2 mb-6 w-full gap-2 sm:gap-0 no-print">
                <span class="text-gray-700 font-bold text-sm md:text-base whitespace-nowrap">Follow Us:</span>
                <div class="flex flex-row items-center justify-center sm:justify-end gap-1 sm:gap-2 w-full sm:w-auto flex-1">
                <?php if (!empty($wa_url)): ?>
                <a href="<?php echo escape($wa_url); ?>" target="_blank" class="inline-flex items-center justify-center flex-1 sm:flex-none px-1 sm:px-3 py-1.5 sm:py-1.5 bg-green-500 text-white text-[11px] sm:text-sm rounded-full font-medium hover:bg-green-600 transition shadow-sm" title="WhatsApp">
                    <i class="fab fa-whatsapp sm:mr-1.5"></i> <span class="ml-1 sm:ml-0 hidden min-[380px]:inline">WhatsApp</span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($fb_url)): ?>
                <a href="<?php echo escape($fb_url); ?>" target="_blank" class="inline-flex items-center justify-center flex-1 sm:flex-none px-1 sm:px-3 py-1.5 sm:py-1.5 bg-blue-600 text-white text-[11px] sm:text-sm rounded-full font-medium hover:bg-blue-700 transition shadow-sm" title="Facebook">
                    <i class="fab fa-facebook-f sm:mr-1.5"></i> <span class="ml-1 sm:ml-0 hidden min-[380px]:inline">Facebook</span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($tw_url)): ?>
                <a href="<?php echo escape($tw_url); ?>" target="_blank" class="inline-flex items-center justify-center flex-1 sm:flex-none px-1 sm:px-3 py-1.5 sm:py-1.5 bg-gray-900 text-white text-[11px] sm:text-sm rounded-full font-medium hover:bg-black transition shadow-sm" title="X (Twitter)">
                    <i class="fa-brands fa-x-twitter sm:mr-1.5"></i> <span class="ml-1 sm:ml-0 hidden min-[380px]:inline">X</span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($yt_url)): ?>
                <a href="<?php echo escape($yt_url); ?>" target="_blank" class="inline-flex items-center justify-center flex-1 sm:flex-none px-1 sm:px-3 py-1.5 sm:py-1.5 bg-red-600 text-white text-[11px] sm:text-sm rounded-full font-medium hover:bg-red-700 transition shadow-sm" title="YouTube">
                    <i class="fab fa-youtube sm:mr-1.5"></i> <span class="ml-1 sm:ml-0 hidden min-[380px]:inline">YouTube</span>
                </a>
                <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Featured Image -->
        <?php if (!empty($article['featured_image'])): ?>
            <div class="mb-6 aspect-video">
                <a href="<?php echo escape($article['featured_image']); ?>" class="glightbox" data-title="<?php echo escape($article['featured_image_alt'] ?? $article['title']); ?>">
                    <img src="<?php echo escape($article['featured_image']); ?>" 
                         alt="<?php echo escape($article['featured_image_alt'] ?? $article['title']); ?>" 
                         class="w-full h-full object-cover rounded-lg shadow-sm cursor-zoom-in">
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Meta Row (Date on Left, Socials on Right) -->
        <div class="flex flex-col md:flex-row items-center justify-between border-y border-gray-100 py-4 mb-8 gap-4 md:gap-0">
            <div class="text-gray-500 text-sm font-medium flex flex-col sm:flex-row items-center gap-2 md:gap-4 w-full md:w-auto">
                <span class="flex items-center justify-center bg-gray-50 px-3 py-1.5 rounded-md border border-gray-100 w-full sm:w-auto"><i class="far fa-clock mr-1.5"></i> <?php echo formatDateBengali($article['published_at'] ?? $article['created_at']); ?></span>
                <?php 
                if (!empty($article['updated_at'])) {
                    $pub_date = formatDateBengali($article['published_at'] ?? $article['created_at']);
                    $upd_date = formatDateBengali($article['updated_at']);
                    
                    if ($upd_date !== $pub_date && strtotime($article['updated_at']) > strtotime($article['published_at'] ?? $article['created_at'])): 
                ?>
                    <span class="flex items-center justify-center bg-gray-50 px-3 py-1.5 rounded-md border border-gray-100 text-gray-500 w-full sm:w-auto">
                        <i class="fas fa-history mr-1.5"></i> আপডেট: <?php echo $upd_date; ?>
                    </span>
                <?php 
                    endif; 
                } 
                ?>
            </div>
            
            <div class="flex items-center space-x-2 md:space-x-3 w-full md:w-auto justify-center md:justify-end no-print">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(url_for_post($article)); ?>" 
                   target="_blank"
                   class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition shadow-sm hover:shadow-md" title="Share on Facebook">
                    <i class="fab fa-facebook-f text-lg"></i>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode($article['title'] . ' ' . url_for_post($article)); ?>" 
                   target="_blank"
                   class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition shadow-sm hover:shadow-md" title="Share on WhatsApp">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(url_for_post($article)); ?>&text=<?php echo urlencode($article['title']); ?>" 
                   target="_blank"
                   class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-gray-900 text-white flex items-center justify-center hover:bg-black transition shadow-sm hover:shadow-md" title="Share on X">
                    <i class="fa-brands fa-x-twitter text-lg"></i>
                </a>
                <button onclick="navigator.clipboard.writeText('<?php echo url_for_post($article); ?>'); alert('Link copied!');"
                   class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-gray-600 text-white flex items-center justify-center hover:bg-gray-700 transition shadow-sm hover:shadow-md" title="Copy Link">
                    <i class="fas fa-link text-lg"></i>
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

            /* Print Styles */
            @media print {
                body {
                    background: #fff;
                    color: #000;
                    margin: 0;
                    padding: 0;
                }
                header, footer, .no-print, .related-articles, .comments-section {
                    display: none !important;
                }
                .max-w-4xl {
                    max-width: 100% !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                h1, h2, h3, p, span, div, .article-content, .article-content * {
                    color: #000 !important;
                }
                .article-content p {
                    font-size: 14pt !important;
                    line-height: 1.6 !important;
                }
                img {
                    max-width: 100% !important;
                    height: auto !important;
                    page-break-inside: avoid;
                }
                a {
                    text-decoration: none !important;
                    color: #000 !important;
                }
                @page {
                    margin: 2cm;
                }
            }
        </style>
        <div id="article-content-wrapper" class="relative overflow-hidden max-h-[600px] transition-all duration-500 ease-in-out mb-12">
            <article id="article-content-inner" class="article-content max-w-none text-gray-800 leading-relaxed pb-8">
                <?php
                // Inject inline ads into article content based on settings
                echo inject_ads_into_content($article['content'], $article['id']);
                ?>
            </article>
            
            <!-- Read More Fade & Button -->
            <div id="read-more-overlay" class="absolute bottom-0 left-0 w-full h-48 bg-gradient-to-t from-white via-white/80 to-transparent flex items-end justify-center pb-4 z-10 hidden">
                <button onclick="expandArticle()" class="bg-primary-600 text-white font-bold py-3 px-8 rounded-full shadow-lg hover:bg-primary-700 hover:shadow-xl transition-all transform hover:-translate-y-1 flex items-center gap-2 border border-primary-500">
                    <span>আরও পড়ুন (Read More)</span>
                    <i class="fas fa-chevron-down animate-bounce mt-1"></i>
                </button>
            </div>
        </div>

        <?php if (($article['post_type'] ?? 'standard') === 'live_blog'): ?>
        <!-- Live Timeline UI -->
        <div class="live-timeline-section mt-8 mb-12">
            <div class="flex items-center mb-6 pb-2 border-b-2 border-red-100">
                <span class="relative flex h-4 w-4 mr-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-4 w-4 bg-red-600 shadow"></span>
                </span>
                <h2 class="text-2xl font-bold text-gray-800 m-0">লাইভ আপডেট (Live Updates)</h2>
            </div>
            
            <?php if (empty($live_updates)): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center text-gray-500">
                    <i class="fas fa-info-circle text-2xl mb-2 text-gray-400"></i>
                    <p>এখনও কোনো লাইভ আপডেট নেই। যুক্ত থাকার জন্য ধন্যবাদ।</p>
                </div>
            <?php else: ?>
                <div class="relative border-l-2 border-red-500 ml-4 pl-6 md:ml-6 md:pl-8 space-y-8 pb-4">
                    <?php foreach ($live_updates as $index => $update): ?>
                    <div class="relative group live-update-item <?php echo $index >= 10 ? 'hidden' : ''; ?>" id="update-<?php echo $update['id']; ?>">
                        <!-- Timeline Dot -->
                        <div class="absolute -left-[31px] md:-left-[39px] top-1.5 h-4 w-4 rounded-full bg-red-600 border-4 border-white shadow-sm"></div>
                        
                        <!-- Timeline Content -->
                        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center text-sm font-bold text-red-600 mb-3 bg-red-50 inline-block px-3 py-1 rounded-md">
                                <i class="far fa-clock mr-1.5"></i> <?php echo date('h:i A, d M Y', strtotime($update['update_time'])); ?>
                            </div>
                            
                            <?php 
                            $raw_text = strip_tags($update['content']);
                            $isLong = mb_strlen($raw_text) > 400; 
                            ?>
                            <div class="relative">
                                <?php if ($isLong): ?>
                                    <div id="update-excerpt-<?php echo $update['id']; ?>" class="article-content prose prose-sm sm:prose max-w-none text-gray-800">
                                        <?php echo mb_substr($raw_text, 0, 400); ?>... <span onclick="expandUpdateContent(<?php echo $update['id']; ?>)" class="text-blue-600 font-semibold cursor-pointer hover:underline ml-1">Load More</span>
                                    </div>
                                    <div id="update-content-<?php echo $update['id']; ?>" class="article-content prose prose-sm sm:prose max-w-none text-gray-800 hidden">
                                        <?php echo $update['content']; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="article-content prose prose-sm sm:prose max-w-none text-gray-800">
                                        <?php echo $update['content']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Share Update -->
                            <div class="mt-4 pt-3 border-t border-gray-50 flex items-center gap-3">
                                <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">শেয়ার করুন:</span>
                                <?php 
                                $updateUrl = urlencode(url_for_post($article) . '#update-' . $update['id']); 
                                $updateText = urlencode("Live Update: " . strip_tags(substr($update['content'], 0, 60)) . '...');
                                ?>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $updateUrl; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 transition"><i class="fab fa-facebook-square text-lg"></i></a>
                                <a href="https://wa.me/?text=<?php echo $updateText . ' ' . $updateUrl; ?>" target="_blank" class="text-green-500 hover:text-green-600 transition"><i class="fab fa-whatsapp text-lg"></i></a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo $updateUrl; ?>&text=<?php echo $updateText; ?>" target="_blank" class="text-gray-800 hover:text-black transition"><i class="fa-brands fa-x-twitter text-lg"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($live_updates) > 10): ?>
                <div class="text-center mt-8">
                    <button id="loadMoreUpdatesBtn" onclick="loadMoreLiveUpdates()" class="bg-red-50 text-red-600 border border-red-200 px-6 py-2.5 rounded-full font-bold hover:bg-red-100 transition shadow-sm">
                        আরও আপডেট দেখুন (Load More) <i class="fas fa-arrow-down ml-1"></i>
                    </button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const wrapper = document.getElementById('article-content-wrapper');
                const inner = document.getElementById('article-content-inner');
                const overlay = document.getElementById('read-more-overlay');
                
                // Show Read More only if content height exceeds the max-height
                if (inner.offsetHeight > 600) {
                    overlay.classList.remove('hidden');
                } else {
                    wrapper.classList.remove('max-h-[600px]');
                    wrapper.classList.remove('overflow-hidden');
                }
            });
            
            function expandArticle() {
                const wrapper = document.getElementById('article-content-wrapper');
                const overlay = document.getElementById('read-more-overlay');
                
                // Expand to full scrollHeight smoothly
                wrapper.style.maxHeight = wrapper.scrollHeight + 'px';
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                
                setTimeout(() => {
                    overlay.style.display = 'none';
                    wrapper.classList.remove('max-h-[600px]');
                    wrapper.classList.remove('overflow-hidden');
                    wrapper.style.maxHeight = 'none';
                }, 500);
            }
            
            function loadMoreLiveUpdates() {
                const items = document.querySelectorAll('.live-update-item.hidden');
                let count = 0;
                for (let i = 0; i < items.length; i++) {
                    if (count >= 10) break;
                    items[i].classList.remove('hidden');
                    count++;
                }
                if (document.querySelectorAll('.live-update-item.hidden').length === 0) {
                    const btn = document.getElementById('loadMoreUpdatesBtn');
                    if(btn) btn.style.display = 'none';
                }
            }
            
            function expandUpdateContent(id) {
                const excerpt = document.getElementById('update-excerpt-' + id);
                const content = document.getElementById('update-content-' + id);
                
                if(excerpt) excerpt.style.display = 'none';
                if(content) content.classList.remove('hidden');
            }
        </script>
        
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

