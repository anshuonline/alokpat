<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? escape($page_title) . ' - ' : ''; ?>আলোকপাত অ্যাডমিন</title>
    <?php component('favicon'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: <?php echo SITE_FONT_CSS; ?>;
        }
        .sidebar {
            transition: all 0.3s;
        }
        .sidebar-link:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, transparent 100%);
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, transparent 100%);
            border-right: 3px solid #3B82F6;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex h-screen">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 bg-white shadow-xl z-50 flex flex-col">
            <div class="p-6 border-b">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="block">
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                        আলোকপাত
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">অ্যাডমিন প্যানেল</p>
                </a>
            </div>
            
            <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span class="font-medium">ড্যাশবোর্ড</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/posts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper w-6"></i>
                    <span class="font-medium">সংবাদ</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/categories.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-folder w-6"></i>
                    <span class="font-medium">ক্যাটাগরি</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/menus.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'menus.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bars w-6"></i>
                    <span class="font-medium">মেনু</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/tags.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'tags.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags w-6"></i>
                    <span class="font-medium">ট্যাগ</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/media.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'active' : ''; ?>">
                    <i class="fas fa-images w-6"></i>
                    <span class="font-medium">মিডিয়া</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span class="font-medium">ব্যবহারকারী</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/seo.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search w-6"></i>
                    <span class="font-medium">এসইও</span>
                </a>
                
                <?php if(hasAnyRole(['admin', 'super_admin'])): ?>
                <a href="<?php echo ADMIN_URL; ?>/contacts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope w-6"></i>
                    <span class="font-medium">যোগাযোগ (Contacts)</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasAnyRole(['super_admin'])): ?>
                <a href="<?php echo ADMIN_URL; ?>/subscribers.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog w-6"></i>
                    <span class="font-medium">সাবস্ক্রাইবার</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/ads.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'ads.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn w-6"></i>
                    <span class="font-medium">বিজ্ঞাপন</span>
                </a>
                <a href="<?php echo ADMIN_URL; ?>/appearance.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'appearance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-palette w-6"></i>
                    <span class="font-medium">এপিয়ারেন্স</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/homepage-settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'homepage-settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span class="font-medium">হোমপেজ সেটিংস</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-blue-600 transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog w-6"></i>
                    <span class="font-medium">সেটিংস</span>
                </a>
            </nav>
            
            <div class="p-4 border-t bg-white shrink-0">
                <a href="<?php echo ADMIN_URL; ?>/logout.php" 
                   class="flex items-center px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-all">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="font-medium">লগআউট</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64">
            
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <button id="sidebarToggle" class="lg:hidden text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">
                                <?php echo escape(getCurrentUser()['full_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php 
                                $roles = [
                                    'super_admin' => 'সুপার অ্যাডমিন',
                                    'admin' => 'অ্যাডমিন',
                                    'editor' => 'এডিটর',
                                    'writer' => 'লেখক',
                                    'seo_manager' => 'এসইও ম্যানেজার'
                                ];
                                echo $roles[getCurrentUser()['role']] ?? getCurrentUser()['role'];
                                ?>
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold">
                            <?php echo mb_substr(getCurrentUser()['full_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if ($flash = getFlash()): ?>
                    <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo escape($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php echo $content ?? ''; ?>
            </main>
            
        </div>
        
    </div>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
    
    <?php include BASE_PATH . '/admin/layouts/media-modal.php'; ?>
</body>
</html>
