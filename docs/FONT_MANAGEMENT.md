# ফন্ট ব্যবস্থাপনা সিস্টেম - Font Management System

## বিবরণ (Description)

Alokpath CMS এখন সম্পূর্ণ ডায়নামিক ফন্ট ম্যানেজমেন্ট সিস্টেম সহ আসে। অ্যাডমিনরা সহজেই পুরো সাইটের ফন্ট পরিবর্তন করতে পারবেন অ্যাডমিন প্যানেল থেকে।

Alokpath CMS now comes with a complete dynamic font management system. Admins can easily change fonts site-wide from the admin panel.

## বৈশিষ্ট্য (Features)

✅ **20+ বাংলা ফন্ট** - 20+ Bengali fonts available  
✅ **ডায়নামিক ফন্ট পরিবর্তন** - Dynamic font switching  
✅ **লাইভ প্রিভিউ** - Live font preview before saving  
✅ **হেডিং ও বডি ফন্ট আলাদা** - Separate heading and body fonts  
✅ **CKEditor ফন্ট ইন্টিগ্রেশন** - CKEditor font integration  
✅ **Google Fonts API** - Automatic Google Fonts loading  
✅ **পুরো সাইটে প্রভাব** - Site-wide font changes  

## উপলব্ধ বাংলা ফন্টসমূহ (Available Bengali Fonts)

1. **Noto Sans Bengali** - ডিফল্ট বডি ফন্ট (Default Body Font)
2. **Noto Serif Bengali** - ডিফল্ট হেডিং ফন্ট (Default Heading Font)
3. **Hind Siliguri** - জনপ্রিয় বাংলা ফন্ট (Popular Bengali Font)
4. **SolaimanLipi** - ক্লিন, মডার্ন ফন্ট (Clean, Modern Font)
5. **Kalpurush** - ঐতিহ্যবাহী বাংলা ফন্ট (Traditional Bengali Font)
6. **Mukti** - সহজ পড়ার ফন্ট (Easy Reading Font)
7. **BenSen** - স্টাইলিশ ফন্ট (Stylish Font)
8. **Ani** - সুন্দর ফন্ট (Beautiful Font)
9. **Lohit Bengali** - সরকারি ফন্ট (Official Font)
10. **Mitra Mono** - মনোস্পেসড ফন্ট (Monospaced Font)
11. **Akaash** - হালকা ফন্ট (Light Font)
12. **Charu Chandan** - সৌন্দর্যময় ফন্ট (Elegant Font)
13. **Galada** - ডেকোরেটিভ ফন্ট (Decorative Font)
14. **Mina** - আধুনিক ফন্ট (Modern Font)
15. **Atma** - বোল্ড ফন্ট (Bold Font)
16. **Baloo Da 2** - রাউন্ডেড ফন্ট (Rounded Font)
17. **Tiro Bangla** - প্রিন্টিং ফন্ট (Printing Font)
18. **Hind Mysore** - মসৃণ ফন্ট (Smooth Font)

## কিভাবে ব্যবহার করবেন (How to Use)

### অ্যাডমিন প্যানেল থেকে (From Admin Panel):

1. **লগইন করুন** (Login to admin panel)
2. **ফন্ট মেনুতে ক্লিক করুন** (Click on "ফন্ট" menu)
3. **হেডিং ফন্ট নির্বাচন করুন** (Select heading font)
4. **বডি ফন্ট নির্বাচন করুন** (Select body font)
5. **প্রিভিউ দেখুন** (Preview the fonts)
6. **আপডেট করুন** (Click "ফন্ট আপডেট করুন")

### ফাইল লোকেশন (File Locations):

- **অ্যাডমিন প্যানেল:** `admin/fonts.php`
- **লেআউট ফাইল:** `layouts/main.php`
- **সেটিংস মডেল:** `models/Setting.php`
- **CKEditor:** `admin/post-create.php`, `admin/post-edit.php`

## ডেভেলপারদের জন্য (For Developers)

### ডাটাবেস স্ট্রাকচার (Database Structure)

```sql
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) 
VALUES 
('font_family_heading', 'Noto Serif Bengali', 'text', 'Font family for headings'),
('font_family_body', 'Noto Sans Bengali', 'text', 'Font family for body text');
```

### ফন্ট লোডিং (Font Loading in Layout)

```php
// Fetch font settings from database
$setting = new Setting();
$site_fonts = $setting->getMultiple(['font_family_heading', 'font_family_body']);
$heading_font = $site_fonts['font_family_heading'] ?? 'Noto Serif Bengali';
$body_font = $site_fonts['font_family_body'] ?? 'Noto Sans Bengali';

// Load from Google Fonts API
$google_fonts_url = 'https://fonts.googleapis.com/css2=' . 
                    urlencode($heading_font) . ':wght@300;400;500;600;700&family=' . 
                    urlencode($body_font) . ':wght@300;400;500;600;700&display=swap';
```

### CSS ইমপ্লিমেন্টেশন (CSS Implementation)

```css
body {
    font-family: '{body_font}', 'Noto Sans Bengali', sans-serif;
}

h1, h2, h3, h4, h5, h6, .heading-font {
    font-family: '{heading_font}', 'Noto Serif Bengali', sans-serif;
}
```

### CKEditor ফন্ট কনফিগারেশন (CKEditor Font Configuration)

```javascript
ClassicEditor.create(editor, {
    fontFamily: {
        options: [
            'Noto Sans Bengali',
            'Noto Serif Bengali',
            'Hind Siliguri',
            'SolaimanLipi',
            'Kalpurush',
            'Mukti',
            'default'
        ],
        supportAllValues: true
    },
    toolbar: [
        'heading', '|',
        'bold', 'italic', '|',
        'fontSize', 'fontFamily', 'fontColor', '|',
        // ... more tools
    ]
})
```

## পরামর্শ (Recommendations)

### সেরা কম্বিনেশন (Best Font Combinations):

**সংবাদ সাইটের জন্য (For News Sites):**
- Heading: **Noto Serif Bengali** (Professional, authoritative)
- Body: **Noto Sans Bengali** (Clean, readable)

**মডার্ন ডিজাইন (Modern Design):**
- Heading: **Hind Siliguri** (Contemporary)
- Body: **SolaimanLipi** (Modern, clean)

**সাহিত্য ব্লগ (Literary Blog):**
- Heading: **Galada** (Artistic)
- Body: **Mitra Mono** (Readable for long texts)

**ম্যাগাজিন (Magazine):**
- Heading: **Atma** (Bold, eye-catching)
- Body: **Kalpurush** (Comfortable reading)

## গুরুত্বপূর্ণ নোট (Important Notes)

⚠️ **Google Fonts প্রয়োজন** - ইন্টারনেট সংযোগ প্রয়োজন  
⚠️ **ফন্ট পরিবর্তনে ক্যাশ ক্লিয়ার করুন** - Clear cache after font changes  
⚠️ **CKEditor এ নতুন কন্টেন্ট** - Existing content won't change font automatically  

## Troubleshooting

### ফন্ট লোড হচ্ছে না (Fonts Not Loading)

1. ইন্টারনেট সংযোগ পরীক্ষা করুন (Check internet connection)
2. ব্রাউজার ক্যাশ ক্লিয়ার করুন (Clear browser cache)
3. Google Fonts URL ব্লক কিনা দেখুন (Check if Google Fonts is blocked)

### ফন্ট পরিবর্তন হচ্ছে না (Fonts Not Changing)

1. সেটিংস সেভ হয়েছে কিনা দেখুন (Check if settings saved)
2. ডাটাবেসে ফন্ট নাম চেক করুন (Verify font names in database)
3. পেজ রিলোড করুন (Reload the page)

### CKEditor এ ফন্ট নেই (Fonts Missing in CKEditor)

1. CKEditor কনফিগারেশন চেক করুন (Check CKEditor config)
2. ব্রাউজার কনসোলে এরর দেখুন (Check browser console for errors)
3. CDN লিঙ্ক কাজ করছে কিনা দেখুন (Verify CDN links are working)

## ভবিষ্যতের আপডেট (Future Updates)

- [ ] More Bengali fonts
- [ ] Custom font upload
- [ ] Font size management
- [ ] Font color presets
- [ ] Typography scale settings
- [ ] Offline font support

## ক্রেডিট (Credits)

- **Google Fonts** - https://fonts.google.com
- **CKEditor 5** - https://ckeditor.com
- **Noto Fonts** - Google's Noto Font Project
- **Bengali Fonts** - Various open-source font contributors

---

**ডেভেলপড উইথ ❤️ ফর আлокপথ সিএমএস**  
**Developed with ❤️ for Alokpath CMS**
