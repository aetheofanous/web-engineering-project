# Appointable Lists

PHP/PDO web application for monitoring appointment lists of candidates, inspired by the public structure of the Educational Service Commission portal.

## Project Scope

This project implements:

- landing page with links to all modules
- authentication with register, login, logout and sessions
- candidate module with profile, own application tracking and tracking of others
- public search module with filters, ordering and statistics
- JSON API module for Postman demo
- MySQL schema with one-to-many and many-to-many relationships

## Technology Stack

- PHP
- PDO
- MySQL / MariaDB
- HTML
- CSS
- XAMPP / Apache

## Folder Structure

- `api/`
- `assets/`
- `auth/`
- `database/`
- `includes/`
- `modules/`
- `index.php`

## Database

Use the files below:

- `database/schema.sql`
- `database/seed.sql`

The schema includes:

- `users`
- `specialties`
- `lists`
- `candidates`
- `applications`
- `tracked_candidates`
- `notifications`

Relationships included:

- one-to-many: `specialties -> lists`
- one-to-many: `lists -> candidates`
- many-to-many via bridge table: `users <-> candidates` through `tracked_candidates`

## Setup

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Import `database/schema.sql`.
4. Import `database/seed.sql`.
5. Open the application at:
   `http://localhost/web-engineering-project/`

## Demo Accounts

- Admin:
  `admin@example.com` / `password`
- Candidate:
  `eleni@example.com` / `password`

## Main Pages

- Landing page:
  `/index.php`
- Login:
  `/auth/login.php`
- Register:
  `/auth/register.php`
- Candidate dashboard:
  `/modules/candidate/dashboard.php`
- Public search:
  `/modules/search/search.php`
- Statistics:
  `/modules/search/statistics.php`
- API home:
  `/api/api.php`

## API Endpoints

- `GET /api/candidates.php`
- `POST /api/candidates.php`
- `GET /api/lists.php`
- `PUT /api/lists.php?id=ID`
- `GET /api/tracked.php`
- `DELETE /api/tracked.php?id=ID`
- `GET /api/stats.php`

## Example Edge Cases for Postman

- `GET /api/lists.php?id=999` is not supported for single fetch and should be tested through invalid update id flow
- `PUT /api/lists.php?id=999` returns `404`
- `POST /api/candidates.php` with missing required fields returns `400`
- `DELETE /api/tracked.php?id=999` returns `404`

## Security Rules

- ΠΑΝΤΑ Prepared Statements - ποτέ string concatenation σε SQL
- ΠΑΝΤΑ `password_hash()` - ποτέ plain-text password στη βάση
- ΠΑΝΤΑ `htmlspecialchars()` σε κάθε echo user data
- ΠΑΝΤΑ `exit` μετά από κάθε `header()` redirect
- ΠΟΤΕ `die($e->getMessage())`

## Design Note

The UI is inspired by `https://www.gov.cy/eey/` but adapted into a student project with a simpler structure and custom presentation layout.
