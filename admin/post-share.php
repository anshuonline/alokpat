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

    <!-- Canvas Workspace -->
    <div class="bg-gray-50 rounded-2xl p-8 border border-gray-200 flex flex-col md:flex-row gap-8 items-start">
        
        <!-- Controls / Info -->
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
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-2">সংবাদের বিবরণ</h3>
                <div class="text-sm font-semibold text-gray-900 mt-2">শিরোনাম:</div>
                <div class="text-sm text-gray-600"><?php echo escape($post['title']); ?></div>
            </div>
            
            <div id="loadingStatus" class="hidden text-indigo-600 font-semibold items-center justify-center p-4 bg-indigo-50 rounded-lg">
                <i class="fas fa-spinner fa-spin mr-2"></i> ইমেজ রেন্ডার হচ্ছে, অপেক্ষা করুন...
            </div>
        </div>

        <!-- Inject Font URL dynamically -->
        <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">

        <!-- The Canvas Preview -->
        <!-- We use absolute pixel sizes to guarantee the output is 1080x1080, but scale it via CSS for viewing -->
        <div class="w-full md:w-2/3 flex justify-center bg-gray-200/50 rounded-xl p-4 overflow-hidden relative" style="min-height: 400px;">
            <div id="card-wrapper" style="width: 1080px; height: 1080px; transform-origin: top center; transform: scale(0.45); margin-bottom: -590px;" class="shadow-2xl flex-shrink-0 transition-transform">
                
                <!-- Actual Capture Node (Magazine Style Light Theme) -->
                <div id="social-card" class="relative w-full h-full bg-white flex flex-col" style="font-family: <?php echo SITE_FONT_CSS; ?>;">
                    
                    <!-- Top Image Area (55%) -->
                    <div class="relative w-full h-[55%]">
                        <img src="<?php echo escape($image_url); ?>" id="card-bg-image" class="absolute inset-0 w-full h-full object-cover" crossorigin="anonymous">
                        
                        <!-- Top Right Tag -->
                        <div class="absolute top-8 right-8 z-10 bg-black text-white px-6 py-3 text-2xl font-bold rounded flex items-center whitespace-nowrap">
                            বিস্তারিত প্রথম কমেন্টে
                            <svg class="w-6 h-6 ml-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>

                        <!-- Breaking Ribbon -->
                        <?php if($post['is_breaking']): ?>
                        <div class="absolute bottom-6 left-8 text-white px-6 py-2 rounded flex items-center text-2xl font-bold uppercase tracking-widest z-10" style="background-color: <?php echo $primary_color; ?>;">
                            <span class="w-3.5 h-3.5 bg-white rounded-full animate-pulse mr-3"></span> ব্রেকিং
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Brand Lines -->
                    <div class="w-full h-3" style="background-color: <?php echo $primary_color; ?>;"></div>
                    <div class="w-full h-1 bg-black"></div>

                    <!-- Bottom Content Area -->
                    <div class="flex-1 w-full bg-white p-12 flex flex-col justify-between">
                        
                        <!-- Title -->
                        <h1 class="text-[#111] font-extrabold leading-[1.35] tracking-tight border-l-[12px] pl-6" style="font-size: 58px; border-color: <?php echo $primary_color; ?>;">
                            <?php echo escape($post['title']); ?>
                        </h1>
                        
                        <!-- Footer bar -->
                        <div class="flex items-center justify-between mt-8 pt-8 border-t-[3px] border-gray-200">
                            <div class="flex items-center">
                                <!-- Logo -->
                                <?php if($site_logo): ?>
                                    <img src="<?php echo escape($site_logo); ?>" alt="Alokpat" class="h-16 object-contain" crossorigin="anonymous">
                                <?php else: ?>
                                    <h2 class="text-black text-5xl font-black tracking-tight">আলোকপাত</h2>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-3 text-gray-500">
                                <!-- Globe SVG -->
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-3xl font-bold tracking-widest uppercase text-gray-800">alokpat.in</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Capture Node -->

            </div>
        </div>
    </div>
</div>

<!-- Load html-to-image (Most modern and bug-free canvas renderer) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
<script>
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
            htmlToImage.toJpeg(node, {
                quality: 0.95,
                backgroundColor: '#ffffff',
                width: 1080,
                height: 1080,
                style: {
                    transform: 'scale(1)',
                    transformOrigin: 'top left'
                }
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
        }, 300);
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
