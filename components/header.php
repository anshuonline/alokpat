<?php
/**
 * Header Component
 * 
 * @param array $categories - Active categories (fallback)
 */

$categories = $categories ?? [];
$setting = new Setting();
$site_info = $setting->getSiteInfo();
$menuModel = new Menu();

// Fetch Dynamic Menus
$primaryMenuItems = $menuModel->getMenuByLocation('primary');
$mobileMenuItems = $menuModel->getMenuByLocation('mobile');

?>

<!-- Logo & Date Area (Top) -->
<div class="bg-white py-6">
    <div class="max-w-6xl mx-auto px-4 flex flex-col items-center justify-center">
        <a href="<?php echo SITE_URL; ?>" class="mb-3">
            <?php if (!empty($site_info['site_logo'])): ?>
                <img src="<?php echo escape($site_info['site_logo']); ?>" alt="Logo" class="h-16 md:h-20">
            <?php else: ?>
                <h1 class="text-3xl font-heading font-black text-gray-900 tracking-tight">
                    <?php echo escape($site_info['site_name'] ?? 'আলোকপাত'); ?>
                </h1>
            <?php endif; ?>
        </a>
        <div class="text-gray-700 font-medium text-sm md:text-base">
            <?php echo formatDateBengali(date('Y-m-d'), 'd F, Y'); ?>
        </div>
    </div>
</div>

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
    .dropdown-hover-effect:hover {
        background-color: #f3f4f6; /* gray-100 */
        color: #1e3a8a;
    }

    @keyframes ticker {
        0% { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }
    .breaking-ticker-container {
        overflow: hidden;
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
    }
    .breaking-ticker {
        display: inline-block;
        white-space: nowrap;
        animation: ticker 25s linear infinite;
        padding-left: 100%;
    }
    .breaking-ticker:hover {
        animation-play-state: paused;
    }
</style>


<nav id="main-nav" class="bg-primary-800 text-white shadow-md sticky z-50 transition-all duration-200 relative" style="position: -webkit-sticky; position: sticky; top: 0;">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between">
            
            <!-- Desktop Links -->
            <div class="hidden lg:flex items-center space-x-1 w-full justify-center">
                <a href="<?php echo SITE_URL; ?>" class="px-4 py-3 nav-hover-effect font-medium text-lg" title="প্রচ্ছদ (Home)">
                    <i class="fas fa-home text-xl"></i>
                </a>
                
                <?php if (!empty($primaryMenuItems)): ?>
                    <?php foreach ($primaryMenuItems as $item): ?>
                        <?php if (!empty($item['children'])): ?>
                            <!-- Dropdown Menu Item -->
                            <div class="relative group h-full">
                                <a href="<?php echo escape($item['url']); ?>" 
                                   class="px-4 py-3 nav-hover-effect font-medium text-lg whitespace-nowrap flex items-center gap-1 h-full cursor-pointer">
                                    <?php echo escape($item['title']); ?>
                                    <i class="fas fa-chevron-down text-xs transition-transform group-hover:rotate-180"></i>
                                </a>
                                <!-- Dropdown Content -->
                                <div class="absolute left-0 top-full hidden group-hover:block w-56 bg-white shadow-lg border-t-2 border-primary-600 rounded-b-lg overflow-hidden py-2 z-50">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <a href="<?php echo escape($child['url']); ?>" 
                                           class="block px-4 py-2 text-gray-700 font-medium dropdown-hover-effect transition-colors">
                                            <?php echo escape($child['title']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Regular Menu Item -->
                            <a href="<?php echo escape($item['url']); ?>" 
                               class="px-4 py-3 nav-hover-effect font-medium text-lg whitespace-nowrap">
                                <?php echo escape($item['title']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Search & Mobile Menu Button -->
            <div class="flex items-center space-x-4 py-2 lg:hidden w-full justify-between">
                <span class="font-bold text-lg">মেনু</span>
                <div class="flex items-center space-x-3">
                    <button onclick="document.getElementById('searchModal').classList.remove('hidden')" 
                            class="hover:text-gray-300 transition p-2">
                        <i class="fas fa-search"></i>
                    </button>
                    <button id="mobileMenuBtn" type="button" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="hover:text-gray-300 transition p-2 border border-primary-600 rounded cursor-pointer">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden lg:hidden bg-white border-b shadow-2xl absolute top-full left-0 w-full z-40 max-h-[75vh] overflow-y-auto">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col space-y-1">
            <a href="<?php echo SITE_URL; ?>" class="px-4 py-3 bg-gray-50 text-primary-800 font-bold border-l-4 border-primary-600">প্রচ্ছদ</a>
            
            <?php if (!empty($mobileMenuItems)): ?>
                <?php foreach ($mobileMenuItems as $item): ?>
                    <a href="<?php echo escape($item['url']); ?>" 
                       class="px-4 py-3 text-gray-800 font-bold hover:bg-gray-50 transition border-b border-gray-100 flex items-center justify-between">
                        <?php echo escape($item['title']); ?>
                    </a>
                    
                    <?php if (!empty($item['children'])): ?>
                        <div class="flex flex-col bg-gray-50 py-1">
                            <?php foreach ($item['children'] as $child): ?>
                                <a href="<?php echo escape($child['url']); ?>" 
                                   class="px-8 py-2 text-gray-700 font-medium hover:text-primary-600 transition flex items-center gap-2">
                                    <i class="fas fa-angle-right text-sm text-gray-400"></i>
                                    <?php echo escape($child['title']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
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
                if (empty($breaking_news)) {
                    // Fallback to latest 3 posts if no breaking news
                    $breaking_news = $post->getPublished(3);
                }
                ?>
                <div class="breaking-ticker-container w-full h-full"><div class="breaking-ticker">
                    <?php if (!empty($breaking_news)): ?>
                        <?php foreach ($breaking_news as $news): ?>
                            <a href="<?php echo url_for_post($news['slug']); ?>" class="inline-block mr-12 hover:text-primary-600 font-medium text-gray-800">
                                <?php echo escape($news['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-gray-500">আপাতত কোন ব্রেকিং নিউজ নেই</span>
                    <?php endif; ?>
                </div></div>
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
        
        const placeholder = document.createElement('div');
        placeholder.style.display = 'none';
        placeholder.style.height = nav.offsetHeight + 'px';
        nav.parentNode.insertBefore(placeholder, nav);

        const stickyOffset = nav.offsetTop;
        
        function handleScroll() {
            if (window.pageYOffset >= stickyOffset) {
                nav.classList.add('fixed', 'top-0', 'left-0', 'w-full');
                placeholder.style.display = 'block';
            } else {
                nav.classList.remove('fixed', 'top-0', 'left-0', 'w-full');
                placeholder.style.display = 'none';
            }
        }
        
        if (!CSS.supports('position', 'sticky')) {
            window.addEventListener('scroll', handleScroll);
        }
    });
</script>
