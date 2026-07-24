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
    'teal'    => '#0d9488', // teal
    'rose'    => '#e11d48', // rose
    'fuchsia' => '#c026d3', // fuchsia
    'indigo'  => '#4f46e5', // indigo
    'sky'     => '#0284c7', // sky
    'cyan'    => '#0891b2', // cyan
    'lime'    => '#65a30d', // lime
    'yellow'  => '#ca8a04', // yellow
    'orange'  => '#ea580c', // orange
    'stone'   => '#57534e', // stone
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
                
                <!-- Actual Capture Node (Minimal & Elegant) -->
                <div id="social-card" class="relative w-full h-full bg-white flex flex-col" style="font-family: <?php echo SITE_FONT_CSS; ?>;">
                    
                    <!-- Top Image Area -->
                    <div class="relative w-full h-[55%]">
                        <img src="<?php echo escape($image_url); ?>" id="card-bg-image" class="absolute inset-0 w-full h-full object-cover" crossorigin="anonymous">
                        
                        <!-- CTA Tag (Solid Theme Color) -->
                        <div class="absolute top-10 right-10 z-10 text-white px-7 py-3 rounded-full flex items-center justify-center text-[22px] font-bold shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white/20" 
                             style="background-color: <?php echo $primary_color; ?>;">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                            বিস্তারিত কমেন্টে
                        </div>

                        <!-- Breaking Ribbon (Bottom Left of Image) -->
                        <?php if($post['is_breaking']): ?>
                        <div class="absolute bottom-6 left-8 text-white px-6 py-2.5 rounded shadow-lg flex items-center text-[22px] font-bold uppercase tracking-widest z-10" style="background-color: <?php echo $primary_color; ?>;">
                            <span class="w-3 h-3 bg-white rounded-full animate-pulse mr-3"></span> ব্রেকিং
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Theme Divider -->
                    <div class="w-full h-3" style="background-color: <?php echo $primary_color; ?>;"></div>

                    <!-- Bottom Content Area -->
                    <div class="flex-1 w-full bg-white p-12 flex flex-col justify-between">
                        
                        <!-- Title -->
                        <h1 class="text-gray-900 font-extrabold leading-[1.35] tracking-tight border-l-[8px] pl-6" style="font-size: 54px; border-color: <?php echo $primary_color; ?>;">
                            <?php echo escape($post['title']); ?>
                        </h1>
                        
                        <!-- Footer bar -->
                        <div class="flex items-center justify-between mt-6 pt-6 border-t-[2px] border-gray-100">
                            <div class="flex items-center">
                                <!-- Logo -->
                                <?php if($site_logo): ?>
                                    <img src="<?php echo escape($site_logo); ?>" alt="Alokpat" class="h-14 object-contain" crossorigin="anonymous">
                                <?php else: ?>
                                    <h2 class="text-black text-4xl font-black tracking-tight" style="color: <?php echo $primary_color; ?>;">আলোকপাত</h2>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-3 text-gray-500">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                <span class="text-[28px] font-bold tracking-widest uppercase text-gray-700">alokpat.in</span>
                            </div>
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
