# Alokpath - Quick Start Guide

## 🚀 Get Started in 3 Steps

### Step 1: Database Setup (2 min)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on database: `alokpath`
3. Go to **Import** tab
4. Choose file: `database/migrations/001_initial_schema.sql`
5. Click **Go**
6. ✅ Success!

### Step 2: Access Website
**Frontend:** Open `http://localhost/alokpath`

**Admin:** Open `http://localhost/alokpath/admin`
- Username: `admin`
- Password: `admin123`

### Step 3: Create First Article
1. Login to admin
2. Click **নতুন সংবাদ**
3. Fill in title, content, category
4. Upload featured image
5. Set status: Published
6. Click **প্রকাশ করুন**

## 🎯 What Works Now

✅ Homepage with Bengali design
✅ Admin dashboard  
✅ Create/Edit articles
✅ Categories (10 pre-loaded)
✅ Media uploads
✅ Search functionality
✅ SEO fields
✅ Breaking news
✅ Trending posts
✅ Mobile responsive
✅ Social sharing

## 📁 Important Files

- `config/config.php` - Configuration
- `admin/` - Admin panel
- `index.php` - Homepage
- `article.php` - Article page
- `database/migrations/` - Database schema
- `README.md` - Full documentation
- `INSTALL.md` - Detailed installation

## 🎨 Customize

**Site Name:** Database → `settings` table → edit `site_name`

**Colors:** Edit `layouts/main.php` → Tailwind config

**Logo:** Upload via Media → Update `site_logo` in settings

## 🔐 Default Login
- **Username:** admin
- **Password:** admin123
- **⚠️ Change immediately!**

## 📞 Help

- Check `README.md` for full docs
- Check `INSTALL.md` for troubleshooting
- Review code comments

---

**Ready to publish Bengali news!** 🎉
