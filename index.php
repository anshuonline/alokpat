<?php
/**
 * Homepage - Magazine Grid Layout
 * 
 * @package Alokpat
 */

require_once 'config/config.php';

// === PAGE CACHING ===
$cache_dir = __DIR__ . '/cache';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}
$cache_file = $cache_dir . '/index.html';

// Try to serve from cache first
if (file_exists($cache_file)) {
    $cache_setting = (new Setting())->get('home_cache_time');
    $cache_time = ($cache_setting !== false) ? (int)$cache_setting : 300;
    
    if ((time() - filemtime($cache_file)) < $cache_time) {
        readfile($cache_file);
        echo "<!-- Cached: " . date('Y-m-d H:i:s', filemtime($cache_file)) . " -->";
        exit;
    }
}

// Get data
$category = new Category();
$post = new Post();

$categories = $category->getCategoriesWithCount();

// Apply homepage categories setting if available
$setting = new Setting();
$homepage_categories_json = $setting->get('homepage_categories_order');
if (!empty($homepage_categories_json)) {
    $homepage_categories_order = json_decode($homepage_categories_json, true);
    if (is_array($homepage_categories_order) && !empty($homepage_categories_order)) {
        $filtered_categories = [];
        // Extract category lookup array for fast access
        $cat_lookup = [];
        foreach ($categories as $cat) {
            $cat_lookup[$cat['id']] = $cat;
        }
        
        // Add categories in the exact order specified in settings
        foreach ($homepage_categories_order as $cat_id) {
            if (isset($cat_lookup[$cat_id])) {
                $filtered_categories[] = $cat_lookup[$cat_id];
            }
        }
        $categories = $filtered_categories;
    }
}
$latest_posts = $post->getPublished(15); // Fetch 15 for the new professional layout

$home_seo_title = $setting->get('home_seo_title');
$page_title = $home_seo_title ?: 'প্রচ্ছদ';

$meta_description = $setting->get('home_seo_description') ?: '';
$meta_keywords = $setting->get('home_seo_keywords') ?: '';

$canonical_url = SITE_URL . '/';
$og_type = 'website';
$og_url = SITE_URL . '/';

// Get logo URL for schema
$_setting = new Setting();
$_site_name = $_setting->get('site_name') ?: 'আলোকপাত';
$_site_logo = $_setting->get('site_logo') ?: (SITE_URL . '/assets/images/logo.png');

$json_ld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'NewsMediaOrganization',
            '@id' => SITE_URL . '/#organization',
            'name' => $_site_name,
            'url' => SITE_URL,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $_site_logo
            ],
            'sameAs' => array_values(array_filter([
                $_setting->get('facebook_url'),
                $_setting->get('twitter_url'),
                $_setting->get('youtube_url'),
                $_setting->get('instagram_url')
            ]))
        ],
        [
            '@type' => 'WebSite',
            '@id' => SITE_URL . '/#website',
            'url' => SITE_URL,
            'name' => $_site_name,
            'publisher' => ['@id' => SITE_URL . '/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => SITE_URL . '/search.php?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ]
    ]
];

ob_start();

// Include Header Component
component('header', ['categories' => $categories]);
?>

<!-- Main Content -->
<main class="bg-gray-100 min-h-screen">
    
    <!-- Top Sections Container -->
    <div class="max-w-6xl mx-auto px-4 py-6">
        
        <!-- Featured Section Removed -->
        
        
        <!-- Top Split Section (Recent News) -->
        <div class="w-full mb-10">
            <!-- Recent News - Professional Layout (15 posts) -->
            <?php if (!empty($latest_posts)): ?>
            <section class="bg-white shadow-sm border-t-4 border-primary-500 rounded-b-lg overflow-hidden">
                <!-- Section Header -->
                <div class="bg-primary-600 text-white px-5 py-3 flex justify-between items-center">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="far fa-newspaper mr-2.5 text-primary-200"></i>সাম্প্রতিক খবর
                    </h2>
                    <span class="text-xs text-primary-200 font-medium hidden sm:inline-block">
                        <i class="fas fa-sync-alt mr-1"></i>সর্বশেষ আপডেট
                    </span>
                </div>
                
                <div class="p-5 md:p-6">
                    <!-- Row 1: Hero (1 large left) + 4 list items right -->
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
                        <!-- Hero Post (3 cols) -->
                        <div class="lg:col-span-3">
                            <?php component('news-card', ['post' => $latest_posts[0], 'variant' => 'magazine-main', 'theme' => 'light']); ?>
                        </div>
                        <!-- Side List (2 cols) -->
                        <div class="lg:col-span-2 flex flex-col justify-between">
                            <?php foreach (array_slice($latest_posts, 1, 4) as $lp): ?>
                                <?php component('news-card', ['post' => $lp, 'variant' => 'magazine-list', 'theme' => 'light']); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Divider -->
                    <div class="border-t-2 border-primary-100 mb-8"></div>
                    
                    <!-- Row 2: 4 medium vertical cards in a grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5 mb-8">
                        <?php foreach (array_slice($latest_posts, 5, 4) as $mp): ?>
                        <a href="<?php echo url_for_post($mp); ?>" class="group block">
                            <div class="relative overflow-hidden rounded-lg aspect-video mb-2.5 bg-gray-100">
                                <?php if(!empty($mp['featured_image'])): ?>
                                    <img src="<?php echo escape($mp['featured_image']); ?>" 
                                         alt="<?php echo escape($mp['title']); ?>" 
                                         loading="lazy" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                         onload="this.classList.remove('animate-pulse', 'bg-gray-200');">
                                <?php endif; ?>
                                <?php if(!empty($mp['category_name'])): ?>
                                    <span class="absolute bottom-0 left-0 bg-primary-600 text-white text-[10px] font-bold px-2 py-0.5">
                                        <?php echo escape($mp['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-bold text-gray-800 group-hover:text-primary-600 transition text-sm leading-snug line-clamp-2 mb-1">
                                <?php echo escape($mp['title']); ?>
                            </h3>
                            <p class="text-[11px] text-gray-400 font-medium">
                                <i class="far fa-clock mr-1"></i><?php echo timeAgoBengali($mp['published_at'] ?? $mp['created_at']); ?>
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Divider -->
                    <div class="border-t-2 border-primary-100 mb-8"></div>
                    
                    <!-- Row 3: 6 compact horizontal list items (2 columns) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-0">
                        <?php foreach (array_slice($latest_posts, 9, 6) as $idx => $cp): ?>
                        <a href="<?php echo url_for_post($cp); ?>" class="group flex items-center py-3.5 border-b border-dashed border-gray-200 last:border-0 gap-4">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-50 text-primary-600 font-black text-sm flex items-center justify-center border border-primary-100">
                                <?php echo $idx + 10; ?>
                            </span>
                            <?php if(!empty($cp['featured_image'])): ?>
                                <div class="w-16 h-12 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                                    <img src="<?php echo escape($cp['featured_image']); ?>" 
                                         alt="<?php echo escape($cp['title']); ?>" 
                                         loading="lazy" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-700 group-hover:text-primary-600 transition text-[14px] leading-snug line-clamp-2">
                                    <?php echo escape($cp['title']); ?>
                                </h3>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    <?php if(!empty($cp['category_name'])): ?>
                                        <span class="text-primary-500 font-semibold"><?php echo escape($cp['category_name']); ?></span> &middot; 
                                    <?php endif; ?>
                                    <?php echo timeAgoBengali($cp['published_at'] ?? $cp['created_at']); ?>
                                </p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
        

        
        <!-- Category Magazine Grids (Full Width Rows) -->
        <div class="space-y-10">
            <?php
            $section_index = 0;
            foreach ($categories as $cat): 
                if ($cat['post_count'] == 0) continue;
                
                // Fetch exactly 5 posts for the 1+4 layout
                $cat_posts = $post->getPublished(5, 0, $cat['id']);
                if (empty($cat_posts)) continue;
                
                // ONLY 1 section is dark (AMOLED pure black)
                $is_dark = ($section_index === 1); 
                $theme = $is_dark ? 'dark' : 'light';
                $section_bg = $is_dark ? 'bg-black' : 'bg-white';
                
                $section_index++;
            ?>
                <section class="<?php echo $section_bg; ?> shadow-sm <?php echo $is_dark ? '' : 'border-t-4 border-primary-500'; ?>">
                    <!-- Category Header Bar (Solid Blue) -->
                    <div class="bg-primary-600 text-white px-4 py-2 flex justify-between items-center">
                        <h2 class="text-xl font-bold">
                            <?php echo escape($cat['name']); ?>
                        </h2>
                        <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($cat['slug']); ?>" class="text-sm hover:underline">
                            আরও খবর &raquo;
                        </a>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <?php component('news-card', ['post' => $cat_posts[0], 'variant' => 'magazine-main', 'theme' => $theme]); ?>
                            </div>
                            <div class="flex flex-col justify-between space-y-4">
                                <?php foreach (array_slice($cat_posts, 1, 4) as $cat_post): ?>
                                    <?php component('news-card', ['post' => $cat_post, 'variant' => 'magazine-list', 'theme' => $theme]); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        
    </div>
    
</main>

<?php
// Include Footer Component
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
