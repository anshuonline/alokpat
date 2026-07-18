<?php
/**
 * Homepage - Magazine Grid Layout
 * 
 * @package Alokpat
 */

require_once 'config/config.php';

// Get data
$category = new Category();
$post = new Post();

$categories = $category->getCategoriesWithCount();
$featured_posts = $post->getFeatured(5);
$trending_posts = $post->getTrending(10);
$latest_posts = $post->getPublished(5); // Fetch exactly 5 for the 1+4 layout

$setting = new Setting();
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
        
        <!-- World Cup 2026 Promo Banner -->
        <div class="w-full mb-10">
            <a href="<?php echo SITE_URL; ?>/worldcup2026.php" class="block relative rounded-xl overflow-hidden shadow-lg group">
                <div class="absolute inset-0 bg-gradient-to-r from-red-900 to-black opacity-95 transition-opacity group-hover:opacity-100"></div>
                <div class="relative p-6 md:p-8 flex flex-col md:flex-row items-center justify-between text-white border border-red-800/50 rounded-xl">
                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                        <i class="fas fa-futbol text-4xl text-amber-500 animate-pulse"></i>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black uppercase tracking-wider bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-400">FIFA World Cup 2026</h3>
                            <p class="text-gray-300 font-medium">সম্পূর্ণ লাইভ কভারেজ, পরিসংখ্যান এবং আরও অনেক কিছু &rarr;</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center bg-black/40 p-3 px-5 rounded-lg border border-red-500/30">
                        <span class="text-xs text-red-400 font-bold uppercase tracking-widest mb-1">Kickoff In</span>
                        <div id="wc-promo-timer" class="flex space-x-3 text-center">
                            <div><span class="days text-xl font-bold font-mono">00</span><span class="text-[10px] uppercase block text-gray-400">Days</span></div>
                            <span class="text-xl font-bold text-gray-600">:</span>
                            <div><span class="hours text-xl font-bold font-mono">00</span><span class="text-[10px] uppercase block text-gray-400">Hrs</span></div>
                            <span class="text-xl font-bold text-gray-600">:</span>
                            <div><span class="minutes text-xl font-bold font-mono">00</span><span class="text-[10px] uppercase block text-gray-400">Min</span></div>
                            <span class="text-xl font-bold text-gray-600">:</span>
                            <div><span class="seconds text-xl font-bold font-mono text-amber-500">00</span><span class="text-[10px] uppercase block text-amber-500/70">Sec</span></div>
                        </div>
                    </div>
                </div>
            </a>
            
            <script>
                function updatePromoTimer() {
                    const target = new Date("June 11, 2026 12:00:00").getTime();
                    const now = new Date().getTime();
                    const diff = target - now;
                    
                    if (diff > 0) {
                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                        
                        const container = document.getElementById('wc-promo-timer');
                        if (container) {
                            container.querySelector('.days').innerText = days.toString().padStart(2, '0');
                            container.querySelector('.hours').innerText = hours.toString().padStart(2, '0');
                            container.querySelector('.minutes').innerText = minutes.toString().padStart(2, '0');
                            container.querySelector('.seconds').innerText = seconds.toString().padStart(2, '0');
                        }
                    }
                }
                setInterval(updatePromoTimer, 1000);
                document.addEventListener('DOMContentLoaded', updatePromoTimer);
                updatePromoTimer();
            </script>
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
include 'layouts/main.php';
?>
