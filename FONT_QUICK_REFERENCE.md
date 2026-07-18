# 🎨 ফন্ট ম্যানেজমেন্ট - কুইক রেফারেন্স কার্ড
# Font Management - Quick Reference Card

## 🔗 লিংক (Links)
- **অ্যাডমিন প্যানেল:** `http://localhost/alokpath/admin/fonts.php`
- **টেস্ট পেজ:** `http://localhost/alokpath/test-fonts.php`
- **ফ্রন্টএন্ড:** `http://localhost/alokpath/`

## ✅ ভেরিফিকেশন চেকলিস্ট (Verification Checklist)

- [x] ডাটাবেসে ফন্ট সেটিংস আছে (Font settings in database)
- [x] অ্যাডমিন প্যানেলে ফন্ট মেনু আছে (Font menu in admin)
- [x] লাইভ প্রিভিউ কাজ করে (Live preview works)
- [x] ফন্ট আপডেট হয় (Fonts update correctly)
- [x] ফ্রন্টএন্ডে পরিবর্তন দেখা যায় (Changes visible on frontend)
- [x] CKEditor এ ফন্ট অপশন আছে (Font option in CKEditor)

## 📁 ফাইল লোকেশন (File Locations)

```
alokpath/
├── admin/
│   ├── fonts.php                 ← ফন্ট ম্যানেজমেন্ট পেজ
│   ├── layouts/
│   │   └── admin.php             ← সাইডবারে ফন্ট লিংক
│   ├── post-create.php           ← CKEditor ফন্ট কনফিগ
│   └── post-edit.php             ← CKEditor ফন্ট কনফিগ
├── layouts/
│   └── main.php                  ← ডায়নামিক ফন্ট লোডিং
├── database/
│   └── migrations/
│       └── 002_add_font_settings.sql ← ডাটাবেস মাইগ্রেশন
├── docs/
│   └── FONT_MANAGEMENT.md        ← বিস্তারিত ডকুমেন্টেশন
├── test-fonts.php                ← টেস্ট পেজ
└── FONT_IMPLEMENTATION_COMPLETE.md ← এই ফাইল
```

## 🎯 ডিফল্ট ফন্ট (Default Fonts)

| Element | Font | Type |
|---------|------|------|
| Headings | Noto Serif Bengali | Serif |
| Body Text | Noto Sans Bengali | Sans-serif |

## 🔧 কোড স্নিপেট (Code Snippets)

### Get Current Fonts
```php
$setting = new Setting();
$heading_font = $setting->get('font_family_heading') ?: 'Noto Serif Bengali';
$body_font = $setting->get('font_family_body') ?: 'Noto Sans Bengali';
```

### Change Fonts via Code
```php
$setting = new Setting();
$setting->updateMultiple([
    'font_family_heading' => 'Hind Siliguri',
    'font_family_body' => 'SolaimanLipi'
]);
```

### Use Fonts in CSS
```css
body {
    font-family: var(--body-font, 'Noto Sans Bengali'), sans-serif;
}

h1, h2, h3 {
    font-family: var(--heading-font, 'Noto Serif Bengali'), sans-serif;
}
```

## 📊 উপলব্ধ ফন্ট (Available Fonts)

### সেরা কম্বিনেশন (Best Combinations)

**নিউজ সাইট (News Site):**
- Heading: Noto Serif Bengali
- Body: Noto Sans Bengali

**মডার্ন ডিজাইন (Modern Design):**
- Heading: Hind Siliguri
- Body: SolaimanLipi

**সাহিত্য ব্লগ (Literary Blog):**
- Heading: Galada
- Body: Mitra Mono

**ম্যাগাজিন (Magazine):**
- Heading: Atma
- Body: Kalpurush

## 🐛 ট্রাবলশুটিং (Troubleshooting)

### ফন্ট লোড হচ্ছে না (Fonts Not Loading)
```
সমাধান:
1. ইন্টারনেট সংযোগ চেক করুন
2. ব্রাউজার ক্যাশ ক্লিয়ার করুন (Ctrl+Shift+Delete)
3. Google Fonts ব্লক কিনা চেক করুন
```

### ফন্ট পরিবর্তন হচ্ছে না (Fonts Not Changing)
```
সমাধান:
1. অ্যাডমিন প্যানেলে গিয়ে সেটিংস সেভ করুন
2. পেজ হার্ড রিফ্রেশ করুন (Ctrl+F5)
3. ডাটাবেসে ফন্ট নাম চেক করুন
```

### CKEditor এ ফন্ট নেই (Fonts Missing in CKEditor)
```
সমাধান:
1. ব্রাউজার কনসোল চেক করুন (F12)
2. CDN লিংক কাজ করছে কিনা দেখুন
3. পেজ রিলোড করুন
```

## 🔍 SQL কোয়েরি (SQL Queries)

### Check Font Settings
```sql
SELECT setting_key, setting_value 
FROM settings 
WHERE setting_key LIKE 'font_%';
```

### Reset to Default Fonts
```sql
UPDATE settings 
SET setting_value = 'Noto Serif Bengali' 
WHERE setting_key = 'font_family_heading';

UPDATE settings 
SET setting_value = 'Noto Sans Bengali' 
WHERE setting_key = 'font_family_body';
```

## 📝 নোট (Notes)

⚡ **ইন্টারনেট প্রয়োজন** - Google Fonts API ব্যবহার হয়  
⚡ **ক্যাশিং** - ফন্ট ব্রাউজারে ক্যাশে হয় দ্রুত লোডিংয়ের জন্য  
⚡ **পারফরম্যান্স** - শুধুমাত্র নির্বাচিত ফন্ট লোড হয়  
⚡ **SEO** - ফন্ট পরিবর্তনে SEO প্রভাবিত হয় না  

## 🎨 কাস্টম ফন্ট যোগ করতে (To Add Custom Fonts)

1. `admin/fonts.php` ফাইলে যোগ করুন:
```php
$bengali_fonts = [
    // ... existing fonts
    'Custom Font Name' => 'বাংলা নাম',
];
```

2. CKEditor এ যোগ করুন `post-create.php` এবং `post-edit.php` তে:
```javascript
fontFamily: {
    options: [
        // ... existing fonts
        'Custom Font Name',
        'default'
    ]
}
```

## ✅ টেস্টিং (Testing)

### কমান্ড লাইন টেস্ট (Command Line Test):
```bash
# Verify font settings in database
c:\xampp\mysql\bin\mysql.exe -u root alokpath -e "SELECT * FROM settings WHERE setting_key LIKE 'font_%';"
```

### ওয়েব টেস্ট (Web Test):
1. Visit: `http://localhost/alokpath/test-fonts.php`
2. Check all fonts render correctly
3. Change fonts from admin panel
4. Verify on frontend

---

## 📞 সাহায্য প্রয়োজন? (Need Help?)

📖 **বিস্তারিত গাইড:** `docs/FONT_MANAGEMENT.md`  
🧪 **টেস্ট পেজ:** `test-fonts.php`  
📋 **সম্পূর্ণ ডক:** `FONT_IMPLEMENTATION_COMPLETE.md`  

---

**শেষ আপডেট:** 2026-05-10  
**ভার্সন:** 1.0.0  
**স্ট্যাটাস:** ✅ প্রোডাকশন রেডি
