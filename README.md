# BlogCraft CMS

A lightweight PHP content management system with rich text editing, category management, user roles, and a clean reading experience.

---

## FILE STRUCTURE

```
blogcraft/
├── install.php          ← Run this first!
├── index.php            ← Public blog homepage
├── post.php             ← Single post reader
├── .htaccess            ← Apache security rules
├── includes/
│   ├── db.php           ← Database config (auto-updated by installer)
│   └── auth.php         ← Session & auth helpers
├── admin/
│   ├── login.php        ← Admin sign-in
│   ├── index.php        ← Dashboard
│   ├── posts.php        ← All posts list
│   ├── post-edit.php    ← Create / edit posts
│   ├── categories.php   ← Manage categories
│   ├── comments.php     ← Moderate comments
│   ├── users.php        ← Team members
│   ├── settings.php     ← Profile & site settings
│   ├── logout.php       ← Sign out
│   └── sidebar.php      ← Shared navigation
├── assets/
│   └── css/
│       ├── style.css    ← Public blog styles
│       └── admin.css    ← Admin panel styles
└── uploads/             ← Cover image uploads (writable)
```

---

## REQUIREMENTS

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (or Nginx)
- A web hosting account (shared hosting works perfectly)

---

## INSTALLATION — STEP BY STEP

### Option A: Shared Hosting (cPanel / Hostinger / Namecheap etc.)

1. **Download** the `blogcraft` folder to your computer
2. **Log into cPanel** → File Manager
3. **Upload** the entire `blogcraft` folder to `public_html/` (or a subfolder)
4. **Create a MySQL database** in cPanel:
   - Go to MySQL Databases
   - Create a new database (e.g. `yourusername_blogcraft`)
   - Create a user and assign it to the database (All Privileges)
   - Note down: database name, username, password
5. **Visit the installer** in your browser:
   ```
   https://yourdomain.com/blogcraft/install.php
   ```
6. **Fill in** your database details and create your admin account
7. **Click Install** — done!
8. **Delete** `install.php` from your server after installation

### Option B: Local Development (XAMPP / WAMP / Laragon)

1. Copy the `blogcraft` folder to `htdocs/` (XAMPP) or `www/` (WAMP)
2. Start Apache and MySQL
3. Visit: `http://localhost/blogcraft/install.php`
4. Use `localhost` as host, your MySQL credentials (XAMPP default: user=`root`, pass=``)
5. Install and go!

### Option C: VPS / Cloud Server

1. Upload files to your web root (e.g. `/var/www/html/blogcraft/`)
2. Make the uploads folder writable:
   ```bash
   chmod 755 uploads/
   ```
3. Create a database:
   ```sql
   CREATE DATABASE blogcraft;
   CREATE USER 'blogcraft'@'localhost' IDENTIFIED BY 'yourpassword';
   GRANT ALL ON blogcraft.* TO 'blogcraft'@'localhost';
   ```
4. Visit `https://yourdomain.com/blogcraft/install.php`

---

## ADDING A LINK ON YOUR EXISTING WEBSITE

To add a "Live Demo" or "Blog" button that opens BlogCraft:

### Simple link:
```html
<a href="https://yourdomain.com/blogcraft/">Visit Blog</a>
```

### Button style:
```html
<a href="https://yourdomain.com/blogcraft/"
   style="display:inline-block; padding:12px 28px; background:#c0392b; color:white;
          border-radius:4px; font-family:sans-serif; font-size:14px;
          font-weight:500; text-decoration:none;">
  Live Demo →
</a>
```

### Open in new tab:
```html
<a href="https://yourdomain.com/blogcraft/" target="_blank" rel="noopener">
  Live Demo ↗
</a>
```

### If BlogCraft is in a subfolder on the same domain:
```html
<!-- If your site is example.com and CMS is at example.com/blog/ -->
<a href="/blog/">Go to Blog</a>
```

---

## URLS AFTER INSTALLATION

| Page | URL |
|------|-----|
| Public Blog | `yourdomain.com/blogcraft/` |
| Single Post | `yourdomain.com/blogcraft/post.php?slug=your-slug` |
| Admin Login | `yourdomain.com/blogcraft/admin/login.php` |
| Dashboard | `yourdomain.com/blogcraft/admin/` |

---

## USER ROLES

| Role | Can Do |
|------|--------|
| **Admin** | Everything — users, settings, all posts |
| **Editor** | Create, edit, publish all posts; manage categories & comments |
| **Author** | Create and edit their own posts (drafts & review only) |
| **Viewer** | Read-only access |

---

## FEATURES

- ✅ Rich text editor (bold, italic, headings, blockquotes, lists, links, code)
- ✅ Category management
- ✅ Tag system
- ✅ User roles (Admin / Editor / Author / Viewer)
- ✅ Cover image uploads
- ✅ Comment system with moderation
- ✅ Post status: Draft → Review → Published
- ✅ SEO excerpt preview
- ✅ View counter
- ✅ Related posts
- ✅ Responsive public blog
- ✅ Clean admin dashboard
- ✅ Pagination
- ✅ Search & filter posts

---

## SECURITY NOTES

- Delete `install.php` after installation
- The `.htaccess` blocks direct access to PHP files in `/uploads/`
- Passwords are hashed with `password_hash()` (bcrypt)
- All inputs are escaped with `real_escape_string()`
- Use HTTPS in production (free via Let's Encrypt)

---

## CUSTOMISATION

- **Colors**: Edit `--accent` in `assets/css/style.css` and `assets/css/admin.css`
- **Fonts**: Change the Google Fonts import at the top of each CSS file
- **Site name**: Update `SITE_NAME` in `includes/db.php`
- **Posts per page**: Change `$per_page` in `index.php` and `admin/posts.php`

---

Built with ❤ using plain PHP, MySQL, and vanilla CSS. No frameworks. No dependencies.
