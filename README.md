# buildpc_purephp - Build PC (Pure PHP + HTML + CSS)

Project: a minimal Build-PC website implemented with pure PHP, HTML and CSS (NO JavaScript).
Designed to run on XAMPP (place the `buildpc_purephp` folder into `C:/xampp/htdocs/`).

## Setup

1. Start Apache & MySQL with XAMPP.
2. Create a database `buildpc_db` (or change DB name in config.php).
3. Import `schema.sql` via phpMyAdmin.
4. Copy this folder into `C:/xampp/htdocs/buildpc_purephp`.
5. Ensure `uploads/` is writable (for product images).
6. Open `http://localhost/buildpc_purephp/` in browser.

## Admin

- Admin login: username `admin`, password `admin123` (simple session-based auth for demo).
- Admin page: `http://localhost/buildpc_purephp/admin.php`

## Notes

- No JavaScript is used. All interactivity via HTML forms and PHP.
- Use prepared statements (PDO) to avoid SQL injection.
- This is a demo starter project — extend as needed.
