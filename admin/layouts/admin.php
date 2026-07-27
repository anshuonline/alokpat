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
            /* Sidebar Minimized State */
        body.sidebar-mini #sidebar { width: 5rem; }
        body.sidebar-mini .sidebar-text { display: none; }
        body.sidebar-mini #logo-full { display: none; }
        body.sidebar-mini #logo-mini { display: block; }
        body.sidebar-mini .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; }
        body.sidebar-mini .sidebar-link > i,
        body.sidebar-mini .sidebar-link > div { margin-right: 0; }
        @media (min-width: 1024px) {
            body.sidebar-mini .lg\:ml-64 { margin-left: 5rem; }
        }
        
        /* Hide scrollbar */
        .sidebar nav::-webkit-scrollbar { display: none; }
        .sidebar nav { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    
    <div class="flex min-h-screen">
        
        <!-- Mobile Overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200 z-50 flex flex-col transform -translate-x-full lg:translate-x-0">
            <div class="p-6 border-b border-gray-200">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="block text-center lg:text-left">
                    <h1 id="logo-full" class="text-2xl font-black text-black tracking-tight uppercase sidebar-text">
                        আলোকপাত
                    </h1>
                    <h1 id="logo-mini" class="text-2xl font-black text-black tracking-tight uppercase hidden">
                        আ
                    </h1>
                    <p class="text-xs text-gray-500 mt-1 font-bold tracking-widest uppercase sidebar-text">Admin Panel</p>
                </a>
            </div>
            
            <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
                <a href="<?php echo ADMIN_URL; ?>/dashboard.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-home w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Dashboard</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/posts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'posts.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-newspaper w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Posts</span>
                </a>
                
                <?php if (hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/prompts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'prompts.php' ? 'active' : 'text-gray-700'; ?>">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'prompts.php' ? 'bg-indigo-600/20 text-indigo-100' : 'bg-gray-100 text-gray-500'; ?>">
                        <i class="fas fa-robot text-lg"></i>
                    </div>
                    <span class="sidebar-text">AI Prompts</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/short-links.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'short-links.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-link w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Short Links</span>
                </a>
                
                <?php if(hasPermission('manage_categories')): ?>
                <a href="<?php echo ADMIN_URL; ?>/categories.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-folder w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Categories</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_menus')): ?>
                <a href="<?php echo ADMIN_URL; ?>/menus.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'menus.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bars w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Menus</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_tags')): ?>
                <a href="<?php echo ADMIN_URL; ?>/tags.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'tags.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-tags w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Tags</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_media')): ?>
                <a href="<?php echo ADMIN_URL; ?>/media.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-images w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Media</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_optimize')): ?>
                <a href="<?php echo ADMIN_URL; ?>/optimize.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'optimize.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-rocket w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Optimize</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_users')): ?>
                <a href="<?php echo ADMIN_URL; ?>/users.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-users w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Users</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_users')): ?>
                <a href="<?php echo ADMIN_URL; ?>/id-cards.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'id-cards.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-id-card w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">ID Cards</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_roles')): ?>
                <a href="<?php echo ADMIN_URL; ?>/roles.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-user-shield w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Roles & Permissions</span>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo ADMIN_URL; ?>/setup-2fa.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'setup-2fa.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-shield-alt w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">2FA Security</span>
                </a>
                
                <?php if(hasPermission('manage_seo')): ?>
                <a href="<?php echo ADMIN_URL; ?>/seo.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-search w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">SEO</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasAnyRole(['super_admin', 'admin', 'editor'])): ?>
                <a href="<?php echo ADMIN_URL; ?>/rss-feeds.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'rss-feeds.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-rss text-orange-500 w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">RSS Feeds</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_contacts')): ?>
                <a href="<?php echo ADMIN_URL; ?>/contacts.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-envelope w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Contacts</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('view_reports')): ?>
                <a href="<?php echo ADMIN_URL; ?>/reports.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-chart-line w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Reports</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_subscribers')): ?>
                <a href="<?php echo ADMIN_URL; ?>/subscribers.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-users-cog w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Subscribers</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/notifications.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bell w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Notifications</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_ads')): ?>
                <a href="<?php echo ADMIN_URL; ?>/ads.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'ads.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-bullhorn w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Ads</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/appearance.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'appearance.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-palette w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Appearance</span>
                </a>
                <?php endif; ?>
                
                <?php if(hasPermission('manage_homepage')): ?>
                <a href="<?php echo ADMIN_URL; ?>/homepage-settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'homepage-settings.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-home w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Homepage Settings</span>
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
                        <span class="font-medium flex-1 sidebar-text">Pending Actions</span>
                        <?php if ($pending_count > 0): ?>
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                
                <?php if(hasPermission('manage_settings')): ?>
                <a href="<?php echo ADMIN_URL; ?>/settings.php" 
                   class="sidebar-link flex items-center px-4 py-3 rounded-lg font-bold transition-all <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : 'text-gray-700'; ?>">
                    <i class="fas fa-cog w-6 transition-colors"></i>
                    <span class="font-medium sidebar-text">Settings</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="p-4 border-t bg-white shrink-0">
                <a href="<?php echo ADMIN_URL; ?>/logout.php" 
                   class="flex items-center px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition-all">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span class="font-medium sidebar-text">Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col w-full lg:ml-64 transition-all duration-300">
            
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <button id="desktopSidebarToggle" class="hidden lg:block text-gray-600 hover:text-gray-900 transition-colors" title="Toggle Sidebar">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Search Bar -->
                        <div class="hidden md:block relative ml-4">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                            <input type="search" id="adminSearch" placeholder="Search menu..." class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-gray-50 focus:bg-white">
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-6 ml-auto">
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
                        <a href="<?php echo ADMIN_URL; ?>/inbox.php" class="relative text-gray-500 hover:text-indigo-600 transition-colors ml-4" title="Messages">
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
        const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
        if (desktopSidebarToggle) {
            desktopSidebarToggle.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-mini');
                if (document.body.classList.contains('sidebar-mini')) {
                    localStorage.setItem('sidebarState', 'minimized');
                } else {
                    localStorage.setItem('sidebarState', 'expanded');
                }
            });
            if (localStorage.getItem('sidebarState') === 'minimized') {
                document.body.classList.add('sidebar-mini');
            }
        }
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
        
        // Sidebar Search Filter
        const adminSearch = document.getElementById('adminSearch');
        if (adminSearch) {
            adminSearch.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                const links = document.querySelectorAll('.sidebar-link');
                links.forEach(link => {
                    const text = link.textContent.toLowerCase();
                    if (text.includes(term)) {
                        link.style.display = 'flex';
                    } else {
                        link.style.display = 'none';
                    }
                });
            });
        }
    </script>
    
    <?php include BASE_PATH . '/admin/layouts/media-modal.php'; ?>
</body>
</html>
