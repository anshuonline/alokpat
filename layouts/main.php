<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Initialize settings helper and fetch site info
    $setting = new Setting();
    $site_name = $setting->get('site_name') ?: 'আলোকপাত';
    $site_tagline = $setting->get('site_tagline') ?: 'বাংলা সংবাদ';
    $seo_title_format = $setting->get('seo_title_format') ?: '%pagetitle% - %sitename%';
    
    $title_to_print = $seo_title_format;
    
    // Replace placeholders
    if (isset($page_title) && $page_title !== '') {
        $title_to_print = str_replace('%pagetitle%', $page_title, $title_to_print);
    } else {
        // If no page title, remove %pagetitle% and clean up leading separators
        $title_to_print = str_replace('%pagetitle%', '', $title_to_print);
        $title_to_print = ltrim($title_to_print, ' -|');
    }
    
    $title_to_print = str_replace('%sitename%', $site_name, $title_to_print);
    $title_to_print = str_replace('%sitetagline%', $site_tagline, $title_to_print);
    
    // Clean up trailing separators if tagline is empty
    if (empty($site_tagline)) {
        $title_to_print = rtrim($title_to_print, ' -|');
    }
    ?>
    <title><?php echo escape(trim($title_to_print)); ?></title>
    <?php if (isset($meta_description) && !empty($meta_description)): ?>
        <meta name="description" content="<?php echo escape($meta_description); ?>">
    <?php endif; ?>
    <?php if (isset($meta_keywords) && !empty($meta_keywords)): ?>
        <meta name="keywords" content="<?php echo escape($meta_keywords); ?>">
    <?php endif; ?>
    <?php
    $google_search_console = $setting->get('google_search_console');
    if ($google_search_console) {
        echo $google_search_console . "\n";
    }
    
    $google_analytics_code = $setting->get('google_analytics_code');
    if ($google_analytics_code) {
        echo $google_analytics_code . "\n";
    }
    
    // Include centrally managed favicon tags
    component('favicon');
    ?>
    <!-- Preconnect to external domains for faster loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://pagead2.googlesyndication.com">
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/css/glightbox.min.css" />
    <?php
    // The font settings are now centrally managed in config.php
    ?>
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?php
    $theme_color = $setting->get('theme_color') ?: 'default';
    
    // Define the full palettes for each theme
    $theme_palettes = [
        'default' => [
            50 => '#eff6ff', 100 => '#dbeafe', 200 => '#bfdbfe', 300 => '#93c5fd', 400 => '#60a5fa',
            500 => '#3b82f6', 600 => '#2563eb', 700 => '#1d4ed8', 800 => '#1e40af', 900 => '#1e3a8a',
        ],
        'ruby' => [
            50 => '#fef2f2', 100 => '#fee2e2', 200 => '#fecaca', 300 => '#fca5a5', 400 => '#f87171',
            500 => '#ef4444', 600 => '#dc2626', 700 => '#b91c1c', 800 => '#991b1b', 900 => '#7f1d1d',
        ],
        'emerald' => [
            50 => '#ecfdf5', 100 => '#d1fae5', 200 => '#a7f3d0', 300 => '#6ee7b7', 400 => '#34d399',
            500 => '#10b981', 600 => '#059669', 700 => '#047857', 800 => '#065f46', 900 => '#064e3b',
        ],
        'amber' => [
            50 => '#fffbeb', 100 => '#fef3c7', 200 => '#fde68a', 300 => '#fcd34d', 400 => '#fbbf24',
            500 => '#f59e0b', 600 => '#d97706', 700 => '#b45309', 800 => '#92400e', 900 => '#78350f',
        ],
        'violet' => [
            50 => '#f5f3ff', 100 => '#ede9fe', 200 => '#ddd6fe', 300 => '#c4b5fd', 400 => '#a78bfa',
            500 => '#8b5cf6', 600 => '#7c3aed', 700 => '#6d28d9', 800 => '#5b21b6', 900 => '#4c1d95',
        ],
        'teal' => [
            50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4', 400 => '#2dd4bf',
            500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e', 800 => '#115e59', 900 => '#134e4a',
        ],
        'rose' => [
            50 => '#fff1f2', 100 => '#ffe4e6', 200 => '#fecdd3', 300 => '#fda4af', 400 => '#fb7185',
            500 => '#f43f5e', 600 => '#e11d48', 700 => '#be123c', 800 => '#9f1239', 900 => '#881337',
        ],
        'fuchsia' => [
            50 => '#fdf4ff', 100 => '#fae8ff', 200 => '#f5d0fe', 300 => '#f0abfc', 400 => '#e879f9',
            500 => '#d946ef', 600 => '#c026d3', 700 => '#a21caf', 800 => '#86198f', 900 => '#701a75',
        ],
        'indigo' => [
            50 => '#eef2ff', 100 => '#e0e7ff', 200 => '#c7d2fe', 300 => '#a5b4fc', 400 => '#818cf8',
            500 => '#6366f1', 600 => '#4f46e5', 700 => '#4338ca', 800 => '#3730a3', 900 => '#312e81',
        ],
        'sky' => [
            50 => '#f0f9ff', 100 => '#e0f2fe', 200 => '#bae6fd', 300 => '#7dd3fc', 400 => '#38bdf8',
            500 => '#0ea5e9', 600 => '#0284c7', 700 => '#0369a1', 800 => '#075985', 900 => '#0c4a6e',
        ],
        'cyan' => [
            50 => '#ecfeff', 100 => '#cffafe', 200 => '#a5f3fc', 300 => '#67e8f9', 400 => '#22d3ee',
            500 => '#06b6d4', 600 => '#0891b2', 700 => '#0e7490', 800 => '#155e75', 900 => '#164e63',
        ],
        'lime' => [
            50 => '#f7fee7', 100 => '#ecfccb', 200 => '#d9f99d', 300 => '#bef264', 400 => '#a3e635',
            500 => '#84cc16', 600 => '#65a30d', 700 => '#4d7c0f', 800 => '#3f6212', 900 => '#365314',
        ],
        'yellow' => [
            50 => '#fefce8', 100 => '#fef9c3', 200 => '#fef08a', 300 => '#fde047', 400 => '#facc15',
            500 => '#eab308', 600 => '#ca8a04', 700 => '#a16207', 800 => '#854d0e', 900 => '#713f12',
        ],
        'orange' => [
            50 => '#fff7ed', 100 => '#ffedd5', 200 => '#fed7aa', 300 => '#fdba74', 400 => '#fb923c',
            500 => '#f97316', 600 => '#ea580c', 700 => '#c2410c', 800 => '#9a3412', 900 => '#7c2d12',
        ],
        'stone' => [
            50 => '#fafaf9', 100 => '#f5f5f4', 200 => '#e7e5e4', 300 => '#d6d3d1', 400 => '#a8a29e',
            500 => '#78716c', 600 => '#57534e', 700 => '#44403c', 800 => '#292524', 900 => '#1c1917',
        ],
        'slate' => [
            50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1', 400 => '#94a3b8',
            500 => '#64748b', 600 => '#475569', 700 => '#334155', 800 => '#1e293b', 900 => '#0f172a',
        ],
        'neelambari' => [
            50 => '#edf2ff', 100 => '#dbebff', 200 => '#bacdff', 300 => '#88a6ff', 400 => '#5476ff',
            500 => '#2d4bf3', 600 => '#1a32d4', 700 => '#1022a8', 800 => '#0d1d8a', 900 => '#0b166d',
        ],
    ];
    $active_palette = $theme_palettes[$theme_color] ?? $theme_palettes['default'];
    ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: <?php echo json_encode($active_palette); ?>,
                        secondary: '#DC2626',
                    },
                    fontFamily: {
                        sans: ['<?php echo SITE_FONT_NAME; ?>', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-primary: <?php echo $active_palette[600]; ?>;
            --color-primary-dark: <?php echo $active_palette[800]; ?>;
            --color-primary-light: <?php echo $active_palette[500]; ?>;
        }
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        body {
            font-family: <?php echo SITE_FONT_CSS; ?>;
        }
        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: <?php echo SITE_FONT_CSS; ?>;
        }
        .article-content h1, .article-content h2, .article-content h3, .article-content h4, .article-content h5, .article-content h6 {
            line-height: 1.3;
        }
        .breaking-ticker {
            animation: ticker 20s linear infinite;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Ad layout helpers */
        .ad { display: block; width: 100%; box-sizing: border-box; }

        /* Post content font size (scoped to article content) */
        .article-content .prose {
            font-size: <?php echo $post_size; ?>;
            line-height: 1.8;
        }
        
        /* Animated Links in Post Content */
        :root {
            --primary-link-color: #2563eb;
        }
        .article-content a:not(.custom-cta-btn) {
            text-decoration: underline !important;
            text-underline-offset: 4px;
            text-decoration-thickness: 1px;
            text-decoration-color: transparent !important;
            color: var(--primary-link-color) !important;
            font-weight: 600;
            transition: text-decoration-color 0.3s ease, color 0.3s ease;
        }
        .article-content a:not(.custom-cta-btn):hover {
            text-decoration-color: var(--primary-link-color) !important;
        }
        .ad { text-align: center; }
        .ad img { max-width: 100%; height: auto; display: block; margin: 0 auto; }

        /* Desktop fixed max-widths for common ad slots */
        @media (min-width: 1024px) {
            .ad-header, .ad-homepage_top, .ad-homepage_middle, .ad-footer {
                max-width: 970px; /* large leaderboard */
                margin-left: auto;
                margin-right: auto;
            }
            .ad-inline, .ad-in_article_1, .ad-in_article_2 {
                max-width: 728px; /* medium rectangle / leaderboard */
                margin-left: auto;
                margin-right: auto;
            }
            .ad-sidebar, .ad-sidebar_1, .ad-sidebar_2 {
                max-width: 300px; /* typical sidebar */
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-bengali">
    
    <?php displayFlash(); ?>
    
    <?php echo $content ?? ''; ?>
    
    <script>
        // Mobile menu is handled directly by onclick in header.php
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
    <!-- Social Embed Scripts — only loaded on article pages -->
    <?php if (!empty($load_social_sdks)): ?>
    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v17.0"></script>
    <script async src="//www.instagram.com/embed.js"></script>
    <?php endif; ?>


    
    <!-- Defer non-critical JS -->
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.3.0/js/glightbox.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Find all images inside the post content that aren't already wrapped in a link
            const contentImages = document.querySelectorAll('.article-content img');
            contentImages.forEach(img => {
                const parent = img.parentElement;
                // If the parent is not an anchor tag, wrap it
                if (parent && parent.tagName.toLowerCase() !== 'a') {
                    const a = document.createElement('a');
                    a.href = img.src;
                    a.classList.add('glightbox');
                    a.setAttribute('data-title', img.alt || '');
                    
                    // Replace image with anchor tag containing the image
                    parent.insertBefore(a, img);
                    a.appendChild(img);
                } else if (parent && parent.tagName.toLowerCase() === 'a') {
                    // If it's already a link (e.g., to an image file), add glightbox class
                    if (parent.href.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
                        parent.classList.add('glightbox');
                        if (!parent.getAttribute('data-title')) {
                            parent.setAttribute('data-title', img.alt || '');
                        }
                    }
                }
            });

            // Initialize GLightbox
            const lightbox = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true,
                zoomable: true
            });
        });

    </script>

    <?php
    $fcm_api_key = $setting->get('fcm_api_key');
    if (!empty($fcm_api_key)) {
        $fcm_project_id      = $setting->get('fcm_project_id');
        $fcm_sender_id       = $setting->get('fcm_messaging_sender_id');
        $fcm_app_id          = $setting->get('fcm_app_id');
        $fcm_vapid_key       = $setting->get('fcm_vapid_key');
        $fcm_popup_title     = $setting->get('fcm_popup_title')     ?: 'আমাদের নোটিফিকেশন সাবস্ক্রাইব করুন';
        $fcm_popup_desc      = $setting->get('fcm_popup_desc')      ?: 'সর্বশেষ খবরের আপডেট পেতে পুশ নোটিফিকেশন চালু করুন।';
        $fcm_btn_subscribe   = $setting->get('fcm_btn_subscribe')   ?: 'সাবস্ক্রাইব করুন';
        $fcm_btn_later       = $setting->get('fcm_btn_later')       ?: 'পরে';
        $fcm_popup_frequency = $setting->get('fcm_popup_frequency') ?: 'once_forever';
        $fcm_popup_delay     = (int)($setting->get('fcm_popup_delay') ?: 5);
    ?>
    <div id="fcm-popup" style="display:none;position:fixed;bottom:20px;right:20px;width:340px;max-width:calc(100vw - 32px);background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.18);border:1px solid #e5e7eb;z-index:99999;">
        <div style="padding:22px;position:relative;">
            <button onclick="fcmHide()" style="position:absolute;top:10px;right:12px;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;">&#x2715;</button>
            <div style="width:48px;height:48px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:22px;">&#x1F514;</div>
            <h4 style="font-size:17px;font-weight:700;color:#111827;text-align:center;margin:0 0 8px;"><?php echo escape($fcm_popup_title); ?></h4>
            <p style="font-size:13px;color:#6b7280;text-align:center;margin:0 0 18px;line-height:1.6;"><?php echo escape($fcm_popup_desc); ?></p>
            <div style="display:flex;gap:8px;">
                <button onclick="fcmHide()" style="flex:1;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;color:#374151;background:#fff;cursor:pointer;"><?php echo escape($fcm_btn_later); ?></button>
                <button id="fcm-sub-btn" style="flex:1;padding:9px 12px;border:none;border-radius:8px;font-size:13px;font-weight:600;color:#fff;background:#2563eb;cursor:pointer;"><?php echo escape($fcm_btn_subscribe); ?></button>
            </div>
        </div>
    </div>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js"></script>
    <script>
    (function(){
        var C={apiKey:"<?php echo addslashes($fcm_api_key);?>",projectId:"<?php echo addslashes($fcm_project_id);?>",messagingSenderId:"<?php echo addslashes($fcm_sender_id);?>",appId:"<?php echo addslashes($fcm_app_id);?>"};
        var VAPID="<?php echo addslashes($fcm_vapid_key);?>",SW="<?php echo SITE_URL;?>/firebase-messaging-sw.js",API="<?php echo SITE_URL;?>/api/fcm_subscribe.php";
        var FREQ="<?php echo $fcm_popup_frequency;?>",DELAY=<?php echo $fcm_popup_delay*1000;?>,BTN="<?php echo addslashes($fcm_btn_subscribe);?>";
        function canShow(){
            if(!('Notification' in window)||Notification.permission!=='default')return false;
            if(FREQ==='every_session')return !sessionStorage.getItem('fcm_s');
            if(FREQ==='once_daily'){var t=localStorage.getItem('fcm_d');return !t||(Date.now()-parseInt(t))>86400000;}
            return !localStorage.getItem('fcm_f');
        }
        function save(){
            if(FREQ==='every_session')sessionStorage.setItem('fcm_s','1');
            else if(FREQ==='once_daily')localStorage.setItem('fcm_d',Date.now().toString());
            else localStorage.setItem('fcm_f','1');
        }
        window.fcmHide=function(){var p=document.getElementById('fcm-popup');if(!p)return;p.style.transition='opacity .3s,transform .3s';p.style.opacity='0';p.style.transform='translateY(16px)';setTimeout(function(){p.style.display='none';},320);save();};
        function fcmShow(){var p=document.getElementById('fcm-popup');if(!p)return;p.style.display='block';p.style.opacity='0';p.style.transform='translateY(16px)';p.style.transition='opacity .35s,transform .35s';setTimeout(function(){p.style.opacity='1';p.style.transform='translateY(0)';},30);}
        function init(){
            if(typeof firebase==='undefined'){console.warn('Firebase SDK missing');return;}
            try{
                firebase.initializeApp(C);
                var msg=firebase.messaging();
                if('serviceWorker' in navigator)navigator.serviceWorker.register(SW).catch(function(e){console.log('SW:',e);});
                if(canShow())setTimeout(fcmShow,DELAY);
                var btn=document.getElementById('fcm-sub-btn');
                if(btn)btn.addEventListener('click',function(){
                    btn.textContent='⏳';btn.disabled=true;
                    Notification.requestPermission().then(function(p){
                        if(p==='granted'){
                            msg.getToken({vapidKey:VAPID}).then(function(tok){
                                if(tok){fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:tok})});btn.textContent='✅';btn.style.background='#16a34a';localStorage.setItem('fcm_f','1');setTimeout(window.fcmHide,1500);}
                            }).catch(function(e){console.log(e);btn.textContent=BTN;btn.disabled=false;});
                        }else window.fcmHide();
                    });
                });
            }catch(e){console.log('FCM:',e);}
        }
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
    })();
    </script>
    <?php } ?>
</body>
</html>
