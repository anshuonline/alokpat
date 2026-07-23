<!-- Global Media Library Modal -->
<div id="mediaLibraryModal" class="hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
        
        <!-- Modal Header with Tabs -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-photo-video mr-2 text-blue-600"></i> মিডিয়া লাইব্রেরি</h3>
            
            <div class="flex space-x-2">
                <button type="button" onclick="switchMediaTab('library')" id="tabLibraryBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium text-sm transition shadow">
                    <i class="fas fa-images mr-1"></i> লাইব্রেরি
                </button>
                <button type="button" onclick="switchMediaTab('upload')" id="tabUploadBtn" class="px-4 py-2 bg-white text-gray-600 border border-gray-300 rounded-md font-medium text-sm hover:bg-gray-50 transition">
                    <i class="fas fa-cloud-upload-alt mr-1"></i> আপলোড করুন
                </button>
                <button type="button" onclick="closeMediaLibrary()" class="ml-4 text-gray-400 hover:text-red-600 transition text-xl px-2">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Library Tab Content -->
        <div class="flex-1 flex overflow-hidden bg-gray-100" id="mediaLibraryGridWrapper">
            <!-- Left Side: Grid -->
            <div class="flex-1 p-6 overflow-y-auto" id="mediaLibraryGrid">
                <div class="flex justify-center items-center h-full text-gray-500 hidden" id="mediaLibraryLoader">
                    <i class="fas fa-spinner fa-spin text-4xl"></i>
                    <span class="ml-3 text-lg font-medium">লোড হচ্ছে...</span>
                </div>
                <div id="mediaItemsContainer" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <!-- Media items injected via JS -->
                </div>
            </div>
            
            <!-- Right Side: Preview Sidebar -->
            <div class="w-80 bg-white border-l border-gray-200 p-6 overflow-y-auto hidden flex-col" id="mediaSidebarPreview">
                <h4 class="font-bold text-gray-700 mb-4 border-b pb-2">প্রিভিউ (Preview)</h4>
                <div class="bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center mb-4 min-h-[200px] border border-gray-200">
                    <img id="mediaSidebarImg" src="" alt="" class="max-w-full max-h-[250px] object-contain">
                </div>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs font-semibold uppercase">ফাইলের নাম</span>
                        <div id="mediaSidebarFilename" class="text-gray-800 break-all font-medium"></div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 space-y-2">
                        <a href="#" id="mediaSidebarFullLink" target="_blank" class="w-full text-center block px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition font-medium text-sm">
                            <i class="fas fa-external-link-alt mr-2"></i> ফুল সাইজ দেখুন
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText(selectedMediaUrl); alert('URL কপি করা হয়েছে!');" class="w-full text-center block px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-200 border border-gray-200 transition font-medium text-sm">
                            <i class="fas fa-copy mr-2"></i> URL কপি করুন
                        </button>
                        <?php if (hasPermission('manage_media')): ?>
                        <button type="button" onclick="deleteSelectedMedia()" class="w-full text-center block px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium text-sm mt-2">
                            <i class="fas fa-trash-alt mr-2"></i> ছবিটি ডিলিট করুন
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Tab Content -->
        <div class="flex-1 p-8 bg-gray-100 hidden flex flex-col justify-center items-center" id="mediaUploadSection">
            <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-md border-2 border-dashed border-gray-300 text-center">
                <i class="fas fa-cloud-upload-alt text-5xl text-blue-500 mb-4"></i>
                <h4 class="text-xl font-bold text-gray-700 mb-2">নতুন ছবি আপলোড করুন</h4>
                <p class="text-gray-500 mb-6 text-sm">Drag & drop অথবা নিচে ক্লিক করে নির্বাচন করুন</p>
                
                <form id="globalMediaUploadForm" onsubmit="event.preventDefault(); submitGlobalMediaUpload();">
                    <input type="file" id="globalMediaFileInput" name="files[]" accept="image/*" class="w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100 mb-4 cursor-pointer" multiple required>
                    <button type="submit" id="globalMediaUploadBtn" class="w-full py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i> আপলোড শুরু করুন
                    </button>
                </form>
                <div id="uploadStatusMsg" class="mt-4 text-sm hidden font-medium"></div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center" id="mediaModalFooter">
            <div class="text-sm text-gray-600" id="mediaLibraryStatus">
                কোনো ছবি নির্বাচন করা হয়নি
            </div>
            <div class="space-x-3">
                <button type="button" onclick="closeMediaLibrary()" class="px-5 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold transition">বাতিল</button>
                <button type="button" id="selectMediaBtn" onclick="confirmMediaSelection()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition opacity-50 cursor-not-allowed" disabled>নির্বাচন করুন</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global Media Library Logic
let selectedMediaUrl = '';
let selectedMediaId = null;
let mediaSelectionCallback = null;

/**
 * Open the media library modal
 * @param {Function} callback - Function to run when a media is selected, receives URL as arg
 */
function openMediaLibrary(callback = null) {
    mediaSelectionCallback = callback;
    document.getElementById('mediaLibraryModal').classList.remove('hidden');
    switchMediaTab('library');
    loadMediaLibrary();
}

function closeMediaLibrary() {
    document.getElementById('mediaLibraryModal').classList.add('hidden');
    // Reset selection in modal
    document.querySelectorAll('.media-item').forEach(el => el.classList.remove('ring-4', 'ring-blue-500', 'border-transparent'));
    selectedMediaUrl = '';
    
    // Hide sidebar
    const sidebar = document.getElementById('mediaSidebarPreview');
    if(sidebar) {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('flex');
    }
    
    const btn = document.getElementById('selectMediaBtn');
    if(btn) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    }
    
    const status = document.getElementById('mediaLibraryStatus');
    if(status) status.innerText = 'কোনো ছবি নির্বাচন করা হয়নি';
    
    // Clear upload form
    document.getElementById('globalMediaUploadForm').reset();
    document.getElementById('uploadStatusMsg').classList.add('hidden');
}

function switchMediaTab(tab) {
    const libGridWrapper = document.getElementById('mediaLibraryGridWrapper');
    const upSection = document.getElementById('mediaUploadSection');
    const footer = document.getElementById('mediaModalFooter');
    
    const tabLibBtn = document.getElementById('tabLibraryBtn');
    const tabUpBtn = document.getElementById('tabUploadBtn');
    
    if (tab === 'library') {
        libGridWrapper.classList.remove('hidden');
        libGridWrapper.classList.add('flex');
        upSection.classList.add('hidden');
        upSection.classList.remove('flex');
        if(footer) footer.classList.remove('hidden');
        
        tabLibBtn.className = "px-4 py-2 bg-blue-600 text-white rounded-md font-medium text-sm transition shadow";
        tabUpBtn.className = "px-4 py-2 bg-white text-gray-600 border border-gray-300 rounded-md font-medium text-sm hover:bg-gray-50 transition";
    } else {
        libGridWrapper.classList.add('hidden');
        libGridWrapper.classList.remove('flex');
        upSection.classList.remove('hidden');
        upSection.classList.add('flex');
        if(footer) footer.classList.add('hidden'); // Hide footer selection during upload
        
        tabUpBtn.className = "px-4 py-2 bg-blue-600 text-white rounded-md font-medium text-sm transition shadow";
        tabLibBtn.className = "px-4 py-2 bg-white text-gray-600 border border-gray-300 rounded-md font-medium text-sm hover:bg-gray-50 transition";
    }
}

function loadMediaLibrary() {
    const container = document.getElementById('mediaItemsContainer');
    const loader = document.getElementById('mediaLibraryLoader');
    
    container.innerHTML = '';
    container.classList.add('hidden');
    loader.classList.remove('hidden');
    
    fetch('<?php echo ADMIN_URL; ?>/ajax/get-media.php')
        .then(response => response.json())
        .then(res => {
            loader.classList.add('hidden');
            container.classList.remove('hidden');
            
            if (res.status === 'success' && res.data && res.data.length > 0) {
                res.data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'media-item relative aspect-square bg-gray-200 rounded-lg overflow-hidden border-2 border-gray-300 cursor-pointer hover:shadow-lg transition group';
                    div.onclick = () => selectMediaItem(div, item.file_url, item.original_filename || item.alt_text || item.filename, item.id);
                    
                    const img = document.createElement('img');
                    img.src = item.file_url;
                    img.className = 'w-full h-full object-cover group-hover:scale-105 transition duration-300';
                    img.loading = 'lazy';
                    
                    const overlay = document.createElement('div');
                    overlay.className = 'absolute inset-0 bg-blue-600 bg-opacity-0 group-hover:bg-opacity-10 transition duration-300';
                    
                    div.appendChild(img);
                    div.appendChild(overlay);
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500">কোনো মিডিয়া পাওয়া যায়নি। দয়া করে "আপলোড করুন" ট্যাবে গিয়ে ছবি আপলোড করুন।</div>';
            }
        })
        .catch(err => {
            console.error('Error fetching media:', err);
            loader.classList.add('hidden');
            container.classList.remove('hidden');
            container.innerHTML = '<div class="col-span-full text-center py-10 text-red-500">মিডিয়া লোড করতে সমস্যা হয়েছে</div>';
        });
}

function selectMediaItem(element, url, filename, id) {
    document.querySelectorAll('.media-item').forEach(el => el.classList.remove('ring-4', 'ring-blue-500', 'border-transparent'));
    
    element.classList.add('ring-4', 'ring-blue-500', 'border-transparent');
    selectedMediaUrl = url;
    selectedMediaId = id;
    
    // Update sidebar
    const sidebar = document.getElementById('mediaSidebarPreview');
    if(sidebar) {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('flex');
        document.getElementById('mediaSidebarImg').src = url;
        document.getElementById('mediaSidebarFilename').innerText = filename;
        document.getElementById('mediaSidebarFullLink').href = url;
    }
    
    const btn = document.getElementById('selectMediaBtn');
    if(btn) {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    const status = document.getElementById('mediaLibraryStatus');
    if(status) status.innerHTML = `নির্বাচিত: <span class="font-bold text-blue-700">${filename}</span>`;
}

function confirmMediaSelection() {
    if (selectedMediaUrl) {
        if (typeof mediaSelectionCallback === 'function') {
            mediaSelectionCallback(selectedMediaUrl);
        } else {
            // Fallback for post-edit/create if callback not provided explicitly
            const urlField = document.getElementById('featured_image_url');
            const previewImg = document.getElementById('featuredImagePreview');
            const previewContainer = document.getElementById('featuredImagePreviewContainer');
            
            if(urlField) urlField.value = selectedMediaUrl;
            if(previewImg) previewImg.src = selectedMediaUrl;
            if(previewContainer) previewContainer.classList.remove('hidden');
            
            const fileInput = document.getElementById('featured_image_input');
            if(fileInput) fileInput.value = '';
        }
        closeMediaLibrary();
    }
}

async function compressImageClientSide(file) {
    return new Promise((resolve) => {
        if (!file.type.startsWith('image/')) {
            resolve(file);
            return;
        }
        
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = event => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const MAX_WIDTH = 1200;
                let width = img.width;
                let height = img.height;
                
                if (width > MAX_WIDTH) {
                    height = Math.floor(height * (MAX_WIDTH / width));
                    width = MAX_WIDTH;
                }
                
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                let quality = 0.8;
                const targetSize = 100 * 1024; // 100kb
                
                const tryCompress = (q) => {
                    canvas.toBlob(blob => {
                        if (blob.size <= targetSize || q <= 0.2) {
                            const newFileName = file.name.replace(/\.[^/.]+$/, "") + ".webp";
                            // Create a file object with the original size attached as a custom property
                            const newFile = new File([blob], newFileName, { type: 'image/webp' });
                            // We can append original size to formData later, or let the server use the new file size.
                            // Client-side compressed file size is the new 'original' for the server.
                            resolve({ file: newFile, originalSize: file.size });
                        } else {
                            tryCompress(q - 0.2);
                        }
                    }, 'image/webp', q);
                };
                tryCompress(quality);
            };
        };
    });
}

async function submitGlobalMediaUpload() {
    const fileInput = document.getElementById('globalMediaFileInput');
    if(fileInput.files.length === 0) return;
    
    const btn = document.getElementById('globalMediaUploadBtn');
    const originalText = btn.innerHTML;
    const statusMsg = document.getElementById('uploadStatusMsg');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> অপ্টিমাইজ করা হচ্ছে...';
    btn.classList.add('opacity-70');
    
    statusMsg.classList.remove('hidden', 'text-green-600', 'text-red-600');
    statusMsg.classList.add('text-blue-600');
    statusMsg.innerText = 'আপনার ব্রাউজারেই ছবি অপ্টিমাইজ হচ্ছে, একটু অপেক্ষা করুন...';
    
    const formData = new FormData();
    for(let i=0; i<fileInput.files.length; i++) {
        let result = await compressImageClientSide(fileInput.files[i]);
        if(result.file) {
            formData.append('files[]', result.file);
            // We append a custom field for the true original size before browser compression
            formData.append('client_original_sizes[]', result.originalSize);
        } else {
            formData.append('files[]', result); // fallback for non-images
            formData.append('client_original_sizes[]', result.size);
        }
    }
    
    statusMsg.innerText = 'সার্ভারে আপলোড করা হচ্ছে...';
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> আপলোড হচ্ছে...';
    
    fetch('<?php echo ADMIN_URL; ?>/ajax/upload-media.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            statusMsg.classList.remove('text-blue-600');
            statusMsg.classList.add('text-green-600');
            
            let origKB = data.data.original_size ? Math.round(data.data.original_size / 1024) : 'N/A';
            let compKB = data.data.file_size ? Math.round(data.data.file_size / 1024) : 'N/A';
            statusMsg.innerHTML = data.message + '<br><span class="text-xs text-gray-500 block mt-1">Size before: ' + origKB + 'KB &rarr; Compressed: ' + compKB + 'KB</span>';
            
            // Reset button immediately
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.classList.remove('opacity-70');
            
            // Switch back to library and select the newly uploaded image quickly
            setTimeout(() => {
                fileInput.value = '';
                switchMediaTab('library');
                loadMediaLibrary(); 
                // Note: it will appear first in the grid due to ordering.
                statusMsg.innerHTML = '';
                statusMsg.classList.add('hidden');
            }, 500);
        } else {
            throw new Error(data.message || 'আপলোডে সমস্যা হয়েছে');
        }
    })
    .catch(err => {
        statusMsg.classList.remove('text-blue-600');
        statusMsg.classList.add('text-red-600');
        statusMsg.innerText = err.message || 'আপলোডে সমস্যা হয়েছে';
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.classList.remove('opacity-70');
    });
}

function deleteSelectedMedia() {
    if(!selectedMediaId) return;
    if(!confirm('আপনি কি নিশ্চিত যে আপনি এই ছবিটি ডিলিট করতে চান?')) return;
    
    fetch('<?php echo ADMIN_URL; ?>/ajax/delete-media.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: selectedMediaId })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert('ছবিটি সফলভাবে ডিলিট করা হয়েছে!');
            document.getElementById('mediaSidebarPreview').classList.add('hidden');
            document.getElementById('mediaSidebarPreview').classList.remove('flex');
            selectedMediaUrl = '';
            selectedMediaId = null;
            loadMediaLibrary();
        } else {
            alert(data.message || 'ডিলিট করতে সমস্যা হয়েছে');
        }
    })
    .catch(err => {
        console.error(err);
        alert('সার্ভার এরর!');
    });
}
</script>
