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
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <?php
    // The font settings are now centrally managed in config.php
    ?>
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            position: relative;
            text-decoration: none !important;
            color: var(--primary-link-color) !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .article-content a:not(.custom-cta-btn)::after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: 0px;
            left: 0;
            background-color: var(--primary-link-color);
            transform-origin: bottom right;
            transition: transform 0.3s cubic-bezier(0.86, 0, 0.07, 1);
        }
        .article-content a:not(.custom-cta-btn):hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
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
    <script>
        // Debug: log selected font names and computed font-family
        (function(){
            try {
                var headingFont = '<?php echo addslashes($heading_font); ?>';
                var bodyFont = '<?php echo addslashes($body_font); ?>';
                console.info('[FONT DEBUG] requested heading font:', headingFont);
                console.info('[FONT DEBUG] requested body font:', bodyFont);
                console.info('[FONT DEBUG] google fonts url:', '<?php echo addslashes($google_fonts_url); ?>');

                var bodyStyle = window.getComputedStyle(document.body).getPropertyValue('font-family');
                var h1 = document.querySelector('h1');
                var h1Style = h1 ? window.getComputedStyle(h1).getPropertyValue('font-family') : null;
                console.info('[FONT DEBUG] computed body font-family:', bodyStyle);
                console.info('[FONT DEBUG] computed h1 font-family:', h1Style);
            } catch (e) {
                console.warn('[FONT DEBUG] error while checking fonts', e);
            }
        })();
    </script>
    
</body>
</html>
