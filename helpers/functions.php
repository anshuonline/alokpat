<?php
/**
 * Helper Functions
 * General utility functions for Alokpath CMS
 * 
 * @package Alokpath\Helpers
 */

/**
 * Sanitize input data
 * 
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return strip_tags(trim($data ?? ''));
}

/**
 * Escape output for HTML
 * 
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8', false);
}

/**
 * Generate slug from string
 * 
 * @param string $text
 * @return string
 */
function generateSlug($text) {
    // For Bengali text, use transliteration or keep as-is with URL encoding
    $text = trim($text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-zA-Z0-9\-_\p{Bengali}]/u', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Generate a unique slug for a given table
 * 
 * @param string $text
 * @param string $table
 * @param string $column
 * @param int $exclude_id
 * @return string
 */
function generateUniqueSlug($text, $table = 'posts', $column = 'slug', $exclude_id = null) {
    $slug = generateSlug($text);
    if (empty($slug)) {
        $slug = 'post-' . time();
    }
    
    $original_slug = $slug;
    $count = 1;
    
    try {
        $db = (new Database())->getConnection();
        
        while (true) {
            $sql = "SELECT id FROM {$table} WHERE {$column} = ?";
            $params = [$slug];
            
            if ($exclude_id !== null) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() == 0) {
                break;
            }
            
            $slug = $original_slug . '-' . $count;
            $count++;
        }
    } catch(PDOException $e) {
        $slug = $original_slug . '-' . time();
    }
    
    return $slug;
}

/**
 * Redirect to URL
 * 
 * @param string $url
 * @param int $code
 */
function redirect($url, $code = 302) {
    http_response_code($code);
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 * 
 * @param string $type
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message if it exists
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = escape($flash['message']);
        
        $bg = 'bg-blue-100';
        $text = 'text-blue-800';
        $icon = 'fa-info-circle';
        
        if ($type === 'success') {
            $bg = 'bg-green-100';
            $text = 'text-green-800';
            $icon = 'fa-check-circle';
        } elseif ($type === 'error') {
            $bg = 'bg-red-100';
            $text = 'text-red-800';
            $icon = 'fa-exclamation-circle';
        } elseif ($type === 'warning') {
            $bg = 'bg-yellow-100';
            $text = 'text-yellow-800';
            $icon = 'fa-exclamation-triangle';
        }
        
        echo "<div class='max-w-4xl mx-auto mt-6 px-4'>";
        echo "<div class='{$bg} {$text} p-4 rounded-lg flex items-center shadow-sm animate-fade-in-down'>";
        echo "<i class='fas {$icon} mr-3 text-lg'></i>";
        echo "<span class='font-medium'>{$message}</span>";
        echo "<button type='button' class='ml-auto text-gray-500 hover:text-gray-700' onclick='this.parentElement.style.display=\"none\"'>";
        echo "<i class='fas fa-times'></i></button>";
        echo "</div>";
        echo "</div>";
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 * 
 * @return array|false
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        $user = new User();
        return $user->getById($_SESSION['user_id']);
    }
    return false;
}

/**
 * Check if user has specific role
 * 
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    $currentUser = getCurrentUser();
    return $currentUser && $currentUser['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 * 
 * @param array $roles
 * @return bool
 */
function hasAnyRole($roles) {
    $currentUser = getCurrentUser();
    return $currentUser && in_array($currentUser['role'], $roles);
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        setFlash('error', 'প্রথমে লগইন করুন');
        redirect(ADMIN_URL . '/login.php');
    }
    
    // Check 2FA setup enforcement
    $current_page = basename($_SERVER['PHP_SELF']);
    $allowed_pages = ['setup-2fa.php', 'logout.php', 'login.php', 'verify-2fa.php'];
    
    if (!in_array($current_page, $allowed_pages)) {
        $user = getCurrentUser();
        if ($user && isset($user['two_factor_enabled']) && $user['two_factor_enabled'] == 0) {
            setFlash('warning', 'আপনার 2FA সেটআপ করা নেই। এটি এখনই সেটআপ করা বাধ্যতামূলক!');
            redirect(ADMIN_URL . '/setup-2fa.php');
        }
    }
}

/**
 * Require specific role
 * 
 * @param string $role
 */
function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        setFlash('error', 'আপনার এই কাজ করার অনুমতি নেই');
        redirect(ADMIN_URL . '/dashboard.php');
    }
}

/**
 * Check if user has specific permission
 * 
 * @param string $permission
 * @return bool
 */
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    
    static $user_permissions = null;
    
    if ($user_permissions === null) {
        $user = getCurrentUser();
        if (!$user) return false;
        
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT permissions FROM roles WHERE slug = ?");
            $stmt->execute([$user['role']]);
            $row = $stmt->fetch();
            
            if ($row && !empty($row['permissions'])) {
                $user_permissions = json_decode($row['permissions'], true);
                if (!is_array($user_permissions)) $user_permissions = [];
            } else {
                $user_permissions = [];
            }
        } catch(PDOException $e) {
            $user_permissions = [];
        }
    }
    
    // Super Admin wildcard
    if (in_array('*', $user_permissions)) {
        return true;
    }
    
    return in_array($permission, $user_permissions);
}

/**
 * Require specific permission
 * 
 * @param string $permission
 */
function requirePermission($permission) {
    requireAuth();
    if (!hasPermission($permission)) {
        setFlash('error', 'আপনার এই কাজ করার বা এই পেজে প্রবেশ করার অনুমতি নেই।');
        redirect(ADMIN_URL . '/dashboard.php');
    }
}

/**
 * Check if user has ANY of the specific permissions
 * 
 * @param array $permissions
 * @return bool
 */
function hasAnyPermission($permissions) {
    foreach ($permissions as $perm) {
        if (hasPermission($perm)) return true;
    }
    return false;
}

/**
 * Generate CSRF token
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * 
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Format date in Bengali
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDateBengali($date, $format = 'd F Y, h:i A') {
    $timestamp = strtotime($date);
    $english_date = date($format, $timestamp);
    
    // Bengali numerals
    $bengali_numbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english_numbers, $bengali_numbers, $english_date);
}

/**
 * Format number in Bengali
 * 
 * @param int|float $number
 * @return string
 */
function formatNumberBengali($number) {
    $bengali_numbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    $english_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english_numbers, $bengali_numbers, $number);
}

/**
 * Truncate text
 * 
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get time ago in Bengali
 * 
 * @param string $date
 * @return string
 */
function timeAgoBengali($date) {
    $timestamp = strtotime($date);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'এইমাত্র';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return formatNumberBengali($mins) . ' মিনিট আগে';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return formatNumberBengali($hours) . ' ঘণ্টা আগে';
    } elseif ($difference < 2592000) {
        $days = floor($difference / 86400);
        return formatNumberBengali($days) . ' দিন আগে';
    } else {
        return formatDateBengali($date, 'd F Y');
    }
}

/**
 * Upload file
 * 
 * @param array $file
 * @param string $directory
 * @return array|false
 */
function uploadFile($file, $directory = 'uploads') {
    $allowed_extensions = ALLOWED_EXTENSIONS;
    $max_size = MAX_FILE_SIZE;
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'ফাইল আপলোডে সমস্যা হয়েছে'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['error' => 'ফাইলের সাইজ ' . ($max_size / 1048576) . 'MB এর বেশি'];
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate extension
    if (!in_array($extension, $allowed_extensions)) {
        return ['error' => 'শুধু ' . implode(', ', $allowed_extensions) . ' ফাইল আপলোড করা যাবে'];
    }
    
    // Create directory structure by date
    if (UPLOAD_DIR_STRUCTURE) {
        $directory .= '/' . date('Y/m/d');
    }
    
    $upload_path = BASE_PATH . '/' . $directory;
    
    // Create directory if not exists
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    // Generate unique base filename
    $base_filename = uniqid() . '_' . time();
    $filename = $base_filename . '.' . $extension;
    $filepath = $upload_path . '/' . $filename;
    
    // Check if the file is an image that can be converted to AVIF
    $image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($extension, $image_types) && function_exists('imagewebp')) {
        $webp_filename = $base_filename . '.webp';
        $webp_filepath = $upload_path . '/' . $webp_filename;
        
        $image = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'png':
                $image = @imagecreatefrompng($file['tmp_name']);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'gif':
                $image = @imagecreatefromgif($file['tmp_name']);
                if ($image) {
                    imagepalettetotruecolor($image);
                }
                break;
            case 'webp':
                $image = @imagecreatefromwebp($file['tmp_name']);
                break;
        }
        
        if ($image !== false && $image !== null) {
            // Resize if too large
            $width = imagesx($image);
            $height = imagesy($image);
            $max_width = 1200; // Resize large images to help hit <100kb
            
            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = floor($height * ($new_width / $width));
                
                $resized = imagecreatetruecolor($new_width, $new_height);
                // Handle transparency for resized image
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
                
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Fast compression to target < 100KB
            // Step 1: Compress at quality 70
            imagewebp($image, $webp_filepath, 70);
            clearstatcache(true, $webp_filepath);
            
            $success = false;
            if (file_exists($webp_filepath)) {
                $current_size = filesize($webp_filepath);
                $target_size = 100 * 1024; // 100 KB
                
                if ($current_size > $target_size) {
                    // Step 2: If still > 100KB, compress at quality 30
                    imagewebp($image, $webp_filepath, 30);
                    clearstatcache(true, $webp_filepath);
                    
                    if (file_exists($webp_filepath)) {
                        $current_size = filesize($webp_filepath);
                        // If it's STILL > 100KB, we force extreme compression (quality 10)
                        if ($current_size > $target_size) {
                             imagewebp($image, $webp_filepath, 10);
                        }
                    }
                }
                $success = true;
            }

            imagedestroy($image);

            if ($success) {
                $file_url = SITE_URL . '/' . $directory . '/' . $webp_filename;
                return [
                    'filename' => $webp_filename,
                    'filepath' => $webp_filepath,
                    'file_url' => $file_url,
                    'file_size' => filesize($webp_filepath),
                    'original_size' => $file['size'], // Return original size
                    'mime_type' => 'image/webp'
                ];
            }
        }
    }
    
    // Fallback: Move uploaded file if conversion failed or file is not an image
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $file_url = SITE_URL . '/' . $directory . '/' . $filename;
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'file_url' => $file_url,
            'file_size' => $file['size'],
            'original_size' => $file['size'], // Return original size
            'mime_type' => $file['type']
        ];
    }
    
    return ['error' => 'ফাইল আপলোড করা যায়নি'];
}

/**
 * Get image dimensions
 * 
 * @param string $filepath
 * @return array|false
 */
function getImageDimensions($filepath) {
    if (file_exists($filepath)) {
        $dimensions = getimagesize($filepath);
        if ($dimensions) {
            return ['width' => $dimensions[0], 'height' => $dimensions[1]];
        }
    }
    return false;
}

/**
 * Compress image
 * 
 * @param string $source
 * @param string $destination
 * @param int $quality
 * @return bool
 */
function compressImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
        return true;
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, 9);
        return true;
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $destination, $quality);
        return true;
    } elseif ($info['mime'] == 'image/avif') {
        $image = imagecreatefromavif($source);
        imageavif($image, $destination, $quality);
        return true;
    }
    
    return false;
}

/**
 * Include component file
 * 
 * @param string $component
 * @param array $data
 */
function component($component, $data = []) {
    extract($data);
    $file = BASE_PATH . '/components/' . $component . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

/**
 * Include partial file
 * 
 * @param string $partial
 * @param array $data
 */
function partial($partial, $data = []) {
    extract($data);
    $file = BASE_PATH . '/partials/' . $partial . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

/**
 * Get asset URL
 * 
 * @param string $path
 * @return string
 */
function asset($path) {
    return SITE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Get upload URL
 * 
 * @param string $path
 * @return string
 */
function upload_url($path = '') {
    return UPLOAD_URL . '/' . ltrim($path, '/');
}

/**
 * Render ad by position (reads settings)
 * @param string $position
 */
function render_ad($position) {
    // sanitize position
    $position = preg_replace('/[^a-z0-9_\-]/i', '', $position);
    component('ad-banner', ['position' => $position]);
}

/**
 * Build pretty URL for a post by slug
 * @param string $slug
 * @return string
 */
function url_for_post($post) {
    if (is_array($post)) {
        $category_slug = !empty($post['category_slug']) ? $post['category_slug'] : 'article';
        $slug = $post['slug'] ?? '';
    } else {
        // Fallback if someone passes just a string
        $category_slug = 'article';
        $slug = $post;
    }
    return rtrim(SITE_URL, '/') . '/' . rawurlencode($category_slug) . '/' . rawurlencode($slug) . '.html';
}

/**
 * Inject inline ads into article HTML after configured paragraph counts.
 * Reads settings: ad_inject_positions (comma separated like "2,5") and uses ad_inline positions ad_in_article_1, ad_in_article_2...
 * @param string $html
 * @param int $postId (optional)
 * @return string
 */
function inject_ads_into_content($html, $postId = null) {
    $setting = new Setting();
    $positions_setting = $setting->get('ad_inject_positions');
    if (!$positions_setting) {
        return $html;
    }

    // parse positions like "2,5"
    $positions = array_filter(array_map('trim', explode(',', $positions_setting)), 'strlen');
    $positions = array_map('intval', $positions);
    if (empty($positions)) return $html;

    // Split by paragraphs
    // Use DOMDocument to be more robust
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>');
    $container = $doc->getElementsByTagName('div')->item(0);
    if (!$container) return $html;

    $pCount = 0;
    $positions = array_map('intval', $positions);
    sort($positions);

    // iterate children and insert ad nodes when needed
    $children = [];
    foreach ($container->childNodes as $node) {
        $children[] = $node;
    }

    $inserts = 0;
    foreach ($children as $node) {
        if ($node->nodeType === XML_ELEMENT_NODE && strtolower($node->nodeName) === 'p') {
            $pCount++;
        }

        // check if next position matches
        if (in_array($pCount, $positions)) {
            // build ad HTML from corresponding setting
            $index = array_search($pCount, $positions) + 1; // 1-based
            $posName = 'in_article_' . $index; // ad_in_article_1, ad_in_article_2...
            $adCode = $setting->get("ad_{$posName}_code");
            $enabled = $setting->get("ad_{$posName}_enabled");
            if ($enabled && !empty(trim((string)$adCode))) {
                // safer insertion: parse ad HTML in a temporary DOM and import nodes
                $adHtml = '<div class="ad ad-inline ad-' . htmlspecialchars($posName) . '">' . $adCode . '</div>';
                $tmp = new DOMDocument();
                libxml_use_internal_errors(true);
                $tmp->loadHTML('<?xml encoding="utf-8" ?>' . $adHtml);
                $tmpContainer = $tmp->getElementsByTagName('body')->item(0);
                $ref = $node->nextSibling;
                if ($tmpContainer) {
                    foreach ($tmpContainer->childNodes as $child) {
                        $import = $doc->importNode($child, true);
                        if ($ref) {
                            $container->insertBefore($import, $ref);
                        } else {
                            $container->appendChild($import);
                        }
                    }
                }
                $inserts++;
            }
        }
    }

    $newHtml = '';
    foreach ($container->childNodes as $node) {
        $newHtml .= $doc->saveHTML($node);
    }
    return $newHtml;
}

/**
 * Minify HTML safely (ignores script, style, pre, textarea)
 * 
 * @param string $html
 * @return string
 */
function minify_html_safe($html) {
    // Extract tags where whitespace matters
    preg_match_all('!(<(?:code|pre|script|style|textarea)[^>]*>.*?</(?:code|pre|script|style|textarea)>)!is', $html, $matches);
    $blocks = $matches[1];
    
    // Replace them with placeholders
    $html = preg_replace('!(<(?:code|pre|script|style|textarea)[^>]*>.*?</(?:code|pre|script|style|textarea)>)!is', '@@@BLOCK@@@', $html);
    
    // Minify the rest (remove comments, collapse whitespace)
    $html = preg_replace('/<!--(?!<!)[^\[>].*?-->/s', '', $html); // Remove HTML comments
    $html = preg_replace('/\s+/', ' ', $html); // Collapse whitespace
    $html = preg_replace('/>\s+</', '><', $html); // Remove space between tags
    
    // Put the blocks back
    foreach ($blocks as $block) {
        // Use explode/implode or preg_replace with limit 1
        $pos = strpos($html, '@@@BLOCK@@@');
        if ($pos !== false) {
            $html = substr_replace($html, $block, $pos, strlen('@@@BLOCK@@@'));
        }
    }
    
    return trim($html);
}

/**
 * Clear homepage and category caches
 */
function clear_page_caches() {
    $index_cache = __DIR__ . '/../cache/index.html';
    if (file_exists($index_cache)) @unlink($index_cache);
    
    $cat_dir = __DIR__ . '/../cache/categories/';
    if (is_dir($cat_dir)) {
        $files = glob($cat_dir . '*.html');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
    }
}
