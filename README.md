# Appointable Lists System

## 👥 Team Members
- Παντελεήμωνη Αλεξάνδρου (AM 30402)
- Χριστιάνα Στυλιανού (AM 30356)
- Αντριάνα Θεοφάνους (AM 30570)
- Σοφία Κυριάκου (AM 30288)
- Πελαγία Κωνιωτάκη (AM 31511)

---

## 📌 Project Description
Το σύστημα “Appointable Lists” είναι μια web εφαρμογή σε PHP/MySQL για την παρακολούθηση υποψηφίων και πινάκων διοριστέων.

Η εφαρμογή επιτρέπει στους χρήστες να:
- δημιουργούν λογαριασμό (register)
- συνδέονται με ασφάλεια (login)
- βλέπουν protected dashboard
- αναζητούν υποψηφίους μέσω keyword search

---

## ⚙️ Technologies Used
- PHP (PDO)
- MySQL
- HTML / CSS
- XAMPP (Apache + MySQL)

---

## 🔐 Security Features
Η εφαρμογή ακολουθεί βασικές πρακτικές ασφάλειας:

- Prepared Statements (PDO) για αποφυγή SQL Injection
- password_hash() για ασφαλή αποθήκευση κωδικών
- password_verify() για έλεγχο login
- htmlspecialchars() για αποφυγή XSS attacks
- Session-based authentication
- Redirect + exit για ασφαλή navigation

---

## 📁 Project Structure
pinakas_dioristewn/
│
├── database/
│ ├── schema.sql
│ └── seed.sql
│
├── includes/
│ └── db.php
│
├── auth/
│ ├── register.php
│ ├── login.php
│ └── logout.php
│
├── modules/
│ ├── dashboard.php
│ └── list.php
│
├── assets/
│ └── css/style.css
│
└── index.php

---

## 🚀 Setup Instructions

1. Εκκίνηση XAMPP
2. Εκκίνηση Apache και MySQL
3. Άνοιγμα phpMyAdmin
4. Δημιουργία database: appointable_lists
5. Import των αρχείων:
- database/schema.sql
- database/seed.sql

6. Άνοιγμα εφαρμογής στον browser: http://localhost/pinakas_dioristewn/

---

## 🔑 Demo Users

| Email              | Password |
|-------------------|----------|
| admin@example.com | password |
| eleni@example.com | password |

---

## 🧩 Features

- ✔ User Registration με validation
- ✔ Secure Login / Logout
- ✔ Protected Dashboard (session-based)
- ✔ Candidate List με keyword search
- ✔ Responsive και καθαρό UI design

---

## 🧑‍💻 Responsibilities

- Παντελεήμωνη Αλεξάνδρου:
  - Authentication system (Register / Login / Logout)
  - Session management & security (password_hash, password_verify)
  - Dashboard implementation (protected page)
  - UI design and styling (CSS, layout consistency)
  - Integration of backend with frontend

- Χριστιάνα Στυλιανού:
  - Database design (schema.sql)
  - Creation of users table and relations
  - Seed data implementation (seed.sql)
  - Database structure validation

- Αντριανή Θεοφάνους:
  - Candidate list module (list.php)
  - Keyword search functionality (GET + LIKE query)
  - Data display in table format
  - Query optimization and filtering logic

- Σοφία Κυριάκου:
  - Input validation (server-side validation rules)
  - Error handling (forms & messages)
  - Security checks (prepared statements usage)
  - Form structure and usability improvements

- Πελαγία Κωνιωτάκη:
  - UI/UX improvements (layout, navigation)
  - Page structure consistency (header, footer, navigation)
  - Design alignment across all pages
  - General testing and bug fixing

---

## 📌 Notes
Το project υλοποιήθηκε σύμφωνα με τις οδηγίες του μαθήματος και ακολουθεί τις βασικές αρχές ασφάλειας και καλής πρακτικής στον web development.

---

## 🏁 Status
✔ Fully functional  
✔ Ready for submission  