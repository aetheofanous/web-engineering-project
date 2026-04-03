## Students' Names + ID's
   - Antriani Theofanous 30570
   - Sophia Kyriacou 30288
   - Christiana Stylianou 30356
   - Panteleimoni Alexandrou 30402
   - Pelagia Koniotaki 31511

# Appointable Lists (PDO)

This project uses a simple PDO-based auth system and a MySQL schema for the `appointable_lists` database.

## Folder Structure

- `database/schema.sql` - CREATE DATABASE + CREATE TABLE statements
- `database/seed.sql` - demo data inserts
- `includes/db.php` - PDO connection
- `auth/register.php` - user registration
- `auth/login.php` - user login
- `auth/logout.php` - user logout
- `modules/dashboard.php` - protected dashboard
- `modules/list.php` - candidate list + keyword search

## Setup

1. Start Apache & MySQL in XAMPP.
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Import `database/schema.sql` into MySQL.
4. (Optional) Import `database/seed.sql` for demo data.
5. Make sure `includes/db.php` matches your DB credentials.

## Demo Accounts (from seed.sql)

- Admin: `admin@example.com` / password: `password`
- Candidate: `eleni@example.com` / password: `password`

## Login URL

`http://localhost/web-engineering-project/pinakas_dioristewn/auth/login.php`

## Κανόνες Ασφάλειας (Υποχρεωτικοί)

- ΠΑΝΤΑ Prepared Statements — ποτέ string concatenation σε SQL
- ΠΑΝΤΑ password_hash() — ποτέ plain-text password στη βάση
- ΠΑΝΤΑ htmlspecialchars() σε κάθε echo user data (XSS protection)
- ΠΑΝΤΑ exit μετά από κάθε header() redirect
- ΠΟΤΕ die($e->getMessage()) — εκθέτει credentials βάσης
