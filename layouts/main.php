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
    $title_suffix = escape($site_name) . ($site_tagline ? ' - ' . escape($site_tagline) : '');
    ?>
    <title><?php echo isset($page_title) ? escape($page_title) . ' - ' : ''; ?><?php echo $title_suffix; ?></title>
    <?php if (isset($meta_description) && !empty($meta_description)): ?>
        <meta name="description" content="<?php echo escape($meta_description); ?>">
    <?php endif; ?>
    <?php if (isset($meta_keywords) && !empty($meta_keywords)): ?>
        <meta name="keywords" content="<?php echo escape($meta_keywords); ?>">
    <?php endif; ?>
    <?php
    // Include centrally managed favicon tags
    component('favicon');
    ?>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <?php
    // The font settings are now centrally managed in config.php
    ?>
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: [<?php echo "'" . SITE_FONT_NAME . "'"; ?>, 'sans-serif'],
                        body: [<?php echo "'" . SITE_FONT_NAME . "'"; ?>, 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        secondary: '#DC2626',
                    }
                }
            }
        }
    </script>
    <style>
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
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
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
