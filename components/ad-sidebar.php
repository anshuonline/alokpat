<?php
/**
 * Ad Sidebar Component
 * Usage: component('ad-sidebar', ['position' => 'sidebar_1']);
 */

$position = $position ?? 'sidebar_1';
$setting = new Setting();

$enabled = $setting->get("ad_{$position}_enabled");
$code = $setting->get("ad_{$position}_code");

if (!$enabled || empty(trim((string)$code))) {
    return;
}

?>
<div class="ad ad-sidebar ad-<?php echo htmlspecialchars($position); ?> mb-6">
    <?php echo $code; ?>
</div>
