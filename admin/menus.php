<?php
/**
 * Admin Menu Builder
 */
require_once '../config/config.php';
requireAuth();

requirePermission('manage_menus');
$menuModel = new Menu();
$categoryModel = new Category();

// Get all menus for dropdown
$allMenus = $menuModel->getAllMenus();
$allCategories = $categoryModel->getAll();

$edit_id = isset($_GET['menu']) ? (int)$_GET['menu'] : (count($allMenus) > 0 ? $allMenus[0]['id'] : 0);

$currentMenu = null;
$menuItems = [];
$menuLocations = [];

if ($edit_id > 0) {
    $currentMenu = $menuModel->getMenu($edit_id);
    if ($currentMenu) {
        $flatItems = $menuModel->getMenuItems($edit_id);
        $menuLocations = $menuModel->getMenuLocations($edit_id);
        
        // Build tree
        $tree = [];
        $children = [];
        foreach ($flatItems as $item) {
            if (empty($item['parent_id'])) {
                $tree[$item['id']] = $item;
                $tree[$item['id']]['children'] = [];
            } else {
                $children[$item['parent_id']][] = $item;
            }
        }
        foreach ($children as $parentId => $childItems) {
            if (isset($tree[$parentId])) {
                $tree[$parentId]['children'] = $childItems;
            }
        }
        $menuItems = array_values($tree);
    } else {
        $edit_id = 0;
    }
}

$page_title = 'মেনু (Menus)';
ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">মেনু (Menus)</h2>
            <p class="text-sm text-gray-500">আপনার ওয়েবসাইটের নেভিগেশন মেনু তৈরি এবং পরিচালনা করুন</p>
        </div>
    </div>

    <!-- Top Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row items-center gap-4">
        <div class="flex items-center space-x-3 w-full md:w-auto">
            <span class="font-medium text-gray-700">সম্পাদনা করার জন্য মেনু নির্বাচন করুন:</span>
            <select id="menuSelector" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="if(this.value !== '') window.location.href='menus.php?menu=' + this.value;">
                <?php foreach ($allMenus as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo $edit_id === $m['id'] ? 'selected' : ''; ?>>
                        <?php echo escape($m['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="text-gray-500">অথবা</span>
            <a href="menus.php?menu=0" class="text-blue-600 hover:underline font-medium">নতুন মেনু তৈরি করুন</a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Left Sidebar -->
        <div class="w-full lg:w-1/3 space-y-4">
            <?php if ($edit_id === 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm">
                    <p class="text-sm text-yellow-700">
                        আইটেম যোগ করার আগে দয়া করে ডানদিকে একটি <strong>মেনুর নাম</strong> দিন এবং সেভ করুন।
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <button type="button" class="w-full px-4 py-3 bg-gray-50 flex justify-between items-center text-left font-bold text-gray-700 hover:bg-gray-100 transition" onclick="document.getElementById('cat-list').classList.toggle('hidden')">
                        ক্যাটাগরি (Categories) <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    <div id="cat-list" class="p-4 border-t border-gray-200">
                        <div class="mb-3">
                            <input type="text" id="cat-search" onkeyup="filterMenuCategories()" placeholder="ক্যাটাগরি খুঁজুন..." class="w-full border-gray-300 rounded text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="max-h-48 overflow-y-auto space-y-2 mb-3 border border-gray-200 p-2 rounded bg-gray-50" id="cat-checkbox-list">
                            <?php foreach ($allCategories as $cat): ?>
                                <label class="flex items-center space-x-2 cat-label">
                                    <input type="checkbox" class="cat-checkbox rounded text-blue-600 focus:ring-blue-500" value="<?php echo $cat['id']; ?>" data-name="<?php echo escape($cat['name']); ?>">
                                    <span class="text-sm text-gray-700 cat-name"><?php echo escape($cat['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex justify-between items-center">
                            <label class="text-sm text-blue-600 hover:underline cursor-pointer"><input type="checkbox" onchange="document.querySelectorAll('.cat-checkbox').forEach(cb => cb.checked = this.checked);" class="mr-1">সব নির্বাচন করুন</label>
                            <button type="button" onclick="addCategoriesToMenu()" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm font-medium transition">মেনুতে যুক্ত করুন</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <button type="button" class="w-full px-4 py-3 bg-gray-50 flex justify-between items-center text-left font-bold text-gray-700 hover:bg-gray-100 transition" onclick="document.getElementById('custom-link-box').classList.toggle('hidden')">
                        কাস্টম লিংক (Custom Links) <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    <div id="custom-link-box" class="p-4 border-t border-gray-200 hidden space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">URL</label>
                            <input type="url" id="custom-url" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500" value="https://">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">লিংকের টেক্সট</label>
                            <input type="text" id="custom-text" class="w-full border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="লিংকের নাম">
                        </div>
                        <div class="text-right">
                            <button type="button" onclick="addCustomLinkToMenu()" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm font-medium transition">মেনুতে যুক্ত করুন</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side -->
        <div class="w-full lg:w-2/3 bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">মেনু স্ট্রাকচার</h3>
            </div>
            
            <form id="menuForm" onsubmit="event.preventDefault(); saveMenu();" class="p-6">
                <input type="hidden" id="menu_id" value="<?php echo $edit_id; ?>">
                
                <div class="mb-6 flex items-center space-x-4">
                    <label class="font-medium text-gray-700 whitespace-nowrap">মেনুর নাম:</label>
                    <input type="text" id="menu_name" required value="<?php echo escape($currentMenu['name'] ?? ''); ?>" placeholder="যেমন: Main Menu" class="w-full md:w-1/2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg min-h-[300px]">
                    <h4 class="text-sm font-bold text-gray-600 mb-3">মেনুর আইটেমসমূহ (Drag & Drop করে সাজান, ডানে সরিয়ে চাইল্ড বানান)</h4>
                    
                    <ul id="menuList" class="space-y-2 nested-sortable min-h-[50px]">
                        <?php 
                        function renderMenuItem($item) {
                            $typeLabel = $item['type'] === 'category' ? 'ক্যাটাগরি' : 'কাস্টম লিংক';
                            $html = '<li class="menu-item-wrapper mb-2" data-id="' . $item['id'] . '">';
                            $html .= '<div class="menu-item bg-white border border-gray-300 rounded shadow-sm flex items-center justify-between p-3 cursor-move" ';
                            $html .= 'data-type="' . escape($item['type']) . '" ';
                            $html .= 'data-type-id="' . escape($item['type_id'] ?? '') . '" ';
                            $html .= 'data-url="' . escape($item['url'] ?? '') . '">';
                            $html .= '<div class="flex items-center space-x-3 w-3/4"><i class="fas fa-arrows-alt text-gray-400"></i>';
                            $html .= '<div class="w-full"><input type="text" class="item-title w-full text-sm border-0 bg-transparent p-0 focus:ring-0 font-medium text-gray-800" value="' . escape($item['title']) . '">';
                            $html .= '<div class="text-xs text-gray-500 uppercase mt-1">' . $typeLabel . '</div></div></div>';
                            $html .= '<button type="button" onclick="this.closest(\'.menu-item-wrapper\').remove()" class="text-red-500 hover:text-red-700 p-1"><i class="fas fa-times"></i></button>';
                            $html .= '</div>';
                            
                            $html .= '<ul class="nested-sortable min-h-[10px] pl-8 mt-2 space-y-2">';
                            if (!empty($item['children'])) {
                                foreach ($item['children'] as $child) {
                                    $html .= renderMenuItem($child);
                                }
                            }
                            $html .= '</ul></li>';
                            return $html;
                        }

                        foreach ($menuItems as $item) {
                            echo renderMenuItem($item);
                        }
                        ?>
                    </ul>
                    
                    <div id="empty-menu-msg" class="text-gray-400 text-center py-10 <?php echo empty($menuItems) ? '' : 'hidden'; ?>">
                        বাম পাশ থেকে মেনুতে আইটেম যোগ করুন
                    </div>
                </div>

                <!-- Menu Locations -->
                <div class="mt-8">
                    <h4 class="font-bold text-gray-800 mb-3">মেনু সেটিং (Menu Settings)</h4>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="loc_primary" class="rounded text-blue-600 focus:ring-blue-500" <?php echo in_array('primary', $menuLocations) ? 'checked' : ''; ?>>
                            <span class="text-gray-700">Primary Menu (হেডারে দেখাবে)</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="loc_mobile" class="rounded text-blue-600 focus:ring-blue-500" <?php echo in_array('mobile', $menuLocations) ? 'checked' : ''; ?>>
                            <span class="text-gray-700">Mobile Menu (মোবাইলে দেখাবে)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-200 flex justify-between items-center">
                    <?php if ($edit_id > 0): ?>
                        <button type="button" onclick="deleteMenu(<?php echo $edit_id; ?>)" class="text-red-600 hover:text-red-800 font-medium text-sm underline">মেনু মুছুন</button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold shadow-md transition">
                        মেনু সেভ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    let tempIdCounter = 1;

    function initSortable(el) {
        new Sortable(el, {
            group: 'nested',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'bg-blue-50',
            onSort: function() {
                checkEmptyMenu();
            }
        });
    }

    document.querySelectorAll('.nested-sortable').forEach(el => initSortable(el));

    function checkEmptyMenu() {
        const rootList = document.getElementById('menuList');
        if (rootList.children.length === 0) {
            document.getElementById('empty-menu-msg').classList.remove('hidden');
        } else {
            document.getElementById('empty-menu-msg').classList.add('hidden');
        }
    }

    function createMenuItemHTML(title, type, typeId, url) {
        const typeLabel = type === 'category' ? 'ক্যাটাগরি' : 'কাস্টম লিংক';
        const tempId = 'tmp_' + tempIdCounter++;
        return `
            <li class="menu-item-wrapper mb-2" data-id="${tempId}">
                <div class="menu-item bg-white border border-gray-300 rounded shadow-sm flex items-center justify-between p-3 cursor-move" 
                    data-type="${type}" 
                    data-type-id="${typeId}" 
                    data-url="${url}">
                    <div class="flex items-center space-x-3 w-3/4">
                        <i class="fas fa-arrows-alt text-gray-400"></i>
                        <div class="w-full">
                            <input type="text" class="item-title w-full text-sm border-0 bg-transparent p-0 focus:ring-0 font-medium text-gray-800" value="${title}">
                            <div class="text-xs text-gray-500 uppercase mt-1">${typeLabel}</div>
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('.menu-item-wrapper').remove(); checkEmptyMenu();" class="text-red-500 hover:text-red-700 p-1"><i class="fas fa-times"></i></button>
                </div>
                <ul class="nested-sortable min-h-[10px] pl-8 mt-2 space-y-2"></ul>
            </li>
        `;
    }

    function addCategoriesToMenu() {
        const checkboxes = document.querySelectorAll('.cat-checkbox:checked');
        checkboxes.forEach(cb => {
            const title = cb.getAttribute('data-name');
            const id = cb.value;
            const html = createMenuItemHTML(title, 'category', id, '');
            document.getElementById('menuList').insertAdjacentHTML('beforeend', html);
            
            // Initialize Sortable on the newly created ul
            const newUl = document.getElementById('menuList').lastElementChild.querySelector('ul');
            initSortable(newUl);
            
            cb.checked = false;
        });
        checkEmptyMenu();
    }

    function addCustomLinkToMenu() {
        const url = document.getElementById('custom-url').value;
        const text = document.getElementById('custom-text').value;
        if (url && text) {
            const html = createMenuItemHTML(text, 'custom', '', url);
            document.getElementById('menuList').insertAdjacentHTML('beforeend', html);
            
            // Initialize Sortable on the newly created ul
            const newUl = document.getElementById('menuList').lastElementChild.querySelector('ul');
            initSortable(newUl);
            
            document.getElementById('custom-text').value = '';
            document.getElementById('custom-url').value = 'https://';
            checkEmptyMenu();
        } else {
            alert('URL এবং লিংকের টেক্সট প্রদান করুন।');
        }
    }

    function saveMenu() {
        const menuId = document.getElementById('menu_id').value;
        const menuName = document.getElementById('menu_name').value;
        
        const locations = [];
        if(document.getElementById('loc_primary').checked) locations.push('primary');
        if(document.getElementById('loc_mobile').checked) locations.push('mobile');

        const items = [];
        let orderCount = 0;

        function traverseTree(ul, parentId) {
            Array.from(ul.children).forEach(li => {
                const menuItem = li.querySelector('.menu-item');
                const myId = li.getAttribute('data-id');
                
                items.push({
                    id: myId,
                    parent_id: parentId,
                    title: menuItem.querySelector('.item-title').value,
                    type: menuItem.getAttribute('data-type'),
                    type_id: menuItem.getAttribute('data-type-id'),
                    url: menuItem.getAttribute('data-url'),
                    order: orderCount++
                });
                
                const childUl = li.querySelector('ul');
                if (childUl && childUl.children.length > 0) {
                    traverseTree(childUl, myId);
                }
            });
        }

        traverseTree(document.getElementById('menuList'), null);

        fetch('<?php echo ADMIN_URL; ?>/ajax/save-menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                menu_id: menuId,
                name: menuName,
                locations: locations,
                items: items
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('মেনু সফলভাবে সেভ হয়েছে!');
                window.location.href = 'menus.php?menu=' + (menuId == 0 ? data.new_id : menuId);
            } else {
                alert(data.message || 'Error saving menu');
            }
        })
        .catch(err => {
            alert('Server Error!');
            console.error(err);
        });
    }

    function deleteMenu(id) {
        if (confirm('আপনি কি নিশ্চিত যে এই মেনুটি মুছতে চান?')) {
            fetch('<?php echo ADMIN_URL; ?>/ajax/delete-menu.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'menus.php';
                } else {
                    alert(data.message);
                }
            });
        }
    }

    function filterMenuCategories() {
        var input, filter, div, labels, span, i, txtValue;
        input = document.getElementById("cat-search");
        filter = input.value.toUpperCase();
        div = document.getElementById("cat-checkbox-list");
        labels = div.getElementsByClassName("cat-label");
        for (i = 0; i < labels.length; i++) {
            span = labels[i].getElementsByClassName("cat-name")[0];
            if (span) {
                txtValue = span.textContent || span.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    labels[i].style.display = "";
                } else {
                    labels[i].style.display = "none";
                }
            }       
        }
    }
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
