<?php
require_once '../config/config.php';
requireAuth();
if (!hasPermission('manage_settings')) die('Access denied');

$db = (new Database())->getConnection();

// Fix: Insert standard roles if they don't exist
$standard_roles = [
    ['slug' => 'super_admin', 'name' => 'Super Admin'],
    ['slug' => 'admin', 'name' => 'Admin'],
    ['slug' => 'editor', 'name' => 'Editor'],
    ['slug' => 'writer', 'name' => 'Writer']
];

$stmt = $db->prepare("INSERT IGNORE INTO roles (slug, name) VALUES (:slug, :name)");
$added = 0;
foreach ($standard_roles as $r) {
    if ($stmt->execute($r) && $stmt->rowCount() > 0) {
        $added++;
    }
}

// Check current roles
$roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo '<div style="background:#111;color:#0f0;padding:20px;font-size:14px;font-family:monospace;">';
echo "✅ Added {$added} new roles to the database.<br><br>";
echo "=== CURRENT ROLES ===<br>";
foreach ($roles as $r) {
    echo "- {$r['name']} ({$r['slug']})<br>";
}
echo '</div>';
