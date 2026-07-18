<?php
/**
 * Header Component
 * 
 * @param array $categories - Active categories
 */

$categories = $categories ?? [];
$setting = new Setting();
$site_info = $setting->getSiteInfo();
?>

<!-- Logo & Date Area (Top) -->
<div class="bg-white py-6">
    <div class="max-w-6xl mx-auto px-4 flex flex-col items-center justify-center">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>" class="mb-3">
            <?php if (!empty($site_info['site_logo'])): ?>
                <img src="<?php echo escape($site_info['site_logo']); ?>" alt="Logo" class="h-16 md:h-20">
            <?php else: ?>
                <h1 class="text-3xl font-heading font-black text-gray-900 tracking-tight">
                    <?php echo escape($site_info['site_name'] ?? 'আলোকপাত'); ?>
                </h1>
            <?php endif; ?>
        </a>
        <!-- Date -->
        <div class="text-gray-700 font-medium text-sm md:text-base">
            <?php echo formatDateBengali(date('Y-m-d'), 'd F, Y'); ?>
        </div>
    </div>
</div>

<!-- Main Navigation (Blue Bar) -->
<style>
    .nav-hover-effect {
        position: relative;
        z-index: 1;
        overflow: hidden;
        transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .nav-hover-effect::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background-color: #ffffff;
        z-index: -1;
        transform: scaleY(0);
        transform-origin: bottom;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .nav-hover-effect:hover {
        color: #1e3a8a !important; /* text-blue-900 */
    }
    .nav-hover-effect:hover::before {
        transform: scaleY(1);
    }
</style>
<nav id="main-nav" class="bg-primary-800 text-white shadow-md sticky z-50 transition-all duration-200 relative" style="position: -webkit-sticky; position: sticky; top: 0;">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between">
            
            <!-- Desktop Links -->
            <div class="hidden lg:flex items-center space-x-1 w-full justify-center">
                <a href="<?php echo SITE_URL; ?>" class="px-4 py-3 nav-hover-effect font-medium text-lg">
                    <i class="fas fa-home text-xl"></i>
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($category['slug']); ?>" 
                       class="px-4 py-3 nav-hover-effect font-medium text-lg whitespace-nowrap">
                        <?php echo escape($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Search & Mobile Menu Button -->
            <div class="flex items-center space-x-4 py-2 lg:hidden w-full justify-between">
                <span class="font-bold text-lg">মেনু</span>
                <div class="flex items-center space-x-3">
                    <button onclick="document.getElementById('searchModal').classList.remove('hidden')" 
                            class="hover:text-gray-300 transition p-2">
                        <i class="fas fa-search"></i>
                    </button>
                    <button id="mobileMenuBtn" class="hover:text-gray-300 transition p-2 border border-primary-600 rounded">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Mobile Menu (Now anchored to the nav) -->
    <div id="mobileMenu" class="hidden lg:hidden bg-white border-b shadow-2xl absolute top-full left-0 w-full z-40 max-h-[75vh] overflow-y-auto">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col space-y-1">
            <a href="<?php echo SITE_URL; ?>" class="px-4 py-3 bg-gray-50 text-primary-800 font-bold border-l-4 border-primary-600">প্রচ্ছদ</a>
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($category['slug']); ?>" 
                   class="px-4 py-3 text-gray-800 font-medium hover:bg-gray-50 transition border-b border-gray-100 last:border-0">
                    <?php echo escape($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<!-- Secondary Bar (Breaking / Social) -->
<div class="bg-gray-100 border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between">
        
        <!-- Breaking News Ticker (Left) -->
        <div class="flex items-center w-full md:w-3/4 overflow-hidden bg-white">
            <div class="bg-primary-100 text-primary-800 font-bold px-4 py-3 whitespace-nowrap z-10 flex-shrink-0">
                এই মুহূর্তে
            </div>
            <div class="overflow-hidden flex-1 relative h-full flex items-center px-4">
                <?php
                $post = new Post();
                $breaking_news = $post->getBreakingNews(5);
                ?>
                <div class="breaking-ticker whitespace-nowrap absolute">
                    <?php if (!empty($breaking_news)): ?>
                        <?php foreach ($breaking_news as $news): ?>
                            <a href="<?php echo url_for_post($news['slug']); ?>" class="inline-block mr-12 hover:text-primary-600 font-medium text-gray-800">
                                <?php echo escape($news['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray-500">আপাতত কোন ব্রেকিং নিউজ নেই</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Social Icons (Right) -->
        <div class="hidden md:flex items-center space-x-4 py-2 px-4">
            <?php if (!empty($site_info['facebook_url'])): ?>
                <a href="<?php echo escape($site_info['facebook_url']); ?>" target="_blank" class="text-primary-600 hover:opacity-80 transition text-lg">
                    <i class="fab fa-facebook-f"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($site_info['youtube_url'])): ?>
                <a href="<?php echo escape($site_info['youtube_url']); ?>" target="_blank" class="text-red-600 hover:opacity-80 transition text-lg">
                    <i class="fab fa-youtube"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($site_info['twitter_url'])): ?>
                <a href="<?php echo escape($site_info['twitter_url']); ?>" target="_blank" class="text-black hover:opacity-80 transition text-lg">
                    <i class="fa-brands fa-x-twitter"></i>
                </a>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- Mobile Menu has been moved inside nav for sticky positioning -->

<?php
// Render header ad if enabled
if (empty($site_info['site_header_html'])) {
    render_ad('header');
}
?>

<!-- Search Modal -->
<div id="searchModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-[60] flex items-start justify-center pt-20 px-4 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full p-6 animate-fade-in-down">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-800">অনুসন্ধান করুন</h3>
            <button onclick="document.getElementById('searchModal').classList.add('hidden')" class="text-gray-400 hover:text-red-600 transition bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
            <div class="flex shadow-sm rounded-lg overflow-hidden border border-gray-300 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 transition">
                <input type="text" 
                       name="q" 
                       placeholder="খবর খুঁজুন..." 
                       class="flex-1 px-4 py-4 w-full focus:outline-none text-lg">
                <button type="submit" class="bg-primary-700 text-white px-8 py-4 hover:bg-primary-800 transition font-bold">
                    খুঁজুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Robust Sticky Navbar Fallback for Mobile Browsers
    document.addEventListener("DOMContentLoaded", function() {
        const nav = document.getElementById('main-nav');
        if (!nav) return;
        
        // Use a placeholder to prevent page jump when nav becomes fixed
        const placeholder = document.createElement('div');
        placeholder.style.display = 'none';
        placeholder.style.height = nav.offsetHeight + 'px';
        nav.parentNode.insertBefore(placeholder, nav);

        const stickyOffset = nav.offsetTop;
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset >= stickyOffset && stickyOffset > 0) {
                nav.classList.add('fixed', 'top-0', 'left-0', 'w-full');
                nav.classList.remove('sticky'); // Remove sticky to prevent conflicts
                nav.style.position = 'fixed';
                placeholder.style.display = 'block';
            } else {
                nav.classList.remove('fixed', 'top-0', 'left-0', 'w-full');
                nav.classList.add('sticky');
                nav.style.position = '';
                placeholder.style.display = 'none';
            }
        });
    });
</script>
