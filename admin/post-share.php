<?php
/**
 * Generate Social Media Share Image
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

if (!isset($_GET['id'])) {
    redirect(ADMIN_URL . '/posts.php');
}

$post_id = (int)$_GET['id'];
$post = (new Post())->getById($post_id);

if (!$post) {
    setFlash('error', 'সংবাদ খুঁজে পাওয়া যায়নি');
    redirect(ADMIN_URL . '/posts.php');
}

$page_title = 'সোশ্যাল মিডিয়া শেয়ার ইমেজ';
ob_start();

$setting = new Setting();
$site_logo = $setting->get('site_logo');

// Setup image and fallback
$image_url = !empty($post['featured_image']) ? $post['featured_image'] : SITE_URL . '/assets/images/default-news.jpg';

// Add cache-busting to avoid cors issues when html2canvas fetches image
// If it's a local image, html2canvas needs it to be accessible or same-origin.
// We'll proxy or use base64 if needed, but since it's same origin mostly, it's fine.
?>

<?php
// Get theme primary color
$theme_color = (new Setting())->get('theme_color') ?: 'default';
$theme_palettes = [
    'default' => '#2563eb', // blue
    'ruby'    => '#dc2626', // red
    'emerald' => '#059669', // green
    'amber'   => '#d97706', // amber
    'violet'  => '#7c3aed', // violet
    'rose'    => '#e11d48', // rose
];
$primary_color = $theme_palettes[$theme_color] ?? '#2563eb';

// Fetch or generate short link
$short_code = null;
$db_error = null;

try {
    $stmt = $db->prepare("SELECT short_code FROM short_links WHERE post_id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $link = $stmt->fetch();

    if ($link) {
        $short_code = $link['short_code'];
    } else {
        // Generate a unique 8-character alphanumeric code
        do {
            $short_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8);
            $checkStmt = $db->prepare("SELECT id FROM short_links WHERE short_code = ?");
            $checkStmt->execute([$short_code]);
        } while ($checkStmt->fetchColumn());
        
        // Insert new short link
        $insertStmt = $db->prepare("INSERT INTO short_links (post_id, short_code) VALUES (?, ?)");
        $insertStmt->execute([$post_id, $short_code]);
    }
} catch (PDOException $e) {
    // Table doesn't exist, ignore generation
    $db_error = true;
}

$short_url = $short_code ? SITE_URL . '/u' . $short_code : 'Database Error: Table not created';
$excerpt = !empty($post['meta_description']) ? escape($post['meta_description']) : escape(substr(strip_tags($post['content']), 0, 150)) . '...';

$hashtags_str = '';
if (!empty($post['tags']) && is_array($post['tags'])) {
    $hashtags = [];
    foreach ($post['tags'] as $tag) {
        if (!empty($tag['name'])) {
            $tag_name = str_replace([' ', '-', '_'], '', $tag['name']);
            $hashtags[] = '#' . $tag_name;
        }
    }
    if (!empty($hashtags)) {
        $hashtags_str = "\n\n" . implode(' ', $hashtags);
    }
}

$share_text = escape($post['title']) . "\n\n" . $excerpt . ($short_code ? "\n\nবিস্তারিত পড়ুন: " . $short_url : "") . $hashtags_str;
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-share-alt"></i>
            </div>
            সোশ্যাল মিডিয়া কার্ড জেনারেটর
        </h2>
        <div class="flex space-x-3">
            <a href="<?php echo ADMIN_URL; ?>/posts.php" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-2"></i> ফিরে যান
            </a>
            <button id="downloadBtn" style="background-color: <?php echo $primary_color; ?>;" class="text-white px-6 py-2.5 rounded-lg font-semibold transition shadow-lg flex items-center hover:opacity-90">
                <i class="fas fa-download mr-2"></i> ইমেজ ডাউনলোড করুন
            </button>
        </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="flex flex-col md:flex-row gap-6 items-start">
        
        <!-- Left Sidebar (Controls & Share Text) -->
        <div class="w-full md:w-1/3 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">নির্দেশনা</h3>
                <p class="text-sm text-gray-600 mb-4">
                    এই পেজটি আপনার নিউজের ওপর ভিত্তি করে ফেসবুক বা ইন্সটাগ্রামের জন্য একটি 1:1 (Square) শেপের ছবি তৈরি করবে।
                </p>
                <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                    <li>উচ্চমানের রেজোলিউশনে সেভ হবে</li>
                    <li>আলোকপাতের লোগো ও ওয়াটারমার্ক থাকবে</li>
                    <li>ব্রাউজার থেকেই সরাসরি ডাউনলোড হবে</li>
                </ul>
            </div>
            
            <!-- Short Link & Share Text Box -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1" style="background-color: <?php echo $primary_color; ?>;"></div>
                <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-link mr-2 text-gray-500"></i> শেয়ার ক্যাপশন ও লিংক
                </h3>
                
                <textarea id="shareCaption" class="w-full h-32 p-3 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none" readonly><?php echo $share_text; ?></textarea>
                
                <div class="mt-4 flex space-x-2">
                    <input type="text" id="shortUrlInput" value="<?php echo escape($short_url); ?>" class="flex-1 p-2 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-600" readonly>
                    <button onclick="copyShareText()" style="background-color: <?php echo $primary_color; ?>;" class="px-4 py-2 text-white text-sm font-semibold rounded-lg hover:opacity-90 transition flex items-center">
                        <i class="fas fa-copy mr-2"></i> কপি করুন
                    </button>
                </div>
            </div>
            
            <div id="loadingStatus" class="hidden text-indigo-600 font-semibold items-center justify-center p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                <i class="fas fa-spinner fa-spin mr-2"></i> ইমেজ রেন্ডার হচ্ছে...
            </div>
        </div>

        <!-- The Canvas Preview (Right Side) -->
        <div class="w-full md:w-2/3 flex justify-center bg-gray-100 rounded-xl p-4 overflow-hidden relative border border-gray-200" style="min-height: 400px;">
            <div id="card-wrapper" style="width: 1080px; height: 1080px; transform-origin: top center; transform: scale(0.45); margin-bottom: -590px;" class="shadow-2xl flex-shrink-0 transition-transform">
                
                <!-- Actual Capture Node (Modern Gen-Z Dark Overlay Style) -->
                <div id="social-card" class="relative w-full h-full flex flex-col overflow-hidden" style="font-family: <?php echo SITE_FONT_CSS; ?>;">
                    
                    <!-- Full Bleed Background Image -->
                    <div class="absolute inset-0 w-full h-full">
                        <img src="<?php echo escape($image_url); ?>" id="card-bg-image" class="absolute inset-0 w-full h-full object-cover" crossorigin="anonymous">
                        <!-- Dark gradient overlay from bottom -->
                        <div class="absolute inset-0" style="background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.75) 35%, rgba(0,0,0,0.15) 65%, rgba(0,0,0,0.05) 100%);"></div>
                    </div>

                    <!-- Top Bar: Tag + Category -->
                    <div class="relative z-10 flex items-center justify-between p-10">
                        <!-- Category Badge -->
                        <?php if(!empty($post['category_name'])): ?>
                        <div class="px-5 py-2 rounded-full text-white text-[20px] font-bold tracking-wide" style="background-color: <?php echo $primary_color; ?>;">
                            <?php echo escape($post['category_name']); ?>
                        </div>
                        <?php else: ?>
                        <div></div>
                        <?php endif; ?>

                        <!-- CTA Tag -->
                        <div class="flex items-center gap-3 bg-white/15 backdrop-blur-sm text-white px-6 py-2.5 rounded-full text-[20px] font-semibold border border-white/20">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"/></svg>
                            বিস্তারিত কমেন্টে
                        </div>
                    </div>

                    <!-- Spacer to push content down -->
                    <div class="flex-1"></div>

                    <!-- Bottom Content Area -->
                    <div class="relative z-10 px-12 pb-0">
                        
                        <!-- Breaking Badge -->
                        <?php if($post['is_breaking']): ?>
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center text-white px-5 py-2 rounded-full text-[20px] font-bold tracking-widest uppercase" style="background: <?php echo $primary_color; ?>;">
                                <span class="w-3 h-3 bg-white rounded-full mr-3" style="animation: pulse 1.5s infinite;"></span>
                                ব্রেকিং নিউজ
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Title -->
                        <h1 class="text-white font-extrabold leading-[1.3] tracking-tight" style="font-size: 56px; text-shadow: 0 2px 20px rgba(0,0,0,0.5);">
                            <?php echo escape($post['title']); ?>
                        </h1>
                    </div>

                    <!-- Brand Footer Strip -->
                    <div class="relative z-10 mt-8 flex items-center justify-between px-12 py-6" style="background: <?php echo $primary_color; ?>;">
                        <div class="flex items-center">
                            <?php if($site_logo): ?>
                                <img src="<?php echo escape($site_logo); ?>" alt="Alokpat" class="h-12 object-contain brightness-0 invert" crossorigin="anonymous">
                            <?php else: ?>
                                <h2 class="text-white text-4xl font-black tracking-tight">আলোকপাত</h2>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-3 text-white/90">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a15.3 15.3 0 014 9 15.3 15.3 0 01-4 9 15.3 15.3 0 01-4-9 15.3 15.3 0 014-9z"/></svg>
                            <span class="text-[26px] font-bold tracking-wider uppercase">alokpat.in</span>
                        </div>
                    </div>
                </div>
                <!-- End Capture Node -->

            </div>
        </div>
    </div>
</div>

    <!-- Inject Font URL dynamically -->
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet" crossorigin="anonymous">

<!-- Load html-to-image (Most modern and bug-free canvas renderer) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
<script>
// Function to copy share text
function copyShareText() {
    const textarea = document.getElementById('shareCaption');
    textarea.select();
    document.execCommand('copy');
    
    // Toast notification
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-50 transform transition-all duration-300';
    toast.innerHTML = '<i class="fas fa-check-circle text-green-400 mr-2"></i> ক্যাপশন ও লিংক কপি করা হয়েছে!';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Adjust scale based on screen width for preview
    function adjustScale() {
        const wrapper = document.getElementById('card-wrapper');
        const container = wrapper.parentElement;
        const containerWidth = container.clientWidth - 32;
        
        if (containerWidth < 1080) {
            const scale = containerWidth / 1080;
            wrapper.style.transform = `scale(${scale})`;
            const visualHeight = 1080 * scale;
            const negativeMargin = 1080 - visualHeight;
            wrapper.style.marginBottom = `-${negativeMargin}px`;
        } else {
            wrapper.style.transform = 'scale(1)';
            wrapper.style.marginBottom = '0px';
        }
    }
    
    window.addEventListener('resize', adjustScale);
    setTimeout(adjustScale, 100);

    // Download functionality
    document.getElementById('downloadBtn').addEventListener('click', function() {
        const node = document.getElementById('social-card');
        const btn = this;
        const loader = document.getElementById('loadingStatus');
        
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        loader.classList.remove('hidden');
        loader.classList.add('flex');
        
        // Wait for UI to update
        setTimeout(() => {
            const options = {
                quality: 0.95,
                backgroundColor: '#ffffff',
                width: 1080,
                height: 1080,
                style: {
                    transform: 'scale(1)',
                    transformOrigin: 'top left'
                },
                pixelRatio: 1
            };

            // First call forces the library to fetch and cache web fonts
            htmlToImage.toJpeg(node, options)
            .then(() => {
                // Second call generates the actual image with fonts applied
                return htmlToImage.toJpeg(node, options);
            })
            .then(function (dataUrl) {
                // Restore UI
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                loader.classList.add('hidden');
                loader.classList.remove('flex');
                
                // Create download link
                const link = document.createElement('a');
                link.download = 'alokpat-post-' + <?php echo $post_id; ?> + '.jpg';
                link.href = dataUrl;
                link.click();
            })
            .catch(function (error) {
                alert('ইমেজ জেনারেট করতে সমস্যা হয়েছে: ' + error.message);
                console.error('oops, something went wrong!', error);
                
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                loader.classList.add('hidden');
                loader.classList.remove('flex');
            });
        }, 500);
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
