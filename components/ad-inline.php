<?php
/**
 * Inline Ad Component
 * Usage: component('ad-inline', ['position' => 'in_article_1']);
 */

$position = $position ?? 'in_article_1';
$setting = new Setting();

$enabled = $setting->get("ad_{$position}_enabled");
$code = $setting->get("ad_{$position}_code");

if (!$enabled || empty(trim((string)$code))) {
    return;
}

?>
<div class="ad ad-inline ad-<?php echo htmlspecialchars($position); ?> my-6 text-center">
    <?php echo $code; ?>
</div>
