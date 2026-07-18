<?php
/**
 * Duplicate a post (creates a copy and redirects to edit)
 */

require_once '../config/config.php';
requireAuth();

$allowed_roles = ['super_admin', 'admin', 'editor'];
if (!in_array(getCurrentUser()['role'], $allowed_roles)) {
    setFlash('error', 'আপনার এই কার্যটি করার অনুমতি নেই');
    redirect(ADMIN_URL . '/posts.php');
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'অবৈধ পোস্ট আইডি');
    redirect(ADMIN_URL . '/posts.php');
}

$postModel = new Post();
$orig = $postModel->getById($id);
if (!$orig) {
    setFlash('error', 'পোস্ট খুঁজে পাওয়া যায়নি');
    redirect(ADMIN_URL . '/posts.php');
}

// Prepare data for duplication
$data = $orig;
// Remove fields that shouldn't be copied
unset($data['id']);
unset($data['created_at']);
unset($data['published_at']);
// Prefix the title and adjust slug to be unique
$data['title'] = 'Copy of ' . ($data['title'] ?? 'Untitled');
// Generate new slug
$baseSlug = isset($data['slug']) ? $data['slug'] : preg_replace('/[^a-z0-9]+/i', '-', strtolower($data['title']));
$newSlug = $baseSlug;
$i = 1;
while ($postModel->getBySlug($newSlug)) {
    $newSlug = $baseSlug . '-' . $i;
    $i++;
}
$data['slug'] = $newSlug;

// Set status to draft to avoid immediate publish
$data['status'] = 'draft';

// Keep tags
// Collect tag IDs (Post::create expects tag IDs in data['tags'])
$origTags = $data['tags'] ?? [];
$tags = [];
if (!empty($origTags) && is_array($origTags)) {
    foreach ($origTags as $t) {
        if (is_array($t) && isset($t['id'])) {
            $tags[] = $t['id'];
        } elseif (is_numeric($t)) {
            $tags[] = intval($t);
        }
    }
}
// Author: current user by default
$data['author_id'] = getCurrentUser()['id'];

// Pass tags into create so the model will attach them
$data['tags'] = $tags;
$newId = $postModel->create($data);
if ($newId) {
    setFlash('success', 'পোস্ট সফলভাবে ডুপ্লিকেট হয়েছে');
    redirect(ADMIN_URL . '/post-edit.php?id=' . $newId);
} else {
    setFlash('error', 'পোস্ট ডুপ্লিকেট করতে সমস্যা হয়েছে');
    redirect(ADMIN_URL . '/posts.php');
}
