<?php
/**
 * Edit Post Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    setFlash('error', 'কোনো পোস্ট নির্বাচন করা হয়নি');
    redirect(ADMIN_URL . '/posts.php');
}

$post_model = new Post();
$category = new Category();
$tag = new Tag();
$media = new Media();

// Get post data
$post = $post_model->getById($post_id);

if (!$post) {
    setFlash('error', 'পোস্ট পাওয়া যায়নি');
    redirect(ADMIN_URL . '/posts.php');
}

// Get categories and tags for dropdown
$categories = $category->getAll();
$all_tags = $tag->getAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Keep HTML tags for rich text
    $status = sanitize($_POST['status'] ?? $post['status']);
    
    // Generate slug if changed
    if (!empty($_POST['slug'])) {
        $slug = generateSlug($_POST['slug']);
    } else {
        $slug = generateSlug($title);
    }
    
    // Validate required fields
    $errors = [];
    if (empty($title)) {
        $errors[] = 'শিরোনাম প্রয়োজন';
    }
    if (empty($content)) {
        $errors[] = 'বিষয়বস্তু প্রয়োজন';
    }
    
    if (empty($errors)) {
        $processed_tags = [];
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            $db = (new Database())->getConnection();
            foreach ($_POST['tags'] as $tag_val) {
                if (!is_numeric($tag_val) && trim($tag_val) !== '') {
                    // New tag created dynamically
                    $tag_name = trim($tag_val);
                    $tag_slug = generateSlug($tag_name);
                    
                    // Check if exists
                    $check = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                    $check->execute([$tag_slug]);
                    if ($check->rowCount() > 0) {
                        $processed_tags[] = $check->fetchColumn();
                    } else {
                        // Insert new tag
                        $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                        if ($stmt->execute([$tag_name, $tag_slug])) {
                            $processed_tags[] = $db->lastInsertId();
                        }
                    }
                } else {
                    $processed_tags[] = (int)$tag_val;
                }
            }
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => sanitize($_POST['excerpt'] ?? ''),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'status' => $status,
            'is_featured' => isset($_POST['is_featured']),
            'is_breaking' => isset($_POST['is_breaking']),
            'is_trending' => isset($_POST['is_trending']),
            'seo_title' => sanitize($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize($_POST['seo_description'] ?? ''),
            'seo_keywords' => sanitize($_POST['seo_keywords'] ?? ''),
            'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
            'meta_og_title' => sanitize($_POST['meta_og_title'] ?? ''),
            'meta_og_description' => sanitize($_POST['meta_og_description'] ?? ''),
            'meta_og_image' => sanitize($_POST['meta_og_image'] ?? ''),
            'meta_twitter_card' => sanitize($_POST['meta_twitter_card'] ?? 'summary_large_image'),
            'robots_meta' => sanitize($_POST['robots_meta'] ?? 'index,follow'),
            'tags' => $processed_tags,
        ];
        
        // Handle featured image (either from URL via Media Chooser or new upload)
        if (!empty($_POST['featured_image_url'])) {
            $data['featured_image'] = sanitize($_POST['featured_image_url']);
            $data['featured_image_alt'] = sanitize($_POST['featured_image_alt'] ?? $title);
        } elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['featured_image'], 'uploads/posts');
            
            if (isset($upload_result['error'])) {
                $errors[] = $upload_result['error'];
            } else {
                $data['featured_image'] = $upload_result['file_url'];
                $data['featured_image_alt'] = sanitize($_POST['featured_image_alt'] ?? $title);
                
                // Add to media library
                $media_data = [
                    'filename' => $upload_result['filename'],
                    'original_filename' => $_FILES['featured_image']['name'],
                    'file_path' => $upload_result['filepath'],
                    'file_url' => $upload_result['file_url'],
                    'file_type' => pathinfo($upload_result['filename'], PATHINFO_EXTENSION),
                    'file_size' => $upload_result['file_size'],
                    'mime_type' => $upload_result['mime_type'],
                    'alt_text' => $data['featured_image_alt'],
                    'uploaded_by' => getCurrentUser()['id']
                ];
                $media->create($media_data);
            }
        }
        
        // Set publish time if changing to published
        if ($status === 'published' && $post['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        if (empty($errors)) {
            if ($post_model->update($post_id, $data)) {
                setFlash('success', 'সংবাদ সফলভাবে আপডেট হয়েছে');
                
                redirect(ADMIN_URL . '/post-edit.php?id=' . $post_id);
            } else {
                $errors[] = 'সংবাদ আপডেটে সমস্যা হয়েছে';
            }
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = $post;
}

$page_title = 'সংবাদ সম্পাদনা করুন';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit text-blue-600 mr-2"></i>
            সংবাদ সম্পাদনা করুন
        </h2>
        <div class="flex space-x-3">
            <a href="<?php echo url_for_post($post); ?>" 
                target="_blank" 
               class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                <i class="fas fa-eye mr-2"></i>
                পূর্বরূপ দেখুন
            </a>
            <button type="submit" form="postEditForm" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow">
                <i class="fas fa-save mr-2"></i>
                আপডেট করুন
            </button>
            <a href="<?php echo ADMIN_URL; ?>/posts.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                ফিরে যান
            </a>
        </div>
    </div>
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo escape($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Status Badge -->
    <div class="flex items-center space-x-3">
        <?php
        $status_classes = [
            'published' => 'bg-green-100 text-green-800',
            'draft' => 'bg-yellow-100 text-yellow-800',
            'scheduled' => 'bg-blue-100 text-blue-800',
            'archived' => 'bg-gray-100 text-gray-800'
        ];
        $status_labels = [
            'published' => 'প্রকাশিত',
            'draft' => 'খসড়া',
            'scheduled' => 'নির্ধারিত',
            'archived' => 'সংরক্ষিত'
        ];
        ?>
        <span class="px-4 py-2 text-sm font-semibold rounded-full <?php echo $status_classes[$post['status']]; ?>">
            <?php echo $status_labels[$post['status']]; ?>
        </span>
        <span class="text-gray-500 text-sm">
            <i class="fas fa-calendar mr-1"></i>
            তৈরি: <?php echo formatDateBengali($post['created_at']); ?>
        </span>
    </div>
    
    <!-- Form -->
    <form id="postEditForm" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Title -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <label class="block text-lg font-semibold text-gray-800 mb-2">
                        শিরোনাম <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="title" 
                           required
                           value="<?php echo isset($_POST['title']) ? escape($_POST['title']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="সংবাদের শিরোনাম লিখুন...">
                </div>
                
                <!-- Slug -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Slug
                    </label>
                    <input type="text" 
                           name="slug"
                           value="<?php echo isset($_POST['slug']) ? escape($_POST['slug']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="auto-generated-from-title">
                    
                    <div class="mt-4 p-3 bg-gray-50 rounded border border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-700 break-all mr-4 font-mono">
                            <span id="fullUrlText"><?php echo url_for_post($post); ?></span>
                        </div>
                        <button type="button" onclick="copyFullUrl()" class="px-3 py-1.5 bg-white text-gray-700 rounded border border-gray-300 hover:bg-gray-100 text-sm font-medium whitespace-nowrap transition shadow-sm">
                            <i class="fas fa-copy mr-1"></i> কপি
                        </button>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <label class="block text-lg font-semibold text-gray-800 mb-2">
                        বিষয়বস্তু <span class="text-red-500">*</span>
                    </label>
                        <textarea name="content" 
                              rows="20"
                              id="contentEditor"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="সংবাদের বিস্তারিত লিখুন..."><?php echo isset($post['content']) ? htmlspecialchars($post['content']) : (isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''); ?></textarea>
                    <?php if (isset($post['content'])): ?>
                        <!-- DEBUG: Content length = <?php echo strlen($post['content']); ?> bytes -->
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1">HTML ট্যাগ ব্যবহার করতে পারবেন বা টুলবার ব্যবহার করুন</p>
                </div>
                
                <!-- Excerpt -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        সারাংশ (Excerpt)
                    </label>
                    <textarea name="excerpt" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="সংবাদের সংক্ষিপ্ত সারাংশ..."><?php echo isset($_POST['excerpt']) ? escape($_POST['excerpt']) : ''; ?></textarea>
                </div>
                
            </div>
            
            <!-- Sidebar with Tabs -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md overflow-hidden sticky top-6">
                    <!-- Tab Headers -->
                    <div class="flex border-b border-gray-200">
                        <button type="button" onclick="switchTab('settings')" id="tab-btn-settings" class="flex-1 py-3 px-2 text-sm font-bold text-center border-b-2 border-blue-600 text-blue-600 bg-gray-50 focus:outline-none transition-colors">
                            <i class="fas fa-cog mr-1"></i> সেটিংস
                        </button>
                        <button type="button" onclick="switchTab('media')" id="tab-btn-media" class="flex-1 py-3 px-2 text-sm font-bold text-center border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                            <i class="fas fa-image mr-1"></i> মিডিয়া
                        </button>
                    </div>
                    
                    <div class="p-5 max-h-[calc(100vh-150px)] overflow-y-auto">
                        <!-- Tab Content: Settings -->
                        <div id="tab-content-settings" class="space-y-6 block">
                            
                            <!-- Publish Options -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 mb-3 border-b pb-2">
                                    <i class="fas fa-paper-plane mr-1 text-gray-500"></i> প্রকাশের ধরন
                                </h3>
                                
                                <?php 
                                $current_status = 'draft';
                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
                                    $current_status = $_POST['status'];
                                } elseif (isset($post['status'])) {
                                    $current_status = $post['status'];
                                }
                                ?>
                                <div class="space-y-2">
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="radio" 
                                               name="status" 
                                               value="draft" 
                                               onchange="toggleScheduleInput()"
                                               <?php echo ($current_status === 'draft') ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-blue-600">
                                        <span class="text-sm">খসড়া (Draft)</span>
                                    </label>
                                    
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="radio" 
                                               name="status" 
                                               value="published" 
                                               onchange="toggleScheduleInput()"
                                               <?php echo ($current_status === 'published') ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-blue-600">
                                        <span class="text-sm">প্রকাশিত (Published)</span>
                                    </label>
                                    
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="radio" 
                                               name="status" 
                                               value="scheduled" 
                                               onchange="toggleScheduleInput()"
                                               <?php echo ($current_status === 'scheduled') ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-blue-600">
                                        <span class="text-sm font-semibold">⏰ শিডিউল (Scheduled)</span>
                                    </label>
                                </div>
                                
                                <?php 
                                $published_at_val = '';
                                if (isset($_POST['published_at'])) {
                                    $published_at_val = $_POST['published_at'];
                                } elseif (!empty($post['published_at'])) {
                                    $published_at_val = date('Y-m-d\TH:i', strtotime($post['published_at']));
                                }
                                ?>
                                <div id="scheduleTimeContainer" class="mt-3 p-3 bg-gray-50 rounded border <?php echo ($current_status === 'scheduled') ? 'block' : 'hidden'; ?>">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">প্রকাশের সময় নির্ধারণ করুন:</label>
                                    <input type="datetime-local" name="published_at" value="<?php echo $published_at_val; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-2 border-b pb-2">
                                    <i class="fas fa-folder-open mr-1 text-gray-500"></i> ক্যাটাগরি
                                </label>
                                <select name="category_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="">-- নির্বাচন করুন --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : (isset($post['category_id']) && $post['category_id'] == $cat['id'] ? 'selected' : ''); ?>>
                                            <?php echo escape($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-2 border-b pb-2">
                                    <i class="fas fa-tags mr-1 text-gray-500"></i> ট্যাগ (Tags)
                                </label>
                                <?php
                                $selected_tag_ids = [];
                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tags'])) {
                                    $selected_tag_ids = $_POST['tags'];
                                } elseif (isset($post['tags']) && is_array($post['tags'])) {
                                    $selected_tag_ids = array_column($post['tags'], 'id');
                                }
                                ?>
                                <select name="tags[]" id="postTags" multiple="multiple" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <?php foreach ($all_tags as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo in_array($t['id'], $selected_tag_ids) ? 'selected' : ''; ?>>
                                            <?php echo escape($t['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">নতুন ট্যাগ লিখতে পারেন এবং এন্টার চাপুন</p>
                            </div>

                        </div>
                        
                        <!-- Tab Content: Media & Flags -->
                        <div id="tab-content-media" class="space-y-6 hidden">
                            
                            <!-- Featured Image -->
                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-2 border-b pb-2">
                                    <i class="fas fa-image mr-1 text-gray-500"></i> ফিচার্ড ইমেজ
                                </label>
                                
                                <div id="featuredImagePreviewContainer" class="<?php echo empty($post['featured_image']) ? 'hidden' : ''; ?> mb-3 relative rounded overflow-hidden border border-gray-200 bg-gray-50">
                                    <img id="featuredImagePreview" src="<?php echo !empty($post['featured_image']) ? escape($post['featured_image']) : ''; ?>" alt="Preview" class="w-full h-auto object-cover max-h-48">
                                    <button type="button" onclick="removeFeaturedImage()" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-7 h-7 flex items-center justify-center hover:bg-red-700 transition shadow-md" title="Remove Image">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>

                                <button type="button" onclick="openMediaLibrary()" class="w-full py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition flex items-center justify-center font-semibold text-sm mb-3">
                                    <i class="fas fa-cloud-upload-alt mr-2"></i> ইমেজ বেছে নিন
                                </button>
                                
                                <input type="hidden" name="featured_image_url" id="featured_image_url" value="<?php echo !empty($post['featured_image']) ? escape($post['featured_image']) : ''; ?>">
                                
                                <div>
                                    <input type="text" 
                                           name="featured_image_alt" 
                                           placeholder="Alt text (SEO)"
                                           value="<?php echo isset($_POST['featured_image_alt']) ? escape($_POST['featured_image_alt']) : ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                            </div>
                            
                            <!-- Flags -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 mb-3 border-b pb-2">
                                    <i class="fas fa-star mr-1 text-gray-500"></i> বিশেষ চিহ্ন
                                </h3>
                                
                                <?php
                                $is_featured_chk = (isset($_POST['is_featured']) && $_POST['is_featured']) || (!isset($_POST['is_featured']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($post['is_featured']));
                                $is_breaking_chk = (isset($_POST['is_breaking']) && $_POST['is_breaking']) || (!isset($_POST['is_breaking']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($post['is_breaking']));
                                $is_trending_chk = (isset($_POST['is_trending']) && $_POST['is_trending']) || (!isset($_POST['is_trending']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($post['is_trending']));
                                $is_live_chk = (isset($_POST['is_live']) && $_POST['is_live']) || (!isset($_POST['is_live']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($post['is_live']));
                                
                                $flags_expiry_val = '';
                                if (isset($_POST['flags_expiry'])) {
                                    $flags_expiry_val = $_POST['flags_expiry'];
                                } elseif (!empty($post['flags_expiry'])) {
                                    $flags_expiry_val = date('Y-m-d\TH:i', strtotime($post['flags_expiry']));
                                }
                                ?>
                                <div class="space-y-2">
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="checkbox" 
                                               name="is_featured" 
                                               value="1"
                                               <?php echo $is_featured_chk ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-blue-600 rounded">
                                        <span class="text-sm">⭐ ফিচার্ড</span>
                                    </label>
                                    
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="checkbox" 
                                               name="is_breaking" 
                                               value="1"
                                               <?php echo $is_breaking_chk ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-red-600 rounded">
                                        <span class="text-sm text-red-600 font-semibold">🔴 ব্রেকিং নিউজ</span>
                                    </label>
                                    
                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="checkbox" 
                                               name="is_trending" 
                                               value="1"
                                               <?php echo $is_trending_chk ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-orange-500 rounded">
                                        <span class="text-sm">🔥 ট্রেন্ডিং</span>
                                    </label>

                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="checkbox" 
                                               name="is_live" 
                                               value="1"
                                               <?php echo $is_live_chk ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-green-600 rounded">
                                        <span class="text-sm text-green-600 font-semibold">🔴 লাইভ (Live)</span>
                                    </label>
                                </div>
                                
                                <div class="mt-4 pt-3 border-t border-gray-200">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        <i class="far fa-clock mr-1 text-gray-500"></i> কতক্ষণ এই আইকনগুলো দেখাবে? (ঐচ্ছিক)
                                    </label>
                                    <input type="datetime-local" name="flags_expiry" value="<?php echo $flags_expiry_val; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <p class="text-[11px] text-gray-500 mt-1">খালি রাখলে আজীবন দেখাবে।</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- SEO Section -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-search text-green-600 mr-2"></i>
                এসইও (SEO) সেটিংস
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- SEO Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        SEO Title
                    </label>
                    <input type="text" 
                           name="seo_title"
                           value="<?php echo isset($_POST['seo_title']) ? escape($_POST['seo_title']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="Search engine title">
                    <p class="text-xs text-gray-500 mt-1">খালি থাকলে শিরোনাম ব্যবহার হবে</p>
                </div>
                
                <!-- SEO Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Description
                    </label>
                    <textarea name="seo_description" 
                              rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Search engine description"><?php echo isset($_POST['seo_description']) ? escape($_POST['seo_description']) : ''; ?></textarea>
                </div>
                
                <!-- SEO Keywords -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Focus Keywords
                    </label>
                    <input type="text" 
                           name="seo_keywords"
                           value="<?php echo isset($_POST['seo_keywords']) ? escape($_POST['seo_keywords']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="keyword1, keyword2, keyword3">
                </div>
                
                <!-- Canonical URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Canonical URL
                    </label>
                    <input type="text" 
                           name="canonical_url"
                           value="<?php echo isset($_POST['canonical_url']) ? escape($_POST['canonical_url']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="https://example.com/article">
                </div>
                
                <!-- OG Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Open Graph Title
                    </label>
                    <input type="text" 
                           name="meta_og_title"
                           value="<?php echo isset($_POST['meta_og_title']) ? escape($_POST['meta_og_title']) : ''; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="Facebook share title">
                </div>
                
                <!-- OG Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Open Graph Description
                    </label>
                    <textarea name="meta_og_description" 
                              rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="Facebook share description"><?php echo isset($_POST['meta_og_description']) ? escape($_POST['meta_og_description']) : ''; ?></textarea>
                </div>
                

                <!-- Robots Meta -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Robots Meta
                    </label>
                    <select name="robots_meta" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="index,follow" <?php echo (!isset($_POST['robots_meta']) || $_POST['robots_meta'] === 'index,follow') ? 'selected' : ''; ?>>
                            Index, Follow
                        </option>
                        <option value="index,nofollow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'index,nofollow') ? 'selected' : ''; ?>>
                            Index, Nofollow
                        </option>
                        <option value="noindex,follow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'noindex,follow') ? 'selected' : ''; ?>>
                            Noindex, Follow
                        </option>
                        <option value="noindex,nofollow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'noindex,nofollow') ? 'selected' : ''; ?>>
                            Noindex, Nofollow
                        </option>
                    </select>
                </div>
                
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex space-x-4">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i>
                আপডেট সংরক্ষণ করুন
            </button>
            <a href="<?php echo ADMIN_URL; ?>/posts.php" 
               class="px-8 py-4 bg-gray-200 rounded-lg font-bold text-lg hover:bg-gray-300 transition">
                বাতিল
            </a>
        </div>
        
    </form>
    
</div>

<script>
function removeFeaturedImage() {
    document.getElementById('featuredImagePreview').src = '';
    document.getElementById('featuredImagePreviewContainer').classList.add('hidden');
    document.getElementById('featured_image_url').value = '';
}

function copyFullUrl() {
    const url = document.getElementById('fullUrlText').innerText;
    navigator.clipboard.writeText(url).then(() => {
        alert('URL কপি করা হয়েছে!');
    });
}

function switchTab(tabName) {
    // Hide all tabs
    document.getElementById('tab-content-settings').classList.add('hidden');
    document.getElementById('tab-content-media').classList.add('hidden');
    
    // Reset all buttons
    const btns = ['settings', 'media'];
    btns.forEach(btn => {
        const el = document.getElementById('tab-btn-' + btn);
        el.classList.remove('border-blue-600', 'text-blue-600', 'bg-gray-50');
        el.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    
    // Active selected button
    const activeBtn = document.getElementById('tab-btn-' + tabName);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-blue-600', 'text-blue-600', 'bg-gray-50');
}

function toggleScheduleInput() {
    const status = document.querySelector('input[name="status"]:checked').value;
    const container = document.getElementById('scheduleTimeContainer');
    if (status === 'scheduled') {
        container.classList.remove('hidden');
        container.classList.add('block');
    } else {
        container.classList.remove('block');
        container.classList.add('hidden');
    }
}
</script>

<!-- TinyMCE Rich Text Editor -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#contentEditor',
        plugins: 'lists link table code image fullscreen accordion media',
        toolbar: 'blocks | bold italic underline | link customMediaBtn imgSizeBtn insertCtaBtn formatTab | alignleft aligncenter alignright | bullist numlist | blockquote table accordion | fullscreen | undo redo',
        height: 500,
        menubar: false,
        branding: false,
        promotion: false,
        image_advtab: true,
        image_dimensions: true,
        object_resizing: true,
        table_resize_bars: true,
        table_column_resizing: 'resizetable',
        resize: true,
        forced_root_block: 'p',
        remove_trailing_brs: true,
        custom_elements: 'figure,figcaption',
        extended_valid_elements: 'figure[class|style],figcaption[class|style],img[src|alt|width|height|style|class|loading],iframe[src|title|width|height|name|align|class|frameborder|allow|allowfullscreen|scrolling],script[src|async|defer|type|charset]',
        media_live_embeds: true,
        paste_data_images: true,
        automatic_uploads: true,
        images_upload_url: 'upload-editor-image.php?<?php echo CSRF_TOKEN_NAME; ?>=<?php echo generateCSRFToken(); ?>',
        images_upload_credentials: true,
        content_style: [
            ':root { --btn-primary: #2563eb; --btn-primary-hover: #1d4ed8; }',
            "body { font-family: <?php echo SITE_FONT_CSS; ?>; font-size: 16px; line-height: 1.8; color: #1f2937; padding: 16px; }",
            'p { margin: 0 0 1em 0; font-size: 20px; line-height: 1.9; }',
            'p:empty { display: none; }',
            'img { max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 4px; }',
            'figure { display: block; margin: 1.5em auto; text-align: center; clear: both; width: 100%; }',
            'figure img { display: inline-block; max-width: 100%; height: auto; border-radius: 4px; }',
            'figcaption { font-size: 0.85em; color: #6b7280; margin-top: 0.4em; font-style: italic; text-align: center; }',
            'table { border-collapse: collapse; width: 100%; margin: 1em 0; }',
            'table td, table th { border: 1px solid #d1d5db; padding: 8px 12px; min-width: 50px; }',
            'table th { background: #f3f4f6; font-weight: 600; }',
            'blockquote { border-left: 4px solid #3b82f6; padding: 12px 20px; margin: 1em 0; color: #4b5563; background: #eff6ff; }',
            '.custom-cta-btn { display: inline-block; padding: 10px 24px; background-color: var(--btn-primary); color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: 600; text-align: center; transition: all 0.3s ease; margin: 10px 0; }',
            '.custom-cta-btn:hover { background-color: var(--btn-primary-hover); }'
        ].join('\n'),
        setup: function (editor) {

            // --- Custom: Format Tab (Embed Code) ---
            editor.ui.registry.addMenuButton('formatTab', {
                text: 'Format Tab',
                tooltip: 'Format Options & Embed Code',
                fetch: function(callback) {
                    var items = [
                        {
                            type: 'menuitem',
                            text: 'Add Embed Code',
                            icon: 'embed',
                            onAction: function() {
                                editor.windowManager.open({
                                    title: 'Add Embed Code',
                                    size: 'large',
                                    body: {
                                        type: 'panel',
                                        items: [
                                            {
                                                type: 'htmlpanel',
                                                html: '<p style="margin-bottom:10px;">Paste your embed code (YouTube, Facebook, Twitter, HTML, etc.) below. The editor will automatically preview it.</p>'
                                            },
                                            {
                                                type: 'textarea',
                                                name: 'embed_code'
                                            }
                                        ]
                                    },
                                    buttons: [
                                        { type: 'cancel', text: 'Cancel' },
                                        { type: 'submit', text: 'Insert Embed', primary: true }
                                    ],
                                    onSubmit: function (api) {
                                        var data = api.getData();
                                        if (data.embed_code) {
                                            editor.insertContent(data.embed_code + '<p>&nbsp;</p>');
                                        }
                                        api.close();
                                    }
                                });
                            }
                        }
                    ];
                    callback(items);
                }
            });

            // --- Custom: Insert CTA Button ---
            editor.ui.registry.addButton('insertCtaBtn', {
                text: 'Button',
                tooltip: 'Insert Styled Link Button',
                onAction: function () {
                    const text = prompt('বাটনের টেক্সট দিন (উদাঃ Read More):');
                    if (!text) return;
                    const url = prompt('বাটনের লিংক (URL) দিন:');
                    if (!url) return;
                    editor.execCommand('mceInsertContent', false, '<a href="' + url + '" class="custom-cta-btn">' + text + '</a>&nbsp;');
                }
            });

            // --- Custom: Insert Image from Media Library ---
            editor.ui.registry.addButton('customMediaBtn', {
                icon: 'image',
                tooltip: 'Insert Image from Media Library',
                onAction: function (_) {
                    openMediaLibrary(function(url) {
                        const caption = prompt('\u099b\u09ac\u09bf\u09b0 \u0995\u09cd\u09af\u09be\u09aa\u09b6\u09a8 \u09b2\u09bf\u0996\u09c1\u09a8 (\u0996\u09be\u09b2\u09bf \u09b0\u09be\u0996\u09b2\u09c7 \u09b6\u09c1\u09a7\u09c1 \u099b\u09ac\u09bf \u09af\u09be\u09ac\u09c7):');
                        if (caption) {
                            editor.execCommand('mceInsertContent', false,
                                '<figure style="display:block;text-align:center;clear:both;margin:1.2em 0;">' +
                                '<img src="' + url + '" alt="Image" style="width:80%;max-width:100%;height:auto;display:inline-block;">' +
                                '<figcaption style="font-size:0.85em;color:#6b7280;margin-top:0.4em;font-style:italic;">' + caption + '</figcaption>' +
                                '</figure>'
                            );
                        } else {
                            editor.execCommand('mceInsertContent', false,
                                '<p><img src="' + url + '" alt="Image" style="width:80%;max-width:100%;height:auto;display:block;margin:0 auto;"></p>'
                            );
                        }
                    });
                }
            });

            // --- Custom: Image Size % Selector (10–100%) ---
            var sizeItems = [];
            for (var s = 10; s <= 100; s += 5) {
                (function(pct) {
                    sizeItems.push({
                        type: 'menuitem',
                        text: pct + '%',
                        onAction: function() {
                            var img = editor.selection.getNode();
                            // Walk up to find img if inside figure
                            if (img && img.nodeName !== 'IMG') {
                                img = img.querySelector ? img.querySelector('img') : null;
                            }
                            if (img && img.nodeName === 'IMG') {
                                editor.dom.setStyles(img, {
                                    width: pct + '%',
                                    height: 'auto',
                                    'max-width': '100%'
                                });
                                img.removeAttribute('width');
                                img.removeAttribute('height');
                                editor.fire('change');
                            } else {
                                editor.notificationManager.open({
                                    text: '\u09aa\u09cd\u09b0\u09a5\u09ae\u09c7 \u098f\u0995\u099f\u09bf \u099b\u09ac\u09bf \u09a8\u09bf\u09b0\u09cd\u09ac\u09be\u099a\u09a8 \u0995\u09b0\u09c1\u09a8!',
                                    type: 'warning',
                                    timeout: 2000
                                });
                            }
                        }
                    });
                })(s);
            }

            editor.ui.registry.addMenuButton('imgSizeBtn', {
                text: '\u099b\u09ac\u09bf \u09b8\u09be\u0987\u099c',
                icon: 'resize',
                fetch: function(callback) {
                    callback(sizeItems);
                }
            });

            editor.on('change input', function () {
                tinymce.triggerSave();
            });
        }
    });
</script>

<script>
document.querySelector('form')?.addEventListener('submit', function () {
    document.querySelectorAll('input[required], textarea[required], select[required]').forEach(function(el) {
        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden' || el.offsetParent === null) {
            el.removeAttribute('required');
        }
    });
});
</script>

<!-- Select2 integration -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Minimal fix for Select2 */
.select2-container .select2-selection--multiple {
    min-height: 46px;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 2px 6px;
    margin-top: 8px;
}
/* Ensure dropdown is always on top */
.select2-container--open {
    z-index: 99999 !important;
}
</style>
<script>
$(document).ready(function() {
    $('#postTags').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: "ট্যাগ খুঁজুন বা নতুন লিখুন",
        width: '100%'
    });
    
    // Close dropdown on scroll to prevent floating issues
    $('main').on('scroll', function() {
        $('#postTags').select2('close');
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>

