# Appointable Lists (PDO)

This project uses a simple PDO-based auth system and a MySQL schema for the `appointable_lists` database.

## Team

- Add your team members and student IDs here.
- Example: Maria Ioannou (AM 12345)
- Example: Giorgos Papas (AM 67890)

## Responsibilities

- Add which student handled each file or feature.
- Example: `database/schema.sql` and `includes/db.php`
- Example: `auth/register.php` and `auth/login.php`
- Example: `modules/dashboard.php` and `modules/list.php`

## Folder Structure

- `database/schema.sql` - CREATE TABLE statements
- `database/seed.sql` - demo data inserts
- `includes/db.php` - PDO connection
- `auth/register.php` - user registration
- `auth/login.php` - user login
- `auth/logout.php` - user logout
- `modules/dashboard.php` - protected dashboard
- `modules/list.php` - candidate list + keyword search

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Import `database/schema.sql`.
4. Import `database/seed.sql`.
5. Make sure `includes/db.php` matches your database credentials.
6. Open the login page in the browser.

## Demo Accounts

- Admin: `admin@example.com` / password: `password`
- Candidate: `eleni@example.com` / password: `password`
- Candidate: `maria@example.com` / password: `password`

## Login URL

`http://localhost/pinakas_dioristewn/auth/login.php`

## Security Rules

- Always use Prepared Statements, never string concatenation in SQL.
- Always use `password_hash()` and `password_verify()`.
- Always use `htmlspecialchars()` when echoing user-controlled data.
- Always call `exit` after every `header()` redirect.
- Never expose `$e->getMessage()` to the browser.
