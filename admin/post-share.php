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
            <button id="downloadBtn" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200 flex items-center">
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

        <!-- The Canvas Preview -->
        <!-- We use absolute pixel sizes to guarantee the output is 1080x1080, but scale it via CSS for viewing -->
        <div class="w-full md:w-2/3 flex justify-center bg-gray-200/50 rounded-xl p-4 overflow-hidden relative" style="min-height: 400px;">
            <div id="card-wrapper" style="width: 1080px; height: 1080px; transform-origin: top center; transform: scale(0.45); margin-bottom: -590px;" class="shadow-2xl flex-shrink-0 transition-transform">
                
                <!-- Actual Capture Node (Jaw-dropping Dark Premium Layout) -->
                <div id="social-card" class="relative w-full h-full bg-[#050B14] overflow-hidden flex flex-col" style="font-family: 'Noto Sans Bengali', sans-serif;">
                    
                    <!-- Background Image -->
                    <img src="<?php echo escape($image_url); ?>" id="card-bg-image" class="absolute inset-0 w-full h-full object-cover opacity-[0.85]" crossorigin="anonymous">
                    
                    <!-- Deep Gradient Overlay (Smooth blend from bottom) -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#050B14] via-[#050B14]/80 to-transparent"></div>
                    <!-- Extra gradient layer for text readability -->
                    <div class="absolute inset-x-0 bottom-0 h-[60%] bg-gradient-to-t from-[#050B14] via-[#050B14]/40 to-transparent"></div>
                    
                    <!-- Top Right Corner "Read More" -->
                    <div class="absolute top-10 right-10 z-20">
                        <div class="bg-red-600 text-white px-6 py-2.5 rounded-lg text-2xl font-bold shadow-2xl flex items-center tracking-wide border border-red-500/50">
                            বিস্তারিত প্রথম কমেন্টে 
                            <!-- Inline SVG for arrow (fixes canvas font rendering bug) -->
                            <svg class="w-6 h-6 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>

                    <!-- Breaking Ribbon -->
                    <div class="absolute top-10 left-10 z-20">
                        <?php if($post['is_breaking']): ?>
                            <div class="bg-red-600 text-white px-6 py-2.5 rounded-lg flex items-center text-2xl font-bold uppercase tracking-widest shadow-2xl border border-red-500/50">
                                <span class="w-3.5 h-3.5 bg-white rounded-full animate-pulse mr-3"></span> ব্রেকিং
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bottom Content Section -->
                    <div class="absolute bottom-0 left-0 w-full p-12 z-30 flex flex-col justify-end">
                        
                        <!-- Title -->
                        <h1 class="text-white font-extrabold leading-[1.35] drop-shadow-2xl mb-10" style="font-size: 64px; text-shadow: 0px 4px 15px rgba(0,0,0,0.8);">
                            <?php echo escape($post['title']); ?>
                        </h1>
                        
                        <!-- Footer bar -->
                        <div class="flex items-center justify-between border-t-[1.5px] border-white/20 pt-8">
                            <div class="flex items-center">
                                <!-- Logo with brightness-0 invert to make it white -->
                                <?php if($site_logo): ?>
                                    <img src="<?php echo escape($site_logo); ?>" alt="Alokpat" class="h-16 object-contain" style="filter: brightness(0) invert(1);" crossorigin="anonymous">
                                <?php else: ?>
                                    <h2 class="text-white text-5xl font-black tracking-tight">আলোকপাত</h2>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-3 text-gray-200">
                                <!-- Inline SVG for globe -->
                                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-3xl font-bold tracking-widest uppercase opacity-90">alokpat.in</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Capture Node -->

            </div>
        </div>
    </div>
</div>

<!-- Load dom-to-image-more (Fixes Bengali text rendering bugs) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image-more/3.1.6/dom-to-image-more.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Adjust scale based on screen width for preview
    function adjustScale() {
        const wrapper = document.getElementById('card-wrapper');
        const container = wrapper.parentElement;
        const containerWidth = container.clientWidth - 32; // minus padding
        
        if (containerWidth < 1080) {
            const scale = containerWidth / 1080;
            wrapper.style.transform = `scale(${scale})`;
            // Adjust margin bottom to fix layout height due to scaling
            const visualHeight = 1080 * scale;
            const negativeMargin = 1080 - visualHeight;
            wrapper.style.marginBottom = `-${negativeMargin}px`;
        } else {
            wrapper.style.transform = 'scale(1)';
            wrapper.style.marginBottom = '0px';
        }
    }
    
    window.addEventListener('resize', adjustScale);
    // Add small delay to ensure CSS is loaded
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
        
        // Wait a tiny bit for the UI to update the loading state
        setTimeout(() => {
            domtoimage.toJpeg(node, {
                quality: 0.95,
                bgcolor: '#ffffff',
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
