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
        <div class="flex items-center justify-center space-x-2 text-gray-700 font-medium text-sm md:text-base mt-2 bg-gray-50 px-4 py-2 rounded-full border border-gray-100 shadow-sm">
            <i class="far fa-calendar-alt text-primary-600"></i>
            <span><?php echo formatDateBengali(date('Y-m-d'), 'd F, Y'); ?></span>
            <span class="mx-1 text-gray-300">|</span>
            <i class="far fa-clock text-primary-600"></i>
            <span id="live-clock" class="font-bold text-primary-800"></span>
        </div>

        <script>
            function updateClock() {
                const now = new Date();
                let hours = now.getHours();
                let minutes = now.getMinutes();
                let seconds = now.getSeconds();
                let ampm = hours >= 12 ? 'পিএম' : 'এএম';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                
                let timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
                
                // Convert English digits to Bengali digits
                const bnNumbers = {'0':'০','1':'১','2':'২','3':'৩','4':'৪','5':'৫','6':'৬','7':'৭','8':'৮','9':'৯'};
                timeString = timeString.replace(/[0-9]/g, function(w){
                    return bnNumbers[w];
                });
                
                const clockEl = document.getElementById('live-clock');
                if (clockEl) {
                    clockEl.textContent = timeString;
                }
            }
            setInterval(updateClock, 1000);
            document.addEventListener('DOMContentLoaded', updateClock);
            updateClock(); // Initial call
        </script>
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
        color: var(--color-primary-dark) !important;
    }
    .nav-hover-effect:hover::before {
        transform: scaleY(1);
    }
    .dropdown-hover-effect:hover {
        background-color: #f3f4f6; /* gray-100 */
        color: var(--color-primary-dark);
    }

    @keyframes ticker {
        0% { transform: translateX(0); }
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
            
            <!-- Desktop Links and Search -->
            <div class="hidden lg:flex items-center justify-between w-full h-[52px]">
                
                <!-- Links -->
                <div class="flex items-center h-full">
                    <a href="<?php echo SITE_URL; ?>" class="px-4 py-3 nav-hover-effect font-medium text-lg flex items-center h-full" title="প্রচ্ছদ (Home)">
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
                                   class="px-4 py-3 nav-hover-effect font-medium text-lg whitespace-nowrap flex items-center h-full">
                                    <?php echo escape($item['title']); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Smart Search -->
                <div class="relative" id="desktopSearchContainer">
                    <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="flex items-center bg-primary-700 rounded-full overflow-hidden border border-primary-600 focus-within:border-white transition-colors h-9">
                        <input type="text" name="q" id="desktopSearchInput" placeholder="খবর খুঁজুন..." autocomplete="off"
                               class="bg-transparent text-white px-4 py-1 focus:outline-none w-48 xl:w-64 text-sm placeholder-gray-300">
                        <button type="submit" class="px-3 text-gray-300 hover:text-white transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- AJAX Results Dropdown -->
                    <div id="desktopSearchResults" class="absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-2xl border border-gray-100 overflow-hidden hidden z-[70]">
                        <!-- Results will be injected here via JS -->
                    </div>
                </div>
                
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
            <?php
            $current_full_url = rtrim(SITE_URL, '/') . $_SERVER['REQUEST_URI'];
            $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $site_path = parse_url(SITE_URL, PHP_URL_PATH) ?? '';
            // Determine if we are on the home page
            $is_home = ($current_path === $site_path || $current_path === rtrim($site_path, '/') . '/' || $current_path === rtrim($site_path, '/') . '/index.php');
            
            $home_class = $is_home 
                ? "px-4 py-3 bg-gray-50 text-primary-800 font-bold border-l-4 border-primary-600 block" 
                : "px-4 py-3 text-gray-800 font-bold hover:bg-gray-50 transition border-b border-gray-100 block";
            ?>
            <a href="<?php echo SITE_URL; ?>" class="<?php echo $home_class; ?>">প্রচ্ছদ</a>
            
            <?php if (!empty($mobileMenuItems)): ?>
                <?php foreach ($mobileMenuItems as $index => $item): 
                    $is_item_active = ($item['url'] === $current_full_url || rtrim($item['url'], '/') === rtrim($current_full_url, '/'));
                    // Check if any child is active
                    $has_active_child = false;
                    if (!empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            if ($child['url'] === $current_full_url || rtrim($child['url'], '/') === rtrim($current_full_url, '/')) {
                                $has_active_child = true;
                                break;
                            }
                        }
                    }
                    
                    $parent_active_class = ($is_item_active || $has_active_child)
                        ? "px-4 py-3 bg-gray-50 text-primary-800 font-bold border-l-4 border-primary-600 flex items-center justify-between"
                        : "px-4 py-3 text-gray-800 font-bold hover:bg-gray-50 transition border-b border-gray-100 flex items-center justify-between focus:outline-none";
                ?>
                    <?php if (!empty($item['children'])): ?>
                        <div>
                            <button onclick="toggleMobileSubmenu(<?php echo $index; ?>, this)" 
                               class="w-full <?php echo $parent_active_class; ?>">
                                <?php echo escape($item['title']); ?>
                                <i id="mobile-icon-<?php echo $index; ?>" class="fas fa-chevron-down text-sm transition-transform text-gray-500 <?php echo $has_active_child ? 'rotate-180' : ''; ?>"></i>
                            </button>
                            
                            <div id="mobile-submenu-<?php echo $index; ?>" class="<?php echo $has_active_child ? 'flex' : 'hidden'; ?> flex-col bg-gray-50 py-1">
                                <?php foreach ($item['children'] as $child): 
                                    $is_child_active = ($child['url'] === $current_full_url || rtrim($child['url'], '/') === rtrim($current_full_url, '/'));
                                    $child_active_class = $is_child_active ? "text-primary-600 font-bold bg-gray-100" : "text-gray-700 font-medium hover:text-primary-600";
                                ?>
                                    <a href="<?php echo escape($child['url']); ?>" 
                                       class="px-8 py-2 <?php echo $child_active_class; ?> transition flex items-center gap-2 border-b border-gray-100 last:border-0">
                                        <i class="fas fa-angle-right text-sm <?php echo $is_child_active ? 'text-primary-500' : 'text-gray-400'; ?>"></i>
                                        <?php echo escape($child['title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo escape($item['url']); ?>" 
                           class="<?php echo $parent_active_class; ?>">
                            <?php echo escape($item['title']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Secondary Bar Removed -->

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
        document.getElementById('mobileMenu').classList.add('hidden');
    });

    function toggleMobileSubmenu(index, btnElement) {
        const submenu = document.getElementById('mobile-submenu-' + index);
        const icon = document.getElementById('mobile-icon-' + index);
        
        submenu.classList.toggle('hidden');
        submenu.classList.toggle('flex');
        icon.classList.toggle('rotate-180');
        
        if (!submenu.classList.contains('hidden')) {
            // Give the browser a moment to render the expanded menu before calculating height
            setTimeout(() => {
                submenu.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 50);
        }
    }

    // Advanced Live Search using vanilla JS
    document.addEventListener("DOMContentLoaded", function() {
        const desktopSearchInput = document.getElementById('desktopSearchInput');
        const resultsContainer = document.getElementById('desktopSearchResults');
        let searchTimeout;

        if (desktopSearchInput && resultsContainer) {
            desktopSearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    resultsContainer.classList.add('hidden');
                    return;
                }

                // Show loading state
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = '<div class="p-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>খোঁজা হচ্ছে...</div>';

                searchTimeout = setTimeout(() => {
                    fetch('<?php echo SITE_URL; ?>/ajax_search.php?q=' + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            if (data.length > 0) {
                                let html = '<div class="flex flex-col">';
                                data.forEach(post => {
                                    html += `
                                        <a href="${post.url}" class="flex items-center gap-3 p-3 hover:bg-gray-50 border-b border-gray-100 transition">
                                            ${post.image ? `<img src="${post.image}" class="w-12 h-12 object-cover rounded shadow-sm flex-shrink-0" alt="">` : `<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400"><i class="fas fa-image"></i></div>`}
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-semibold text-gray-800 truncate">${post.title}</h4>
                                                <span class="text-xs text-gray-500">${post.date}</span>
                                            </div>
                                        </a>
                                    `;
                                });
                                html += `<a href="<?php echo SITE_URL; ?>/search.php?q=${encodeURIComponent(query)}" class="block w-full text-center p-3 text-sm text-primary-600 hover:bg-primary-50 font-medium">সব ফলাফল দেখুন</a>`;
                                html += '</div>';
                                resultsContainer.innerHTML = html;
                            } else {
                                resultsContainer.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm">কোনো খবর পাওয়া যায়নি।</div>';
                            }
                        })
                        .catch(err => {
                            resultsContainer.innerHTML = '<div class="p-4 text-center text-red-500 text-sm">সমস্যা হয়েছে। আবার চেষ্টা করুন।</div>';
                        });
                }, 300); // 300ms debounce
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const container = document.getElementById('desktopSearchContainer');
                if (container && !container.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });
        }
    });
</script>

