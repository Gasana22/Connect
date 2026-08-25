# Let's Go Medical — Medical Tourism Platform

A complete medical tourism website built in plain PHP + MySQL (PDO), with a full-featured admin panel. No framework, no Composer, no build step — just copy the folder into XAMPP's `htdocs` and go.

## Features

**Public site**
- Home page with search, featured treatments, destinations, packages, testimonials, blog
- Treatments, Destinations, Hospitals, Doctors and Packages — with filterable listing pages and detail pages
- Blog / patient resources
- Testimonials, About, Contact
- "Get a Free Quote" lead-capture form and per-package enquiry forms
- Fully responsive (Bootstrap 5), mobile-friendly navigation, WhatsApp click-to-chat button

**Admin panel** (`/marinka`)
- Secure login (bcrypt password hashing, CSRF-protected, session-based)
- Dashboard with lead stats and a 14-day leads chart
- Full CRUD for Treatments, Destinations, Hospitals, Doctors, Packages, Blog Posts, Testimonials
- Leads/enquiries inbox with status tracking (new / contacted / converted / closed)
- Site settings (contact info, social links, about text)
- Admin user management with roles (super admin / editor)
- Image upload with validation on every content type

## Requirements

- XAMPP (or any Apache + PHP 8+ + MySQL/MariaDB stack)
- PHP extensions: `pdo_mysql`, `fileinfo` (both enabled by default in XAMPP)

## Installation (XAMPP)

1. **Copy the project.** Copy the entire `medical-tourism` folder into your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\medical-tourism
   ```
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database.** Open [phpMyAdmin](http://localhost/phpmyadmin), click *Import*, and import:
   ```
   database/schema.sql
   ```
   This creates the `medical_tourism` database, all tables, and sample demo content (treatments, hospitals, doctors, packages, blog posts, testimonials and one admin account).

   Alternatively, from a terminal:
   ```
   mysql -u root -p < database/schema.sql
   ```
4. **Configure the database connection.** Open `config/db.php` and adjust if your MySQL credentials differ from the XAMPP defaults (`root` / no password):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'medical_tourism');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', 'http://localhost/medical-tourism');
   ```
   Set `BASE_URL` to match the folder name you used in `htdocs`.
5. **Visit the site:**
   ```
   http://localhost/medical-tourism/
   ```
6. **Log into the admin panel:**
   ```
   http://localhost/medical-tourism/marinka/login.php
   ```
   Default credentials (change this password after first login, via *My Profile*):
   ```
   Email:    admin@letsgomedical.test
   Password: admin123
   ```

## Folder Structure

```
medical-tourism/
├── marinka/              Admin panel (auth-protected)
│   └── includes/        Admin layout + auth guard
├── assets/              CSS, JS, placeholder image
├── config/               db.php – database & site configuration
├── database/            schema.sql – schema + seed data
├── includes/            Shared PHP includes (header, footer, helpers)
├── uploads/              User-uploaded images, organized per content type
├── index.php, treatments.php, ...   Public-facing pages
└── marinka/index.php, marinka/treatments.php, ...   Admin panel pages
```

## Security Notes

- All database queries use PDO prepared statements.
- Admin passwords are hashed with `password_hash()` (bcrypt), minimum 8 characters.
- Every state-changing form (public and admin) is protected with a CSRF token.
- The admin login locks an account for 15 minutes after 5 failed attempts.
- Uploaded images are validated by extension + real MIME type, capped at 5MB, re-encoded through GD (strips metadata and defeats disguised files), and renamed to random filenames on save.
- Session cookies are `HttpOnly`, `SameSite=Lax` and marked `Secure` automatically over HTTPS.
- Security response headers are sent on every page: `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- Raw PHP errors are never shown to visitors (`display_errors` off) — check your PHP error log if something goes wrong.
- A single root `.htaccess` (requires `mod_rewrite`, on by default in XAMPP) blocks direct browser access to `/config`, `/database`, `/includes` and `/marinka/includes`, and prevents any uploaded file from being executed as a script — even if it were somehow given a disallowed extension.
- The public lead/quote forms are throttled to one submission per 20 seconds per visitor.
- Change the default admin password immediately after your first login.
- **Database user:** this project defaults to XAMPP's `root` user with a blank password, which is fine for local development but should never be used if the site is exposed beyond your own machine. Before going live, create a dedicated MySQL user with access to only this database:
  ```sql
  CREATE USER 'medtourism_app'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON medical_tourism.* TO 'medtourism_app'@'localhost';
  FLUSH PRIVILEGES;
  ```
  Then update `DB_USER` / `DB_PASS` in `config/db.php` to match. Also set a root password in XAMPP's MySQL/phpMyAdmin (it ships blank by default) and disable remote access to phpMyAdmin if this server is ever reachable from outside your own machine.

## Customization

- Site name, contact details, social links and "About" text are all editable from **Admin → Site Settings** — no code changes required.
- Colors and theme live in `assets/css/style.css` (see the `:root` CSS variables at the top of the file — the palette is purple `#4b2e83` + blue `#2856c9` with a gold accent, matching the Let's Go Medical logo).
- The logo is `assets/img/logo.svg`, referenced from the main header, footer, admin sidebar and admin login page. Replace this file with your own artwork (same filename) to swap it everywhere at once.
- To add a new content type, follow the pattern used by `marinka/treatments.php` + `marinka/treatment-form.php`.
