# Amanuel Yohannes — Portfolio CMS

A dynamic PHP portfolio CMS with a premium public website and a private admin panel.

---

## Installation

### 1. Place files
Copy the entire `pro/` folder into `C:\xampp\htdocs\pro\`

### 2. Import the database
- Open phpMyAdmin: http://localhost/phpmyadmin
- Create a database named `portfolio` (utf8mb4_unicode_ci)
- Import `database.sql`

### 3. Configure database connection
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'portfolio');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password
define('BASE_URL', 'http://localhost/pro');
```

### 4. Set admin password
Visit: http://localhost/pro/setup.php
- Enter your name, email, and a strong password
- After completion, **delete setup.php** from the server

### 5. Verify upload permissions
Ensure the `uploads/` directory is writable by the web server.

---

## Access

| URL | Description |
|-----|-------------|
| http://localhost/pro/ | Public portfolio website |
| http://localhost/pro/admin/ | Admin login |
| http://localhost/pro/setup.php | First-time password setup (delete after use) |

---

## Admin Credentials
Set via `setup.php` on first run.

---

## File Structure

```
pro/
├── index.php              Public homepage
├── project.php            Project detail page
├── about.php              About page
├── contact.php            Contact page
├── setup.php              First-run setup (DELETE AFTER USE)
├── database.sql           Database schema + seed data
├── .htaccess              Security rules
│
├── includes/
│   ├── config.php         Database & app config
│   ├── database.php       PDO singleton
│   ├── functions.php      Shared helpers
│   ├── header.php         Public HTML head + nav
│   └── footer.php         Public footer + scripts
│
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── projects.php
│   ├── project-create.php
│   ├── project-edit.php
│   ├── categories.php
│   ├── clients.php
│   ├── media.php
│   ├── services.php
│   ├── about.php
│   ├── homepage.php
│   ├── settings.php
│   ├── ajax/              AJAX reorder endpoints
│   └── includes/
│       ├── auth.php       Session authentication middleware
│       ├── header.php     Admin sidebar + topbar
│       └── footer.php     Admin scripts
│
├── assets/
│   ├── css/
│   │   ├── public.css     Premium editorial public styles
│   │   └── admin.css      Clean admin panel styles
│   ├── js/
│   │   ├── public.js      GSAP, GLightbox, filter, mobile menu
│   │   └── admin.js       Drag-drop, confirmations, AJAX
│   └── images/
│       └── placeholder.svg
│
└── uploads/
    ├── .htaccess          Blocks PHP execution in uploads
    ├── projects/          Project media (images + videos)
    ├── covers/            Project cover images
    ├── clients/           Client logos
    └── profile/           About profile image
```

---

## Security Notes

- All uploads validated by MIME type + extension + `getimagesize()`
- Uploaded files cannot execute as PHP (`uploads/.htaccess`)
- All DB queries use PDO prepared statements
- CSRF tokens on every POST form
- Passwords hashed with `PASSWORD_BCRYPT` cost 12
- Sessions regenerated on login
- Session timeout after 2 hours of inactivity

---

## Deployment Checklist

- [ ] Change `DB_PASS` in `includes/config.php`
- [ ] Change `BASE_URL` to your live domain
- [ ] Run `setup.php` and set a strong password
- [ ] Delete `setup.php` after use
- [ ] Set contact email in Admin → Settings
- [ ] Upload profile image in Admin → About
- [ ] Upload project images in Admin → Projects → Edit
- [ ] Add social links in Admin → Settings
- [ ] Set `display_errors = Off` in PHP config for production
