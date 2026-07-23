<?php
/**n+ * Admin Ads Management
 */
require_once '../config/config.php';
requireAuth();

requirePermission('manage_ads');
$setting = new Setting();

// Define ad positions to manage
$positions = [
    'header', 'homepage_top', 'homepage_middle', 'sidebar_1', 'sidebar_2',
    'in_article_1', 'in_article_2', 'footer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check omitted for brevity but should be added in production
    $updates = [];
    foreach ($positions as $pos) {
        $enabled = isset($_POST["{$pos}_enabled"]) ? '1' : '';
        $code = $_POST["{$pos}_code"] ?? '';
        // create setting keys if not existing
        if ($setting->get("ad_{$pos}_enabled") === false) {
            $setting->create("ad_{$pos}_enabled", $enabled, 'text', 'Enable ad ' . $pos);
        } else {
            $updates["ad_{$pos}_enabled"] = $enabled;
        }

        if ($setting->get("ad_{$pos}_code") === false) {
            $setting->create("ad_{$pos}_code", $code, 'textarea', 'Ad code ' . $pos);
        } else {
            $updates["ad_{$pos}_code"] = $code;
        }
    }

    // ad inject positions (e.g., 2,5)
    $inject_positions = $_POST['ad_inject_positions'] ?? '';
    if ($setting->get('ad_inject_positions') === false) {
        $setting->create('ad_inject_positions', $inject_positions, 'text', 'Inline ad injection positions');
    } else {
        $updates['ad_inject_positions'] = $inject_positions;
    }

    if (!empty($updates)) {
        $setting->updateMultiple($updates);
    }

    setFlash('success', 'Ads updated');
    redirect(ADMIN_URL . '/ads.php');
}

$site_settings = $setting->getMultiple(array_map(function($p){return "ad_{$p}_code";}, $positions));
$site_enabled = $setting->getMultiple(array_map(function($p){return "ad_{$p}_enabled";}, $positions));
$inject_positions = $setting->get('ad_inject_positions') ?: '2,5';

$page_title = 'Ad Management';
ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Ad Management</h2>
    </div>

    <?php if ($flash = getFlash()): ?>
        <div class="p-4 rounded <?php echo $flash['type'] === 'success' ? 'bg-green-100' : 'bg-red-100'; ?>">
            <?php echo escape($flash['message']); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($positions as $pos): ?>
                <?php
                // recommended sizes per position
                $recommended = 'Responsive';
                if ($pos === 'header' || $pos === 'homepage_top') $recommended = '728x90 or responsive';
                if ($pos === 'homepage_middle') $recommended = '728x90 / 970x90';
                if (strpos($pos, 'sidebar') !== false) $recommended = '300x250 or 300x600';
                if (strpos($pos, 'in_article') !== false) $recommended = '300x250 or responsive';
                if ($pos === 'footer') $recommended = '728x90 or responsive';

                // try to detect image width/height from stored code (simple regex)
                $detected = '';
                $codeVal = $site_settings["ad_{$pos}_code"] ?? '';
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $codeVal, $m)) {
                    $imgSrc = $m[1];
                    if (preg_match('/width=["\']?(\d+)["\']?/i', $codeVal, $w)) $detected .= 'W:' . $w[1] . 'px ';
                    if (preg_match('/height=["\']?(\d+)["\']?/i', $codeVal, $h)) $detected .= 'H:' . $h[1] . 'px';
                    // if no explicit attrs, try to get image size on server if local
                    if (empty($detected) && strpos($imgSrc, SITE_URL) === 0) {
                        $localPath = BASE_PATH . substr($imgSrc, strlen(SITE_URL));
                        if (file_exists($localPath)) {
                            $dims = getImageDimensions($localPath);
                            if ($dims) $detected = 'W:' . $dims['width'] . 'px H:' . $dims['height'] . 'px';
                        }
                    }
                }
                ?>
                <div class="bg-white p-4 rounded shadow">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold"><?php echo escape($pos); ?></h3>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="<?php echo $pos; ?>_enabled" value="1" <?php echo !empty($site_enabled["ad_{$pos}_enabled"]) ? 'checked' : ''; ?>>
                            <span>Enabled</span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between mb-2 text-xs text-gray-600">
                        <div>Recommended size: <strong><?php echo $recommended; ?></strong></div>
                        <div>Detected: <strong><?php echo $detected ?: '—'; ?></strong></div>
                    </div>
                    <textarea name="<?php echo $pos; ?>_code" id="code_<?php echo $pos; ?>" rows="6" class="w-full border p-2" placeholder="Paste ad script or image tag for testing"><?php echo escape($codeVal); ?></textarea>
                    <p class="text-xs text-gray-500 mt-2">You can paste Google AdSense code or an <code>&lt;img&gt;</code> tag for testing. Use the Media Manager to copy image URL.</p>
                </div>
            <?php endforeach; ?>

            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold">Inline ad injection positions</h3>
                <input type="text" name="ad_inject_positions" value="<?php echo escape($inject_positions); ?>" class="w-full border p-2" placeholder="e.g., 2,5">
                <p class="text-xs text-gray-500 mt-2">Comma separated paragraph numbers where inline ads will be injected.</p>
            </div>

            <div>
                <div class="flex items-center space-x-2 mb-2">
                    <button type="button" id="pasteUrlBtn" class="bg-gray-200 px-3 py-1 rounded text-sm">Paste last image URL</button>
                    <span class="text-xs text-gray-500">Click a textarea, then click this to paste the clipboard URL as an &lt;img&gt; tag.</span>
                </div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Save Ads</button>
            </div>
        </div>
    </form>
</div>

<script>
// Paste clipboard URL as <img> into focused textarea
let focusedTextarea = null;
document.querySelectorAll('textarea').forEach(t => {
    t.addEventListener('focus', function(){ focusedTextarea = this; });
});

document.getElementById('pasteUrlBtn').addEventListener('click', async function(){
    try {
        const text = await navigator.clipboard.readText();
        if (!text) { alert('Clipboard empty'); return; }
        const imgHtml = `<a href="#" target="_blank" rel="nofollow noopener"><img src="${text}" alt="Ad" style="max-width:100%;height:auto;"></a>`;
        if (focusedTextarea) {
            focusedTextarea.value += '\n' + imgHtml;
            focusedTextarea.focus();
        } else {
            alert('First click the textarea where you want to insert the image.');
        }
    } catch (e) {
        alert('Unable to read clipboard. Make sure your browser allows clipboard access.');
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
