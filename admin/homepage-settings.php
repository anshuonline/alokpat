<?php
/**
 * Homepage Settings
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$setting_model = new Setting();
$category_model = new Category();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_homepage_settings'])) {
        $selected_categories = $_POST['homepage_categories'] ?? [];
        
        // Form array order is preserved by browser, so we just use it directly!
        $final_order = array_map('intval', $selected_categories);
        $final_order = array_filter($final_order);
        
        $key = 'homepage_categories_order';
        $value = json_encode($final_order);
        
        if ($setting_model->get($key) === false) {
            $success_query = $setting_model->create($key, $value, 'json', 'Ordered category IDs for homepage');
        } else {
            $success_query = $setting_model->updateMultiple([$key => $value]);
        }
        
        if ($success_query) {
            $success = "হোমপেজ সেটিংস সফলভাবে আপডেট করা হয়েছে।";
        } else {
            $error = "সেটিংস সেভ করতে সমস্যা হয়েছে।";
        }
}

// Fetch all active categories
$all_categories = $category_model->getActive();

// Fetch current setting
$current_setting_json = $setting_model->get('homepage_categories_order');
$current_order = [];
if (!empty($current_setting_json)) {
    $decoded = json_decode($current_setting_json, true);
    if (is_array($decoded)) {
        $current_order = $decoded;
    }
}

// If no setting, we default to all categories having an implicit order based on their current fetch order
$is_first_time = empty($current_order);

// Prepare category data for display
$display_categories = [];
foreach ($all_categories as $index => $cat) {
    if ($is_first_time) {
        $display_categories[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'selected' => true,
            'order' => ($index + 1) * 10 // e.g. 10, 20, 30
        ];
    } else {
        $pos = array_search($cat['id'], $current_order);
        $display_categories[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'selected' => $pos !== false,
            'order' => $pos !== false ? ($pos + 1) * 10 : 999
        ];
    }
}

// Sort display categories by their order for better UX
usort($display_categories, function($a, $b) {
    return $a['order'] <=> $b['order'];
});

$page_title = 'হোমপেজ সেটিংস - Admin';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-gray-800">হোমপেজ ক্যাটাগরি সেটিংস</h1>
</div>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        <?php echo escape($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <?php echo escape($error); ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">হোমপেজে প্রদর্শিত ক্যাটাগরি</h2>
        <p class="text-sm text-gray-500 mt-1">এখানে আপনি নির্ধারণ করতে পারবেন হোমপেজে কোন কোন ক্যাটাগরিগুলো দেখানো হবে। চেক বক্সে টিক দিন এবং ক্যাটাগরির নামের বামপাশে ধরে <strong>ড্র্যাগ (Drag)</strong> করে উপরে-নিচে নিয়ে সিরিয়াল ঠিক করুন।</p>
    </div>
    
    <div class="p-6">
        <form action="" method="POST">
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                <i class="fas fa-arrows-alt-v"></i>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                                দেখান
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ক্যাটাগরির নাম
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="sortable-tbody">
                        <?php foreach ($display_categories as $cat): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <i class="fas fa-grip-vertical cursor-grab text-gray-400 hover:text-gray-600 handle px-2 text-lg"></i>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="homepage_categories[]" value="<?php echo $cat['id']; ?>" 
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                                       <?php echo $cat['selected'] ? 'checked' : ''; ?>>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo escape($cat['name']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="save_homepage_settings" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition font-medium">
                    সেভ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var el = document.getElementById("sortable-tbody");
        if (el) {
            new Sortable(el, {
                animation: 150,
                handle: ".handle",
                ghostClass: "bg-blue-50"
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
