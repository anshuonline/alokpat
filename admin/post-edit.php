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

// Permission check: own post or others' post
$current_user_edit = getCurrentUser();
$is_own_post_edit = ($post['author_id'] == $current_user_edit['id']);
if ($is_own_post_edit) {
    requirePermission('edit_own_posts');
} else {
    requirePermission('edit_others_posts');
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
        $slug = $_POST['slug'];
    } else {
        $slug = $title;
    }
    
    // Generate unique slug, excluding the current post ID
    $slug = generateUniqueSlug($slug, 'posts', 'slug', $post_id);
    
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

        
        // Process FAQ Schema
        $faqs = [];
        if (!empty($_POST['faq_q']) && !empty($_POST['faq_a'])) {
            for ($i = 0; $i < count($_POST['faq_q']); $i++) {
                $q = trim($_POST['faq_q'][$i]);
                $a = trim($_POST['faq_a'][$i]);
                if (!empty($q) && !empty($a)) {
                    $faqs[] = ['q' => $q, 'a' => $a];
                }
            }
        }
        $faq_schema = !empty($faqs) ? json_encode($faqs, JSON_UNESCAPED_UNICODE) : null;
        
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
            'is_live' => isset($_POST['is_live']),
            'flags_expiry' => !empty($_POST['flags_expiry']) ? date('Y-m-d H:i:s', strtotime($_POST['flags_expiry'])) : null,
            'seo_title' => sanitize($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize($_POST['seo_description'] ?? ''),
            'seo_keywords' => sanitize($_POST['seo_keywords'] ?? ''),
            'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
            'meta_og_title' => sanitize($_POST['meta_og_title'] ?? ''),
            'meta_og_description' => sanitize($_POST['meta_og_description'] ?? ''),
            'meta_og_image' => sanitize($_POST['meta_og_image'] ?? ''),
            'post_type' => sanitize($_POST['post_type'] ?? 'standard'),
            'meta_twitter_card' => sanitize($_POST['meta_twitter_card'] ?? 'summary_large_image'),
            'faq_schema' => $faq_schema,
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
        
        // Set publish time if changing to published or scheduling
        if ($status === 'published' && $post['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'scheduled' && !empty($_POST['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s', strtotime($_POST['published_at']));
        }
        
        $data['updated_by'] = getCurrentUser()['id'];
        
        if (empty($errors)) {
            // Writers Approval Workflow for Edits
            if (!hasPermission('publish_posts')) {
                if ($post['status'] === 'published') {
                    // Create a revision if editing an already published post
                    $data['status'] = 'pending_review';
                    $data['parent_id'] = $post_id;
                    $data['author_id'] = $post['author_id']; // keep original author
                    $data['slug'] = $data['slug'] . '-rev-' . time(); // Make slug unique for the revision
                    
                    if ($post_model->create($data)) {
                        setFlash('success', 'আপনার এডিটটি অ্যাডমিনের অনুমোদনের জন্য পেন্ডিং এ পাঠানো হয়েছে।');
                        redirect(ADMIN_URL . '/posts.php');
                    } else {
                        $errors[] = 'সংবাদ আপডেটে সমস্যা হয়েছে';
                    }
                    $is_revision = true;
                } else {
                    // If editing a draft/pending_review, ensure they cannot publish it directly
                    if (in_array($data['status'], ['published', 'scheduled'])) {
                        $data['status'] = 'pending_review';
                    }
                }
            }
            
            if (!isset($is_revision)) {
                // Normal update
                if ($post_model->update($post_id, $data)) {
                    // FCM Auto Push on Publish (if it wasn't published before)
                    if ($status === 'published' && $post['status'] !== 'published') {
                        $setting = new Setting();
                        if ($setting->get('fcm_auto_send_on_publish') == '1') {
                            require_once BASE_PATH . '/admin/api/send_push.php';
                            sendFirebasePushNotification($post_id);
                        }
                    }
                    setFlash('success', 'সংবাদ সফলভাবে আপডেট হয়েছে');
                    redirect(ADMIN_URL . '/post-edit.php?id=' . $post_id);
                } else {
                    $errors[] = 'সংবাদ আপডেটে সমস্যা হয়েছে';
                }
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
            'archived' => 'bg-gray-100 text-gray-800',
            'trashed' => 'bg-red-100 text-red-800',
            'unlisted' => 'bg-purple-100 text-purple-800',
            'pending_review' => 'bg-yellow-100 text-yellow-800',
            'pending_delete' => 'bg-orange-100 text-orange-800'
        ];
        $status_labels = [
            'published' => 'প্রকাশিত',
            'draft' => 'খসড়া',
            'scheduled' => 'নির্ধারিত',
            'archived' => 'সংরক্ষিত',
            'trashed' => 'ট্র্যাশ',
            'unlisted' => 'আনলিস্টেড',
            'pending_review' => 'পেন্ডিং এডিট',
            'pending_delete' => 'পেন্ডিং ডিলিট'
        ];
        
        $current_status = $post['status'];
        $badge_class = $status_classes[$current_status] ?? 'bg-gray-100 text-gray-800';
        $badge_label = $status_labels[$current_status] ?? $current_status;
        ?>
        <span class="px-4 py-2 text-sm font-semibold rounded-full <?php echo $badge_class; ?>">
            <?php echo $badge_label; ?>
        </span>
        <span class="text-gray-500 text-sm">
            <i class="fas fa-calendar mr-1"></i>
            তৈরি: <?php echo formatDateBengali($post['created_at']); ?>
        </span>
    </div>
    
    <!-- Form -->
    <form id="postEditForm" method="POST" action="?id=<?php echo $post_id; ?>" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="autosave_post_id" id="autosave_post_id" value="<?php echo $post_id; ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Post Type Selector -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <label class="block text-lg font-semibold text-gray-800 mb-4">
                        পোস্টের ধরন <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="post_type" value="standard" class="sr-only peer" <?php echo ($post['post_type'] ?? 'standard') === 'standard' ? 'checked' : ''; ?>>
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">সাধারণ পোস্ট (Standard)</p>
                                        <div class="text-gray-500">নিয়মিত নিউজ আর্টিকেল</div>
                                    </div>
                                </div>
                                <div class="shrink-0 text-blue-600 hidden peer-checked:block">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                            <span class="pointer-events-none absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-blue-600" aria-hidden="true"></span>
                        </label>

                        <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="post_type" value="live_blog" class="sr-only peer" <?php echo ($post['post_type'] ?? 'standard') === 'live_blog' ? 'checked' : ''; ?>>
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 flex items-center">লাইভ ব্লগ (Live Blog) <span class="ml-2 flex h-2 w-2 relative"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span></p>
                                        <div class="text-gray-500">টাইমলাইন আপডেট ভিত্তিক সংবাদ</div>
                                    </div>
                                </div>
                                <div class="shrink-0 text-blue-600 hidden peer-checked:block">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                            </div>
                            <span class="pointer-events-none absolute -inset-px rounded-lg border-2 border-transparent peer-checked:border-blue-600" aria-hidden="true"></span>
                        </label>
                    </div>
                </div>
                
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
                
                <!-- Excerpt -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        সারাংশ (Excerpt)
                    </label>
                    <textarea name="excerpt" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                              placeholder="সংবাদের সংক্ষিপ্ত সারাংশ..."><?php echo isset($post['excerpt']) ? escape($post['excerpt']) : (isset($_POST['excerpt']) ? escape($_POST['excerpt']) : ''); ?></textarea>
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

                <!-- Live Blog Updates Timeline -->
                <?php if (($post['post_type'] ?? 'standard') === 'live_blog'): ?>
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <label class="block text-lg font-bold text-gray-800 flex items-center m-0">
                            <span class="relative flex h-3 w-3 mr-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                            </span>
                            লাইভ আপডেট টাইমলাইন
                        </label>
                        <button type="button" onclick="openLiveUpdateModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-red-700 font-semibold text-sm transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> নতুন আপডেট যোগ করুন
                        </button>
                    </div>
                    
                    <div id="liveUpdatesTimelineContainer" class="space-y-4">
                        <!-- Updates will be loaded here via AJAX -->
                        <div class="text-center text-gray-500 py-8"><i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i><br>আপডেট লোড হচ্ছে...</div>
                    </div>
                </div>
                <?php endif; ?>
                
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
                                    
                                    <?php if (hasPermission('publish_posts')): ?>
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

                                    <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition">
                                        <input type="radio" 
                                               name="status" 
                                               value="unlisted" 
                                               onchange="toggleScheduleInput()"
                                               <?php echo ($current_status === 'unlisted') ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-blue-600">
                                        <span class="text-sm text-gray-600"><i class="fas fa-eye-slash mr-1"></i> আনলিস্টেড (Unlisted)</span>
                                    </label>
                                    <?php else: ?>
                                    <label class="flex items-center p-2 rounded hover:bg-yellow-50 cursor-pointer border border-transparent hover:border-yellow-200 transition bg-yellow-50/50">
                                        <input type="radio" 
                                               name="status" 
                                               value="published" 
                                               onchange="toggleScheduleInput()"
                                               <?php echo in_array($current_status, ['published', 'pending_review']) ? 'checked' : ''; ?>
                                               class="mr-2 h-4 w-4 text-yellow-600">
                                        <span class="text-sm font-semibold text-yellow-700"><i class="fas fa-paper-plane mr-1"></i> রিভিউয়ের জন্য পাঠান (Submit for Review)</span>
                                    </label>
                                    <?php endif; ?>
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
                                    <input type="datetime-local" step="any" name="published_at" value="<?php echo $published_at_val; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
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
                                    <input type="datetime-local" step="any" name="flags_expiry" value="<?php echo $flags_expiry_val; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <p class="text-[11px] text-gray-500 mt-1">খালি রাখলে আজীবন দেখাবে।</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- SEO Section - Enhanced -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100/80 overflow-hidden transition-all hover:shadow-xl">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-50 to-white px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">SEO Settings</h3>
                        <p class="text-xs text-gray-500">Optimize your content for search engines and social sharing</p>
                    </div>
                </div>
                <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    <i class="fas fa-check-circle mr-1"></i> Live Preview
                </span>
            </div>
            
            <div class="p-6 md:p-8 space-y-8">
                <!-- Group 1: General SEO -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-6 bg-emerald-500 rounded-full"></div>
                        <h4 class="text-sm font-semibold text-gray-700">Search Engine Optimization</h4>
                        <span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Primary</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- SEO Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                SEO Title
                                <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="seo_title" value="<?php echo isset($_POST['seo_title']) ? escape($_POST['seo_title']) : ''; ?>" 
                                       class="w-full pl-4 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50 hover:bg-white" 
                                       placeholder="Custom search engine title">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <span class="inline-block w-1 h-1 bg-gray-300 rounded-full"></span>
                                Leave blank to use the main title
                            </p>
                        </div>
                        
                        <!-- Focus Keywords -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Focus Keywords
                                <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="seo_keywords" value="<?php echo isset($_POST['seo_keywords']) ? escape($_POST['seo_keywords']) : ''; ?>" 
                                       class="w-full pl-4 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50 hover:bg-white" 
                                       placeholder="keyword1, keyword2, keyword3">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <span class="inline-block w-1 h-1 bg-gray-300 rounded-full"></span>
                                Separate with commas
                            </p>
                        </div>
        
                        <!-- SEO Description -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Meta Description
                                <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                            </label>
                            <div class="relative">
                                <textarea name="seo_description" rows="2" 
                                          class="w-full pl-4 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50 hover:bg-white resize-none" 
                                          placeholder="Write a compelling description for search results..."><?php echo isset($_POST['seo_description']) ? escape($_POST['seo_description']) : ''; ?></textarea>
                                <div class="absolute right-3 top-3 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1.5">
                                <p class="text-[11px] text-gray-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 bg-gray-300 rounded-full"></span>
                                    Recommended: 150-160 characters
                                </p>
                                <span class="text-[10px] text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">0/160</span>
                            </div>
                        </div>
                    </div>
                </div>
        
                <!-- Group 2: Social Media -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-6 bg-blue-500 rounded-full"></div>
                        <h4 class="text-sm font-semibold text-gray-700">Social Sharing</h4>
                        <span class="text-[10px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Open Graph</span>
                    </div>
                    
                    <div class="bg-gradient-to-r from-blue-50/30 to-transparent p-4 rounded-xl border border-blue-100/50">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- OG Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Social Title
                                    <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                                </label>
                                <input type="text" name="meta_og_title" value="<?php echo isset($_POST['meta_og_title']) ? escape($_POST['meta_og_title']) : ''; ?>" 
                                       class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 bg-white/80" 
                                       placeholder="Title for social media posts">
                            </div>
                            
                            <!-- OG Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Social Description
                                    <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                                </label>
                                <textarea name="meta_og_description" rows="1" 
                                          class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 bg-white/80 resize-none" 
                                          placeholder="Description for social media"><?php echo isset($_POST['meta_og_description']) ? escape($_POST['meta_og_description']) : ''; ?></textarea>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-blue-100/50">
                            <span class="text-[10px] font-medium text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </span>
                            <span class="text-[10px] font-medium text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.104c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 0021.216-5.53c.852-2.143.852-4.294.852-6.435 0-.098-.002-.196-.006-.294A9.99 9.99 0 0023.953 4.57z"/></svg>
                                Twitter
                            </span>
                            <span class="text-[10px] text-gray-400 ml-auto">Preview available when shared</span>
                        </div>
                    </div>
                </div>
        
                <!-- Group 3: Advanced SEO -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-6 bg-purple-500 rounded-full"></div>
                        <h4 class="text-sm font-semibold text-gray-700">Advanced Configuration</h4>
                        <span class="text-[10px] font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Technical</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Canonical URL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Canonical URL
                                <span class="text-gray-400 font-normal text-xs ml-1">(optional)</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="canonical_url" value="<?php echo isset($_POST['canonical_url']) ? escape($_POST['canonical_url']) : ''; ?>" 
                                       class="w-full pl-4 pr-10 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-200 bg-gray-50/50 hover:bg-white" 
                                       placeholder="https://example.com/original-article">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <span class="inline-block w-1 h-1 bg-gray-300 rounded-full"></span>
                                Prevents duplicate content issues
                            </p>
                        </div>
                        
                        <!-- Robots Meta -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Robots Meta
                                <span class="text-gray-400 font-normal text-xs ml-1">(required)</span>
                            </label>
                            <select name="robots_meta" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all duration-200 bg-white appearance-none cursor-pointer">
                                <option value="index,follow" <?php echo (!isset($_POST['robots_meta']) || $_POST['robots_meta'] === 'index,follow') ? 'selected' : ''; ?>>✅ Index, Follow</option>
                                <option value="index,nofollow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'index,nofollow') ? 'selected' : ''; ?>>📌 Index, Nofollow</option>
                                <option value="noindex,follow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'noindex,follow') ? 'selected' : ''; ?>>🚫 Noindex, Follow</option>
                                <option value="noindex,nofollow" <?php echo (isset($_POST['robots_meta']) && $_POST['robots_meta'] === 'noindex,nofollow') ? 'selected' : ''; ?>>🚫 Noindex, Nofollow</option>
                            </select>
                            <div class="relative pointer-events-none">
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        
                <!-- Group 4: FAQ Schema -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-6 bg-orange-500 rounded-full"></div>
                            <h4 class="text-sm font-semibold text-gray-700">FAQ Schema</h4>
                            <span class="text-[10px] font-medium text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">Rich Results</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="document.getElementById('import-json-container').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 bg-gray-50 hover:bg-gray-100 px-4 py-2 rounded-xl transition-all duration-200 border border-gray-200 hover:border-gray-300">
                                <i class="fas fa-code"></i>
                                Import JSON
                            </button>
                            <button type="button" onclick="addFaqItem()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-600 bg-orange-50 hover:bg-orange-100 px-4 py-2 rounded-xl transition-all duration-200 border border-orange-200 hover:border-orange-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Question
                            </button>
                        </div>
                    </div>

                    <div id="import-json-container" class="hidden mb-4 p-4 bg-orange-50/30 rounded-xl border border-orange-100/50 relative">
                        <button type="button" onclick="this.parentElement.classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                        <label class="block text-xs font-medium text-gray-600 mb-2">Paste JSON-LD Schema (People Also Ask) here:</label>
                        <textarea id="import-json-textarea" rows="4" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 text-xs font-mono mb-3 bg-white" placeholder='{&#10;  "@context": "https://schema.org",&#10;  "@type": "FAQPage",&#10;  "mainEntity": [...]&#10;}'></textarea>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="processJsonImport()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                                <i class="fas fa-file-import"></i>
                                Import Questions
                            </button>
                        </div>
                    </div>
                    
                    <div id="faq-container" class="space-y-3">
                        <?php 
                        $faqs = [];
                        if (!empty($post['faq_schema'])) { $faqs = json_decode($post['faq_schema'], true); }
                        if (!empty($faqs)): 
                            foreach ($faqs as $index => $faq):
                        ?>
                            <div class="faq-item bg-gradient-to-r from-orange-50/30 to-transparent p-4 rounded-xl border border-orange-100/50 relative group transition-all hover:border-orange-200 hover:shadow-sm">
                                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2.5 right-2.5 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all bg-white rounded-lg p-1.5 shadow-sm border border-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <div class="grid gap-3 md:grid-cols-2 pr-8">
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Question</label>
                                        <input type="text" name="faq_q[]" value="<?php echo escape($faq['q']); ?>" 
                                               class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-sm bg-white" 
                                               placeholder="e.g. What is the price?">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Answer</label>
                                        <textarea name="faq_a[]" rows="1" 
                                                  class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-sm bg-white resize-none" 
                                                  placeholder="e.g. The price starts from..."><?php echo escape($faq['a']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-[10px] text-gray-400">💡</span>
                        <p class="text-[10px] text-gray-400">Add frequently asked questions to appear in Google's rich snippets</p>
                    </div>
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

// Auto-fill SEO and OG fields
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    const excerptInput = document.querySelector('textarea[name="excerpt"]');
    const seoTitleInput = document.querySelector('input[name="seo_title"]');
    const seoDescInput = document.querySelector('textarea[name="seo_description"]');
    const ogTitleInput = document.querySelector('input[name="meta_og_title"]');
    const ogDescInput = document.querySelector('textarea[name="meta_og_description"]');

    let slugEdited = slugInput && slugInput.value.trim() !== '';
    let seoDescEdited = seoDescInput && seoDescInput.value.trim() !== '';
    let ogDescEdited = ogDescInput && ogDescInput.value.trim() !== '';
    let ogTitleEdited = ogTitleInput && ogTitleInput.value.trim() !== '';

    if(slugInput) slugInput.addEventListener('input', () => slugEdited = true);
    if(seoDescInput) seoDescInput.addEventListener('input', () => seoDescEdited = true);
    if(ogDescInput) ogDescInput.addEventListener('input', () => ogDescEdited = true);
    if(ogTitleInput) ogTitleInput.addEventListener('input', () => ogTitleEdited = true);

    if(excerptInput) {
        excerptInput.addEventListener('input', function() {
            if(seoDescInput && !seoDescEdited) seoDescInput.value = this.value;
            if(ogDescInput && !ogDescEdited) ogDescInput.value = this.value;
        });
    }

    if(seoTitleInput) {
        seoTitleInput.addEventListener('input', function() {
            if(ogTitleInput && !ogTitleEdited) ogTitleInput.value = this.value;
        });
    }

    let translateTimeout;
    if(titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if(!slugEdited) {
                clearTimeout(translateTimeout);
                const text = this.value.trim();
                if(text === '') {
                    slugInput.value = '';
                    return;
                }
                translateTimeout = setTimeout(() => {
                    fetch('https://translate.googleapis.com/translate_a/single?client=gtx&sl=bn&tl=en&dt=t&q=' + encodeURIComponent(text))
                    .then(res => res.json())
                    .then(data => {
                        let translated = '';
                        if (data && data[0]) {
                            data[0].forEach(item => { if (item[0]) translated += item[0]; });
                        }
                        if (translated) {
                            slugInput.value = translated.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
                        }
                    }).catch(err => console.error(err));
                }, 800);
            }
        });
    }
});
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

<?php if (($post['post_type'] ?? 'standard') === 'live_blog'): ?>
<!-- Live Update Modal -->
<div id="liveUpdateModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeLiveUpdateModal()"></div>
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto relative z-10 p-6 transform transition-all">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-bolt text-red-500 mr-2"></i> <span id="liveUpdateModalTitle">নতুন লাইভ আপডেট</span>
            </h3>
            <button type="button" onclick="closeLiveUpdateModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="liveUpdateForm" onsubmit="submitLiveUpdate(event)">
            <input type="hidden" id="live_update_id" value="">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">সময় (Update Time)</label>
                <input type="datetime-local" id="live_update_time" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">বিস্তারিত (Content)</label>
                <textarea id="live_update_content" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 font-mono text-sm"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeLiveUpdateModal()" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition">বাতিল</button>
                <button type="submit" class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 shadow-md transition flex items-center">
                    <i class="fas fa-save mr-2"></i> সংরক্ষণ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Initialize Live Update TinyMCE when modal opens
let liveUpdateEditorInit = false;

function openLiveUpdateModal(id = '', content = '', time = '') {
    document.getElementById('liveUpdateModalTitle').innerText = id ? 'আপডেট সম্পাদনা' : 'নতুন লাইভ আপডেট';
    document.getElementById('live_update_id').value = id;
    
    // Set default time if new
    if (!time) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        time = now.toISOString().slice(0,16);
    }
    document.getElementById('live_update_time').value = time;
    document.getElementById('live_update_content').value = content;
    
    document.getElementById('liveUpdateModal').classList.remove('hidden');
    
    if (!liveUpdateEditorInit) {
        tinymce.init({
            selector: '#live_update_content',
            height: 300,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | image link | code help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: function(editor) {
                editor.on('change input', function () {
                    tinymce.triggerSave();
                });
            }
        });
        liveUpdateEditorInit = true;
    } else {
        tinymce.get('live_update_content').setContent(content);
    }
}

function closeLiveUpdateModal() {
    document.getElementById('liveUpdateModal').classList.add('hidden');
}

function submitLiveUpdate(e) {
    e.preventDefault();
    tinymce.triggerSave();
    
    const id = document.getElementById('live_update_id').value;
    const time = document.getElementById('live_update_time').value;
    const content = document.getElementById('live_update_content').value;
    
    if (!content.trim()) {
        alert('বিস্তারিত লেখা প্রয়োজন');
        return;
    }
    
    const action = id ? 'update' : 'create';
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    formData.append('post_id', '<?php echo $post_id; ?>');
    formData.append('update_time', time);
    formData.append('content', content);
    if (id) formData.append('id', id);
    
    fetch('ajax_live_update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeLiveUpdateModal();
            fetchLiveUpdates(); // Reload updates
            
            // Show toast/alert
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50 transition-opacity duration-500';
            toast.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${data.message}`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); }, 3000);
        } else {
            alert(data.message || 'Error saving update');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Server connection error');
    });
}

function deleteLiveUpdate(id) {
    if (!confirm('আপনি কি নিশ্চিত যে এই আপডেটটি মুছে ফেলতে চান?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    formData.append('id', id);
    
    fetch('ajax_live_update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchLiveUpdates();
        } else {
            alert(data.message || 'Error deleting update');
        }
    });
}

function fetchLiveUpdates() {
    const formData = new FormData();
    formData.append('action', 'fetch');
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    formData.append('post_id', '<?php echo $post_id; ?>');
    
    fetch('ajax_live_update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('liveUpdatesTimelineContainer');
        if (data.success) {
            if (data.updates.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-6 border-2 border-dashed border-gray-300 rounded-lg">কোন আপডেট পাওয়া যায়নি। নতুন আপডেট যোগ করুন।</div>';
                return;
            }
            
            let html = '<div class="relative border-l-2 border-red-500 ml-3 pl-6 space-y-6">';
            data.updates.forEach(update => {
                // Ensure properly formatted datetime-local
                let dt = new Date(update.update_time);
                dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
                let isoTime = dt.toISOString().slice(0,16);
                
                // Escape HTML for data attributes safely
                const escapedContent = update.content.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                
                html += `
                <div class="relative">
                    <div class="absolute -left-[31px] top-1 h-4 w-4 rounded-full bg-red-500 border-4 border-white shadow"></div>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 shadow-sm relative group hover:shadow-md transition">
                        <div class="text-xs font-bold text-red-600 mb-2 flex items-center">
                            <i class="far fa-clock mr-1"></i> ${update.display_time}
                        </div>
                        <div class="prose prose-sm max-w-none text-gray-800">
                            ${update.content}
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition flex space-x-2 bg-white px-2 py-1 rounded shadow-sm">
                            <button type="button" onclick="openLiveUpdateModal(${update.id}, \`${escapedContent}\`, '${isoTime}')" class="text-blue-500 hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="deleteLiveUpdate(${update.id})" class="text-red-500 hover:text-red-700 w-7 h-7 rounded flex items-center justify-center hover:bg-red-50 transition" title="Delete">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="text-red-500">${data.message || 'Error loading updates'}</div>`;
        }
    });
}

// Load initially if it's a live blog
document.addEventListener('DOMContentLoaded', fetchLiveUpdates);
</script>
<?php endif; ?>

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

<!-- AutoSave Toast Notification -->
<div id="autosave-toast" class="fixed bottom-5 right-5 bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center">
    <i class="fas fa-check-circle text-green-400 mr-2"></i>
    <span id="autosave-toast-message">Auto saved</span>
</div>

<script>
$(document).ready(function() {
    let lastSavedData = '';
    
    function showAutosaveToast(message) {
        const toast = $('#autosave-toast');
        $('#autosave-toast-message').text(message);
        toast.removeClass('translate-y-20 opacity-0').addClass('translate-y-0 opacity-100');
        
        setTimeout(() => {
            toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-20 opacity-0');
        }, 3000);
    }

    function autoSave() {
        const title = $('input[name="title"]').val();
        if (!title || !title.trim()) return;

        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const formEl = document.getElementById('postEditForm') || document.getElementById('post-form');
        if (!formEl) return;

        const formData = new FormData(formEl);
        formData.append('action', 'autosave');
        
        const dataString = new URLSearchParams(formData).toString();
        
        if (dataString === lastSavedData) {
            return;
        }

        $.ajax({
            url: 'ajax/autosave.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res.success && res.post_id) {
                        $('#autosave_post_id').val(res.post_id);
                        lastSavedData = dataString;
                        
                        const time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                        showAutosaveToast('Auto-saved at ' + time);
                    }
                } catch (e) {
                    console.error('Autosave parse error', e);
                }
            }
        });
    }

    setInterval(autoSave, 30000);
});

function addFaqItem(q = '', a = '') {
    const container = document.getElementById('faq-container');
    const faqItem = document.createElement('div');
    faqItem.className = 'faq-item bg-gradient-to-r from-orange-50/30 to-transparent p-4 rounded-xl border border-orange-100/50 relative group transition-all hover:border-orange-200 hover:shadow-sm';
    
    const escapeHtml = (text) => text.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
    const safeQ = escapeHtml(q);
    const safeA = escapeHtml(a);

    faqItem.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-2.5 right-2.5 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all bg-white rounded-lg p-1.5 shadow-sm border border-gray-100">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
        <div class="grid gap-3 md:grid-cols-2 pr-8">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Question</label>
                <input type="text" name="faq_q[]" value="${safeQ}" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-sm bg-white" placeholder="e.g. What is the price?">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Answer</label>
                <textarea name="faq_a[]" rows="1" class="w-full px-3.5 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-sm bg-white resize-none" placeholder="e.g. The price starts from...">${safeA}</textarea>
            </div>
        </div>
    `;
    container.appendChild(faqItem);
}

function processJsonImport() {
    const textarea = document.getElementById('import-json-textarea');
    const jsonStr = textarea.value.trim();
    if(!jsonStr) {
        alert('Please paste some JSON-LD code.');
        return;
    }
    
    try {
        const data = JSON.parse(jsonStr);
        if(data['@type'] === 'FAQPage' && Array.isArray(data.mainEntity)) {
            let count = 0;
            data.mainEntity.forEach(item => {
                if(item['@type'] === 'Question' && item.name && item.acceptedAnswer && item.acceptedAnswer.text) {
                    addFaqItem(item.name, item.acceptedAnswer.text);
                    count++;
                }
            });
            if(count > 0) {
                textarea.value = '';
                document.getElementById('import-json-container').classList.add('hidden');
            } else {
                alert('No valid Question/Answer pairs found in the JSON.');
            }
        } else {
            alert('Invalid FAQ Schema format. Make sure it contains "@type": "FAQPage" and a "mainEntity" array.');
        }
    } catch(e) {
        alert('Invalid JSON! Please check the syntax.');
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>

