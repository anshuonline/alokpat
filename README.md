# আলোকপথ - Bengali News Portal CMS

A professional Bengali news website with a complete CMS/admin panel system similar to WordPress.

## 🚀 Features

### Frontend
- ✅ Modern, minimal, fast-loading Bengali UI
- ✅ Fully responsive (mobile, tablet, desktop)
- ✅ SEO-optimized structure
- ✅ Breaking news ticker
- ✅ Featured & trending sections
- ✅ Category pages with pagination
- ✅ Search functionality
- ✅ Social media sharing
- ✅ Bengali typography & dates
- ✅ Beautiful gradient designs

### Admin Panel (CMS)
- ✅ WordPress-like dashboard
- ✅ Article management (create, edit, delete, draft, publish)
- ✅ Category & tag management
- ✅ Media library
- ✅ User management with roles
- ✅ Advanced SEO panel
- ✅ Breaking news control
- ✅ Website settings
- ✅ Role-based access control

### User Roles
- Super Admin
- Admin
- Editor
- Writer
- SEO Manager

### Security
- ✅ Session protection
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection
- ✅ CSRF token validation
- ✅ Secure file uploads
- ✅ Rate limiting on login
- ✅ Role-based permissions

## 📁 Project Structure

```
alokpath/
├── admin/                  # Admin panel
│   ├── layouts/           # Admin layouts
│   ├── dashboard.php      # Admin dashboard
│   ├── login.php          # Admin login
│   ├── posts.php          # Post management
│   └── ...
├── assets/                # CSS, JS, images
├── components/            # Reusable frontend components
│   ├── header.php
│   ├── footer.php
│   └── news-card.php
├── config/                # Configuration
│   └── config.php
├── database/              # Database classes & migrations
│   ├── Database.php
│   └── migrations/
├── helpers/               # Helper functions
│   ├── functions.php
│   └── security.php
├── layouts/               # Frontend layouts
│   └── main.php
├── models/                # Database models
│   ├── User.php
│   ├── Post.php
│   ├── Category.php
│   ├── Tag.php
│   ├── Media.php
│   └── Setting.php
├── uploads/               # Uploaded files
├── index.php              # Homepage
├── article.php            # Article detail
├── category.php           # Category page
├── search.php             # Search page
└── requirements.txt       # Project requirements
```

## 🛠️ Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser
- Text editor (optional)

### Step 1: Setup Database

1. Start XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Create database named `alokpath` (already done according to your info)
5. Import the database schema:
   - Click on `alokpath` database
   - Go to **Import** tab
   - Choose file: `c:\xampp\htdocs\alokpath\database\migrations\001_initial_schema.sql`
   - Click **Go**

### Step 2: Configure Application

1. Open `c:\xampp\htdocs\alokpath\config\config.php`
2. Verify database settings (should work with defaults):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'alokpath');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
3. Update `SITE_URL` if needed:
   ```php
   define('SITE_URL', 'http://localhost/alokpath');
   ```

### Step 3: Set Permissions

Ensure the `uploads` folder is writable:
```
c:\xampp\htdocs\alokpath\uploads\
```

### Step 4: Access the Website

**Frontend (Public Site):**
- URL: `http://localhost/alokpath`

**Admin Panel:**
- URL: `http://localhost/alokpath/admin`
- Username: `admin`
- Password: `admin123`

## 📝 Default Admin Credentials

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Super Admin |

**⚠️ IMPORTANT:** Change the default password immediately after first login!

## 🎯 Getting Started

### Creating Your First Article

1. Login to admin panel (`http://localhost/alokpath/admin`)
2. Click **নতুন সংবাদ** (New News)
3. Fill in:
   - Title (in Bengali)
   - Content
   - Category
   - Featured image
   - SEO fields
4. Set status to **Published**
5. Click **প্রকাশ করুন** (Publish)

### Managing Categories

1. Go to **ক্যাটাগরি** in admin
2. Categories are pre-populated with sample data
3. Add/edit categories as needed
4. Set SEO meta for each category

### Uploading Media

1. Go to **মিডিয়া** in admin
2. Click upload button
3. Select images (JPG, PNG, WebP)
4. Add alt text for accessibility

## 🎨 Customization

### Changing Site Name
Edit in database → `settings` table:
- `site_name` (Bengali)
- `site_name_en` (English)

### Adding Logo
1. Upload logo via Media manager
2. Update `site_logo` in settings
3. Or replace manually in `components/header.php`

### Colors & Styling
Edit Tailwind config in `layouts/main.php`:
```javascript
colors: {
    primary: '#1E40AF',    // Change main color
    secondary: '#DC2626',  // Change accent color
}
```

## 🔐 Security Notes

### Current Password Hashing
The system uses **MD5** temporarily for password hashing. This is defined in:
```php
define('HASH_ALGORITHM', 'md5');
```

### Future Upgrade to bcrypt
When ready to upgrade to bcrypt:
1. Change to: `define('HASH_ALGORITHM', 'bcrypt');`
2. Update `User::authenticate()` method to use `password_verify()`
3. The code is already structured for easy migration

### Production Checklist
- [ ] Change default admin password
- [ ] Disable error display in `config.php`
- [ ] Set proper file permissions
- [ ] Enable HTTPS
- [ ] Backup database regularly
- [ ] Update session lifetime
- [ ] Configure CSRF protection
- [ ] Set up automated backups

## 📊 Database Schema

### Main Tables
- `users` - Admin users
- `posts` - Articles/news
- `categories` - News categories
- `tags` - Tags
- `post_tags` - Post-tag relationships
- `media` - Media library
- `settings` - Website settings
- `menus` - Navigation menus
- `menu_items` - Menu structure
- `advertisements` - Ad placements
- `comments` - User comments (optional)
- `post_analytics` - Post statistics (optional)

## 🚀 Performance Optimization

### Image Optimization
- Auto-resize on upload
- WebP support
- Lazy loading enabled
- Organized by date folders

### Database
- PDO prepared statements
- Indexed columns for fast queries
- Optimized JOIN queries
- Pagination implemented

### Caching (Future)
- Cache folder created for future implementation
- Rate limiter uses file cache
- Ready for Redis/Memcached integration

## 📱 Responsive Design

The website is fully responsive:
- **Mobile**: 320px+
- **Tablet**: 768px+
- **Desktop**: 1024px+
- **Large Desktop**: 1280px+

## 🔧 Development

### Adding New Features
The modular architecture makes it easy to:
- Add new database tables (create model in `/models`)
- Add new admin pages (create in `/admin`)
- Add new frontend components (create in `/components`)
- Add APIs (create in `/api`)

### Code Standards
- Clean, commented PHP code
- MVC-like structure
- Reusable components
- Prepared statements only
- Bengali UI text

## 📞 Support

For issues or questions:
1. Check this README
2. Review code comments
3. Check error logs in XAMPP

## 📄 License

This project is created for educational/demo purposes. Customize as needed.

## 🎉 Credits

- **Tailwind CSS** - Styling
- **Font Awesome** - Icons
- **Google Fonts** - Noto Sans Bengali
- **PHP** - Backend
- **MySQL** - Database

---

**Built with ❤️ for Bengali journalism**

আলোকপথ - আপনার বিশ্বস্ত বাংলা সংবাদ মাধ্যম
