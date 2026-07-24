<?php
require_once '../config/config.php';
require_once '../helpers/functions.php';
require_once '../database/Database.php';
require_once '../models/Category.php';

requireAuth();

// Restrict access - only admins or editors usually need this
if (!hasAnyRole(['super_admin', 'admin', 'editor'])) {
    setFlash('error', 'আপনার এই পেজটি দেখার অনুমতি নেই।');
    redirect(ADMIN_URL . '/dashboard.php');
}

$page_title = 'RSS Feeds';

// Fetch all categories
$categoryModel = new Category();
$categories = $categoryModel->getAll();

$main_feed_url = rtrim(SITE_URL, '/') . '/feed.php';
?>

<?php require_once 'layouts/admin.php'; ?>

<!-- Top Action Bar -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-rss text-orange-500 mr-3"></i> 
            RSS Feeds (News Publisher)
        </h1>
        <p class="text-sm text-gray-500 mt-1">কপি করে Google News বা অন্যান্য প্ল্যাটফর্মে আপনার ফিড সাবমিট করুন</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Main Feed (প্রধান ফিড)</h2>
    <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
        <input type="text" readonly value="<?= $main_feed_url ?>" class="w-full bg-transparent border-none text-gray-700 font-mono focus:ring-0" id="mainFeedUrl">
        <button onclick="copyUrl('mainFeedUrl')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors whitespace-nowrap">
            <i class="fas fa-copy mr-1"></i> Copy
        </button>
        <a href="<?= $main_feed_url ?>" target="_blank" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors whitespace-nowrap">
            <i class="fas fa-external-link-alt mr-1"></i> Preview
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Category Feeds (ক্যাটাগরি অনুযায়ী ফিড)</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach($categories as $category): 
            $cat_url = $main_feed_url . '?category=' . urlencode($category['slug']);
            $inputId = 'cat_feed_' . $category['id'];
        ?>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <div class="font-bold text-gray-700 mb-2"><?= htmlspecialchars($category['name']) ?></div>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="<?= $cat_url ?>" class="w-full bg-white border border-gray-200 rounded px-3 py-1.5 text-sm text-gray-600 font-mono focus:ring-0" id="<?= $inputId ?>">
                <button onclick="copyUrl('<?= $inputId ?>')" class="p-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors" title="Copy URL">
                    <i class="fas fa-copy"></i>
                </button>
                <a href="<?= $cat_url ?>" target="_blank" class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition-colors" title="Preview Feed">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function copyUrl(inputId) {
    var copyText = document.getElementById(inputId);
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */
    
    navigator.clipboard.writeText(copyText.value).then(function() {
        showToast('URL Copied to clipboard!', 'success');
    }, function(err) {
        showToast('Failed to copy URL', 'error');
    });
}
</script>

<?php require_once 'layouts/admin_footer.php'; ?>
