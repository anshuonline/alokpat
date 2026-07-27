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
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-link:hover {
            background-color: #f3f4f6; /* gray-100 */
            color: #000;
        }
        .sidebar-link.active {
            background-color: #000;
            color: #fff;
        }
        .sidebar-link.active i {
            color: #fff;
        }
        .sidebar-link:not(.active) i {
            color: #6b7280; /* gray-500 */
        }
        .sidebar-link:hover:not(.active) i {
            color: #000;
        }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    
    <div class="flex min-h-screen">
        
        <!-- Mobile Overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200 z-50 flex flex-col transform -translate-x-full lg:translate-x-0">
            <div class="p-6 border-b border-gray-200">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="block">
                    <h1 class="text-2xl font-black text-black tracking-tight uppercase">
                        আলোকপাত
                    </h1>
                    <p class="text-xs text-gray-500 mt-1 font-bold tracking-widest uppercase">অ্যাডমিন প্যানেল</p>
                </a>
            </div>
            
            <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-home w-6 transition-colors"></i>
                    <span class="font-medium">ড্যাশবোর্ড</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/posts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-newspaper w-6 transition-colors"></i>
                    <span class="font-medium">সংবাদ</span>
                </a>
                
                <?php if (hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/prompts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'prompts.php' ? 'active' : 'text-gray-700'; ?>">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'prompts.php' ? 'bg-indigo-600/20 text-indigo-100' : 'bg-gray-100 text-gray-500'; ?>">
                        <i class="fas fa-robot text-lg"></i>
                    </div>
                    <span>AI প্রম্পট</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/short-links.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'short-links.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-link w-6 transition-colors"></i>
                    <span class="font-medium">শর্ট লিংক</span>
                </a>
                
                <?php if(hasPermission('manage_categories')): ?>
                <a href="<?php echo ADMIN_URL; ?>/categories.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-folder w-6 transition-colors"></i>
                    <span class="font-medium">ক্যাটাগরি</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_menus')): ?>
                <a href="<?php echo ADMIN_URL; ?>/menus.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'menus.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bars w-6 transition-colors"></i>
                    <span class="font-medium">মেনু</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_tags')): ?>
                <a href="<?php echo ADMIN_URL; ?>/tags.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'tags.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-tags w-6 transition-colors"></i>
                    <span class="font-medium">ট্যাগ</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_media')): ?>
                <a href="<?php echo ADMIN_URL; ?>/media.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-images w-6 transition-colors"></i>
                    <span class="font-medium">মিডিয়া</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_optimize')): ?>
                <a href="<?php echo ADMIN_URL; ?>/optimize.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'optimize.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-rocket w-6 transition-colors"></i>
                    <span class="font-medium">অপ্টিমাইজ</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_users')): ?>
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-users w-6 transition-colors"></i>
                    <span class="font-medium">ব্যবহারকারী</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_users')): ?>
                <a href="<?php echo ADMIN_URL; ?>/id-cards.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'id-cards.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-id-card w-6 transition-colors"></i>
                    <span class="font-medium">আইডি কার্ড</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_roles')): ?>
                <a href="<?php echo ADMIN_URL; ?>/roles.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-user-shield w-6 transition-colors"></i>
                    <span class="font-medium">রোল ও পারমিশন</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/setup-2fa.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'setup-2fa.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-shield-alt w-6 transition-colors"></i>
                    <span class="font-medium">2FA Security</span>
                </a>
                
                <?php if(hasPermission('manage_seo')): ?>
                <a href="<?php echo ADMIN_URL; ?>/seo.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-search w-6 transition-colors"></i>
                    <span class="font-medium">এসইও</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasAnyRole(['super_admin', 'admin', 'editor'])): ?>
                <a href="<?php echo ADMIN_URL; ?>/rss-feeds.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'rss-feeds.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-rss text-orange-500 w-6 transition-colors"></i>
                    <span class="font-medium">RSS Feeds</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_contacts')): ?>
                <a href="<?php echo ADMIN_URL; ?>/contacts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-envelope w-6 transition-colors"></i>
                    <span class="font-medium">যোগাযোগ (Contacts)</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('view_reports')): ?>
                <a href="<?php echo ADMIN_URL; ?>/reports.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-chart-line w-6 transition-colors"></i>
                    <span class="font-medium">রিপোর্টস</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_subscribers')): ?>
                <a href="<?php echo ADMIN_URL; ?>/subscribers.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-users-cog w-6 transition-colors"></i>
                    <span class="font-medium">সাবস্ক্রাইবার</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/notifications.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bell w-6 transition-colors"></i>
                    <span class="font-medium">নোটিফিকেশন</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_ads')): ?>
                <a href="<?php echo ADMIN_URL; ?>/ads.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'ads.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bullhorn w-6 transition-colors"></i>
                    <span class="font-medium">বিজ্ঞাপন</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/appearance.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'appearance.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-palette w-6 transition-colors"></i>
                    <span class="font-medium">এপিয়ারেন্স</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_homepage')): ?>
                <a href="<?php echo ADMIN_URL; ?>/homepage-settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'homepage-settings.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-home w-6 transition-colors"></i>
                    <span class="font-medium">হোমপেজ সেটিংস</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('publish_posts')): ?>
                    <!-- Pending Actions Notification -->
                    <?php
                        $db_conn = (new Database())->getConnection();
                        $pending_count = $db_conn->query("SELECT count(*) FROM posts WHERE status IN ('pending_review', 'pending_delete')")->fetchColumn();
                    ?>
                    <a href="<?php echo ADMIN_URL; ?>/pending-actions.php" 
                       class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'pending-actions.php' ? 'active' : 'text-gray-700'; ?>">
                        <i class="fas fa-tasks w-6 transition-colors"></i>
                        <span class="font-medium flex-1">পেন্ডিং অ্যাকশন</span>
                        <?php if ($pending_count > 0): ?>
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-cog w-6 transition-colors"></i>
                    <span class="font-medium">সেটিংস</span>
                </a>
                <?php endif; ?>
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
        <div class="flex-1 flex flex-col w-full lg:ml-64 transition-all duration-300">
            
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <button id="sidebarToggle" class="lg:hidden text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">
                                <?php echo escape(getCurrentUser()['full_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php 
                                echo ucfirst(str_replace('_', ' ', getCurrentUser()['role']));
                                ?>
                            </p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-black flex items-center justify-center text-white font-bold border-2 border-white shadow">
                            <?php echo mb_substr(getCurrentUser()['full_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        
                        <?php
                        $unread_msg_count = 0;
                        try {
                            $db_conn = (new Database())->getConnection();
                            $user_id = getCurrentUser()['id'];
                            $unread_stmt = $db_conn->prepare("SELECT COUNT(*) FROM admin_messages WHERE receiver_id = :uid AND is_read = 0");
                            $unread_stmt->execute(['uid' => $user_id]);
                            $unread_msg_count = $unread_stmt->fetchColumn();
                        } catch (Exception $e) {
                            // Table may not exist yet
                        }
                        ?>
                        <a href="<?php echo ADMIN_URL; ?>/inbox.php" class="relative text-gray-500 hover:text-indigo-600 transition-colors" title="Messages">
                            <i class="fas fa-envelope text-2xl"></i>
                            <?php if($unread_msg_count > 0): ?>
                            <span class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white shadow-sm">
                                <?php echo $unread_msg_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
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
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        if(toggleBtn) {
            toggleBtn.addEventListener('click', toggleSidebar);
        }
        if(overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }
    </script>
    
    <?php include BASE_PATH . '/admin/layouts/media-modal.php'; ?>
</body>
</html>
