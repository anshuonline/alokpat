<?php
/**
 * Delete Post Action
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Permission check will be handled logically
// requirePermission('delete_posts');
if (!isset($_GET['id'])) {
    redirect(ADMIN_URL . '/posts.php');
}

$id = (int)$_GET['id'];
$post = new Post();

// Verify permission
$post_data = $post->getById($id);
if (!$post_data) {
    setFlash('error', 'পোস্টটি পাওয়া যায়নি');
    redirect(ADMIN_URL . '/posts.php');
}

// Check delete_posts permission or if the writer is trying to delete their own post
$current_user_del = getCurrentUser();
if (!hasPermission('delete_posts')) {
    // Writer requesting deletion - must have edit_own_posts and be the author
    if (!hasPermission('edit_own_posts') || $post_data['author_id'] != $current_user_del['id']) {
        setFlash('error', 'আপনার এই পোস্ট মুছে ফেলার অনুমতি নেই');
        redirect(ADMIN_URL . '/posts.php');
    }
    
    // Change status to pending_delete
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE posts SET status = 'pending_delete', updated_by = ? WHERE id = ?");
    if ($stmt->execute([$current_user_del['id'], $id])) {
        setFlash('success', 'পোস্টটি ডিলিট করার অনুরোধ অ্যাডমিনের কাছে পাঠানো হয়েছে।');
    } else {
        setFlash('error', 'অনুরোধ পাঠাতে সমস্যা হয়েছে');
    }
} else {
    // Admin with delete_posts permission
    if ($post->delete($id)) {
        setFlash('success', 'পোস্ট সফলভাবে ট্র্যাশে পাঠানো হয়েছে');
    } else {
        setFlash('error', 'পোস্ট ট্র্যাশে পাঠাতে সমস্যা হয়েছে');
    }
}

$redirect_url = $_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/posts.php';
redirect($redirect_url);
