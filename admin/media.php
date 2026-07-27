<?php
/**
 * Admin Media Manager
 */
require_once '../config/config.php';
requireAuth();

requirePermission('manage_media');
$mediaModel = new Media();

// Handle Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

$mediaList = $mediaModel->getAll($limit, $offset);
$totalMedia = $mediaModel->getCount();
$totalPages = ceil($totalMedia / $limit);

$page_title = 'মিডিয়া ম্যানেজার (Media Manager)';
ob_start();
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">মিডিয়া ম্যানেজার</h2>
            <p class="text-sm text-gray-500">আপনার ওয়েবসাইটের সমস্ত ছবি এবং মিডিয়া পরিচালনা করুন</p>
        </div>
        
        <button type="button" onclick="openMediaLibrary(() => location.reload())" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-md transition flex items-center">
            <i class="fas fa-cloud-upload-alt mr-2"></i> নতুন আপলোড করুন
        </button>
    </div>

    <!-- Media Grid -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <?php if (empty($mediaList)): ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-medium text-gray-600">কোনো মিডিয়া পাওয়া যায়নি</h3>
                <p class="text-gray-500 mt-2">উপরের "নতুন আপলোড করুন" বাটনে ক্লিক করে ছবি আপলোড করুন</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($mediaList as $item): ?>
                    <div class="media-card group relative bg-gray-100 rounded-lg overflow-hidden border border-gray-200 hover:shadow-lg transition flex flex-col" id="media-card-<?php echo $item['id']; ?>">
                        
                        <!-- Image thumbnail -->
                        <div class="aspect-square relative overflow-hidden bg-gray-200 cursor-pointer" onclick="previewMediaFullscreen('<?php echo escape($item['file_url']); ?>')">
                            <img src="<?php echo escape($item['file_url']); ?>" alt="<?php echo escape($item['alt_text'] ?? $item['filename']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-news.jpg'; this.onerror=null;">
                        </div>
                        
                        <!-- Actions Overlay -->
                        <div class="absolute inset-x-0 top-0 p-2 flex justify-end opacity-0 group-hover:opacity-100 transition-opacity bg-gradient-to-b from-black/50 to-transparent pointer-events-none">
                            <button onclick="deleteMedia(<?php echo $item['id']; ?>)" class="text-white hover:text-red-400 p-1 pointer-events-auto" title="মুছে ফেলুন">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        
                        <!-- Details -->
                        <div class="p-3 bg-white flex-1 flex flex-col justify-between">
                            <div class="truncate text-xs font-semibold text-gray-700 mb-1" title="<?php echo escape($item['alt_text'] ?? $item['original_filename'] ?? $item['filename']); ?>" id="media-name-<?php echo $item['id']; ?>">
                                <?php echo escape($item['alt_text'] ?? $item['original_filename'] ?? $item['filename']); ?>
                            </div>
                            <div class="text-[10px] text-gray-500 font-medium">
                                <?php 
                                $size = $item['file_size'] ?? 0;
                                echo $size > 1048576 ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 1) . ' KB';
                                ?>
                            </div>
                            
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                                <button onclick="renameMedia(<?php echo $item['id']; ?>, '<?php echo escape($item['alt_text'] ?? $item['original_filename'] ?? $item['filename']); ?>')" class="text-xs text-blue-600 hover:text-blue-800" title="নাম পরিবর্তন">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="copyMediaUrl('<?php echo escape($item['file_url']); ?>')" class="text-xs text-gray-500 hover:text-gray-800 flex items-center" title="URL কপি করুন">
                                    <i class="fas fa-copy mr-1"></i> কপি
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-1">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded-lg border <?php echo $page === $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Fullscreen Lightbox -->
<div id="mediaLightbox" class="hidden fixed inset-0 bg-black bg-opacity-90 z-[60] flex items-center justify-center p-4 backdrop-blur-sm transition-opacity" onclick="closeLightbox()">
    <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white hover:text-red-400 text-3xl focus:outline-none transition-transform hover:scale-110">
        <i class="fas fa-times"></i>
    </button>
    <img id="mediaLightboxImg" src="" alt="Preview" class="max-w-full max-h-full object-contain shadow-2xl rounded" onclick="event.stopPropagation()">
</div>

<script>
function previewMediaFullscreen(url) {
    document.getElementById('mediaLightboxImg').src = url;
    document.getElementById('mediaLightbox').classList.remove('hidden');
}

function closeLightbox() {
    document.getElementById('mediaLightbox').classList.add('hidden');
    // Clear src slightly after to avoid flicker
    setTimeout(() => {
        document.getElementById('mediaLightboxImg').src = '';
    }, 200);
}

function copyMediaUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('URL কপি করা হয়েছে: ' + url);
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

function renameMedia(id, currentName) {
    const newName = prompt("নতুন নাম (Alt Text) দিন:", currentName);
    if (newName && newName !== currentName) {
        fetch('<?php echo ADMIN_URL; ?>/ajax/rename-media.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, alt_text: newName })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('media-name-' + id).innerText = newName;
                document.getElementById('media-name-' + id).title = newName;
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('সার্ভার ত্রুটি!'));
    }
}

function deleteMedia(id) {
    if (confirm("আপনি কি নিশ্চিত যে এই ছবিটি মুছতে চান? এটি মুছে ফেললে যে পোস্টগুলোতে এই ছবি আছে, সেখান থেকেও মুছে যাবে!")) {
        fetch('<?php echo ADMIN_URL; ?>/ajax/delete-media.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('media-card-' + id).remove();
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('সার্ভার ত্রুটি!'));
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
