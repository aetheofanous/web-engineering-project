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
