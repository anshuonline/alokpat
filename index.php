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
$cache_time = 300; // 5 minutes

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    // Serve from cache instantly
    readfile($cache_file);
    echo "<!-- Cached: " . date('Y-m-d H:i:s', filemtime($cache_file)) . " -->";
    exit;
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
$featured_posts = $post->getFeatured(5);
$trending_posts = $post->getTrending(10);
$latest_posts = $post->getPublished(5); // Fetch exactly 5 for the 1+4 layout

$home_seo_title = $setting->get('home_seo_title');
$page_title = $home_seo_title ?: 'প্রচ্ছদ';

$meta_description = $setting->get('home_seo_description') ?: '';
$meta_keywords = $setting->get('home_seo_keywords') ?: '';

ob_start();

// Include Header Component
component('header', ['categories' => $categories]);
?>

<!-- Main Content -->
<main class="bg-gray-100 min-h-screen">
    
    <!-- Top Sections Container -->
    <div class="max-w-6xl mx-auto px-4 py-6">
        
        <!-- Featured Section Removed -->
        
        
        <!-- Top Split Section (Recent News + Sidebar) -->
        <div class="w-full mb-10">
            <!-- Recent News Grid (1 Large, 4 Small) -->
            <?php if (!empty($latest_posts)): ?>
            <section class="bg-white p-6 shadow-sm border-t-4 border-gray-400">
                <div class="flex justify-center mb-8 relative">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative bg-white px-4">
                        <h2 class="text-xl md:text-2xl font-bold text-primary-800 bg-primary-50 px-6 py-2 rounded-full border border-primary-100 shadow-sm">
                            সাম্প্রতিক খবর
                        </h2>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <?php component('news-card', ['post' => $latest_posts[0], 'variant' => 'magazine-main', 'theme' => 'light']); ?>
                    </div>
                    <div class="flex flex-col justify-between space-y-4">
                        <?php foreach (array_slice($latest_posts, 1, 4) as $latest_post): ?>
                            <?php component('news-card', ['post' => $latest_post, 'variant' => 'magazine-list', 'theme' => 'light']); ?>
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
