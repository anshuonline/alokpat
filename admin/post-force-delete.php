<?php
/**
 * Force Delete Post Action
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

requirePermission('delete_posts');
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

// Only admin/super_admin or the author can delete
$user = getCurrentUser();
if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin' && $post_data['author_id'] !== $user['id']) {
    setFlash('error', 'আপনার এই পোস্ট স্থায়ীভাবে মুছে ফেলার অনুমতি নেই');
    redirect(ADMIN_URL . '/posts.php');
}

if ($post->forceDelete($id)) {
    setFlash('success', 'পোস্ট স্থায়ীভাবে মুছে ফেলা হয়েছে');
} else {
    setFlash('error', 'পোস্ট স্থায়ীভাবে মুছে ফেলতে সমস্যা হয়েছে');
}

$redirect_url = $_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/posts.php';
redirect($redirect_url);
