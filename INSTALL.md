# Alokpath CMS - Installation Guide

## Quick Start Guide

This guide will help you set up the Alokpath Bengali News CMS in under 5 minutes.

### ✅ Pre-requisites Check
- [x] XAMPP installed
- [x] Database "alokpath" created
- [x] Apache running
- [x] MySQL running

### 📦 Installation Steps

#### Step 1: Import Database (2 minutes)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `alokpath` (left sidebar)
3. Click **Import** tab (top menu)
4. Click **Choose File**
5. Navigate to: `c:\xampp\htdocs\alokpath\database\migrations\001_initial_schema.sql`
6. Click **Go** (bottom)
7. Wait for success message ✅

**Expected Result:** 12 tables created with sample data

#### Step 2: Verify Configuration (1 minute)

Open: `c:\xampp\htdocs\alokpath\config\config.php`

Verify these lines (should be correct already):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'alokpath');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/alokpath');
```

Save if you made changes.

#### Step 3: Create Uploads Folder (30 seconds)

The uploads folder should auto-create, but verify it exists:

```
c:\xampp\htdocs\alokpath\uploads\
```

If not, create it manually and ensure it's writable.

#### Step 4: Access the Website (30 seconds)

**Frontend (Public Site):**
- Open browser
- Go to: `http://localhost/alokpath`
- You should see the homepage with Bengali content ✅

**Admin Panel:**
- Open browser
- Go to: `http://localhost/alokpath/admin`
- Login with:
  - Username: `admin`
  - Password: `admin123`
- Click **লগইন** (Login)
- You should see the admin dashboard ✅

### 🎯 First Steps After Login

1. **Change Admin Password** (Important!)
   - Go to: Users → Edit admin
   - Change password from default

2. **Create Your First Article**
   - Click: **নতুন সংবাদ** (New News)
   - Fill in title, content in Bengali
   - Select category
   - Upload featured image
   - Set SEO fields
   - Status: Published
   - Click: **প্রকাশ করুন** (Publish)

3. **Upload Media**
   - Go to: মিডিয়া (Media)
   - Click upload button
   - Select image file
   - Add alt text
   - Save

4. **Manage Categories**
   - Go to: ক্যাটাগরি (Categories)
   - 10 sample categories pre-loaded
   - Edit names or add new ones
   - Set order and icons

### 🎨 Customization Tips

**Change Site Name:**
```
Database → settings table
Edit: site_name (Bengali)
Edit: site_name_en (English)
```

**Add Logo:**
1. Upload via Media manager
2. Copy file URL
3. Database → settings → site_logo
4. Paste URL

**Change Colors:**
Edit `layouts/main.php`:
```javascript
colors: {
    primary: '#YOUR_COLOR',
    secondary: '#YOUR_COLOR',
}
```

### 🔧 Troubleshooting

**Problem: Database import fails**
- Solution: Check database name is "alokpath"
- Check MySQL is running in XAMPP

**Problem: Can't login**
- Solution: Verify credentials: admin / admin123
- Check users table has admin record

**Problem: Blank page**
- Solution: Check error logs in XAMPP
- Enable errors in config.php (already enabled for dev)

**Problem: Images not uploading**
- Solution: Check uploads/ folder exists
- Check folder permissions (should be writable)
- Check PHP upload settings in php.ini

**Problem: CSS not loading**
- Solution: Clear browser cache (Ctrl+Shift+R)
- Check internet connection (Tailwind loads from CDN)

### 📊 What's Included

**Database Tables (12):**
✅ users - Admin users
✅ posts - Articles
✅ categories - News categories  
✅ tags - Tags
✅ post_tags - Post-tag links
✅ media - Media library
✅ settings - Site settings
✅ menus - Navigation
✅ menu_items - Menu structure
✅ advertisements - Ad placements
✅ comments - Comments (optional)
✅ post_analytics - Stats (optional)

**Sample Data:**
✅ 1 Super Admin user
✅ 10 Bengali categories
✅ 20 website settings
✅ 2 navigation menus

**Features Working:**
✅ Login/Logout
✅ Create/Edit/Delete posts
✅ Categories & Tags
✅ Media uploads
✅ Search functionality
✅ SEO meta tags
✅ Breaking news ticker
✅ Trending posts
✅ Featured posts
✅ Pagination
✅ Bengali dates
✅ Social sharing
✅ Mobile responsive

### 🚀 Next Steps

1. Add real content (articles, images)
2. Customize site settings
3. Add social media links
4. Upload logo
5. Configure advertisements
6. Set up backup system
7. Plan security hardening for production

### 📞 Need Help?

- Check README.md for detailed docs
- Review code comments
- Check XAMPP error logs
- Database structure is documented

### 🎉 Success Indicators

You know it's working when:
- ✅ Homepage loads with Bengali text
- ✅ Breaking news ticker shows (after adding breaking news)
- ✅ Can login to admin
- ✅ Can create and publish articles
- ✅ Can upload images
- ✅ Search works
- ✅ Category pages load
- ✅ Mobile view works

---

**Enjoy building your Bengali news portal!** 🎊

আলোকপথ - আপনার বিশ্বস্ত বাংলা সংবাদ মাধ্যম
