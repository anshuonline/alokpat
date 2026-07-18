<?php
/**
 * Ad Banner Component
 * Usage: component('ad-banner', ['position' => 'header']);
 */

$position = $position ?? 'header';
$setting = new Setting();

$enabled = $setting->get("ad_{$position}_enabled");
$code = $setting->get("ad_{$position}_code");

if (!$enabled || empty(trim((string)$code))) {
    // nothing to render — no blank space
    return;
}

?>
<div class="ad ad-<?php echo htmlspecialchars($position); ?> my-4" style="text-align:center;">
    <?php echo $code; ?>
</div>
