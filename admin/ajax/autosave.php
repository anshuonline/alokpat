<?php
/**
 * AutoSave Post AJAX Handler
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!validateCSRFRequest()) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

$post = new Post();

$title = sanitize($_POST['title'] ?? '');
$content = $_POST['content'] ?? ''; // Keep HTML tags for rich text
$status = 'draft'; // Auto-saves are always drafts initially or we keep existing status

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

// Generate slug
if (!empty($_POST['slug'])) {
    $slug = $_POST['slug'];
} else {
    $slug = $title;
}

$autosave_id = !empty($_POST['autosave_post_id']) ? (int)$_POST['autosave_post_id'] : null;
$slug = generateUniqueSlug($slug, 'posts', 'slug', $autosave_id);

// Process Tags
$processed_tags = [];
if (isset($_POST['tags']) && is_array($_POST['tags'])) {
    $db = (new Database())->getConnection();
    foreach ($_POST['tags'] as $tag_val) {
        if (!is_numeric($tag_val) && trim($tag_val) !== '') {
            $tag_name = trim($tag_val);
            $tag_slug = generateSlug($tag_name);
            $check = $db->prepare("SELECT id FROM tags WHERE slug = ?");
            $check->execute([$tag_slug]);
            if ($check->rowCount() > 0) {
                $processed_tags[] = $check->fetchColumn();
            } else {
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
    'post_type' => sanitize($_POST['post_type'] ?? 'standard'),
    'excerpt' => sanitize($_POST['excerpt'] ?? ''),
    'author_id' => getCurrentUser()['id'],
    'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
    // When updating, we probably shouldn't override status if it's already published? 
    // Actually, if we are in 'post-create.php', it's safe to use 'draft' as status for autosaves.
    'status' => 'draft',
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
    'meta_twitter_card' => sanitize($_POST['meta_twitter_card'] ?? 'summary_large_image'),
    'robots_meta' => sanitize($_POST['robots_meta'] ?? 'index,follow'),
    'schema_markup' => $_POST['schema_markup'] ?? '',
    'tags' => $processed_tags,
];

// Determine if Update or Create
if (!empty($_POST['autosave_post_id'])) {
    $post_id = (int)$_POST['autosave_post_id'];
    
    // Optionally preserve the old status if it's already published
    // For now, in post-create workflow, it's always draft.
    $existing = $post->getById($post_id);
    if ($existing) {
        $data['status'] = $existing['status'] === 'published' ? 'published' : 'draft';
        if ($post->update($post_id, $data)) {
            echo json_encode(['success' => true, 'post_id' => $post_id, 'mode' => 'updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update autosave']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Original post not found']);
    }
} else {
    $post_id = $post->create($data);
    if ($post_id) {
        echo json_encode(['success' => true, 'post_id' => $post_id, 'mode' => 'created']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create autosave draft']);
    }
}
