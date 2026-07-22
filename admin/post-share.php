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
                
                <!-- Actual Capture Node (Premium Split Layout) -->
                <div id="social-card" class="relative w-full h-full bg-slate-900 overflow-hidden flex flex-col" style="font-family: 'Noto Sans Bengali', sans-serif;">
                    
                    <!-- Top Accent Line (Black/Dark Theme) -->
                    <div class="absolute top-0 left-0 w-full h-4 bg-gradient-to-r from-gray-900 via-gray-600 to-black z-50"></div>
                    
                    <!-- Top Image Section (Flex 1 to take remaining space, dynamically shrinking if title is long) -->
                    <div class="relative flex-1 w-full overflow-hidden bg-slate-800">
                        <img src="<?php echo escape($image_url); ?>" id="card-bg-image" class="absolute inset-0 w-full h-full object-cover" crossorigin="anonymous">
                        
                        <!-- Subtle gradient overlay at the very bottom of the image to blend smoothly -->
                        <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>
                        
                        <!-- Top Right Corner "Read More" -->
                        <div class="absolute top-12 right-12 z-20 flex space-x-4">
                            <div class="bg-black/70 backdrop-blur-md text-white px-8 py-3 rounded-full text-3xl font-bold shadow-2xl border border-white/20 flex items-center">
                                বিস্তারিত পড়ুন <i class="fas fa-arrow-right ml-3 text-2xl"></i>
                            </div>
                        </div>

                        <!-- Top Ribbon / Category -->
                        <div class="absolute bottom-8 left-12 flex space-x-4 z-10">
                            <?php if($post['is_breaking']): ?>
                                <div class="bg-red-600 text-white px-8 py-3 rounded-md flex items-center text-3xl font-bold uppercase tracking-widest shadow-2xl">
                                    <span class="w-4 h-4 bg-white rounded-full animate-pulse mr-4"></span> ব্রেকিং
                                </div>
                            <?php endif; ?>
                            <div class="bg-indigo-600 text-white px-8 py-3 rounded-md text-3xl font-bold shadow-2xl">
                                বিস্তারিত প্রথম কমেন্টে
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bottom Content Section (Auto height) -->
                    <div class="w-full bg-slate-900 flex flex-col p-12 pt-8 relative shrink-0">
                        
                        <!-- Title (Full text, no ellipsis) -->
                        <h1 class="text-white font-bold leading-tight drop-shadow-sm mb-10" style="font-size: 56px;">
                            <?php echo escape($post['title']); ?>
                        </h1>
                        
                        <!-- Footer bar -->
                        <div class="flex items-center justify-between border-t border-slate-700/80 pt-8">
                            <div class="flex items-center">
                                <!-- Logo only (Removed left text) -->
                                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" onerror="this.style.display='none'" alt="Alokpat" class="h-16 object-contain" crossorigin="anonymous">
                            </div>
                            <div class="flex items-center space-x-4 text-slate-300">
                                <i class="fas fa-globe text-4xl opacity-80"></i>
                                <span class="text-4xl font-semibold tracking-widest uppercase text-slate-200">alokpat.in</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Capture Node -->

            </div>
        </div>
    </div>
</div>

<!-- Load html2canvas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        const originalText = btn.innerHTML;
        
        // Convert local images to base64 or ensure crossorigin works.
        // html2canvas handles it usually if crossorigin is set, but sometimes proxy is needed.
        // We enabled allowTaint and useCORS.
        
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        loader.classList.remove('hidden');
        loader.classList.add('flex');
        
        // Wait a tiny bit for the UI to update the loading state
        setTimeout(() => {
            html2canvas(node, {
                scale: 1, // We already use 1080px base size, so scale 1 is enough for high-res
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#0a0a0a',
                logging: false
            }).then(function(canvas) {
                // Restore UI
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                loader.classList.add('hidden');
                loader.classList.remove('flex');
                
                // Create download link
                const link = document.createElement('a');
                link.download = 'alokpat-post-' + <?php echo $post_id; ?> + '.jpg';
                // Use JPEG for smaller file size, quality 0.9
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
                
            }).catch(function(err) {
                alert('ইমেজ জেনারেট করতে সমস্যা হয়েছে: ' + err.message);
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
