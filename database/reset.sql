-- =============================================================================
-- Reset script for the appointable_lists database.
-- Run the entire file in phpMyAdmin (tab SQL) while the database
-- `appointable_lists` is selected, or import it from Import tab.
--
-- IMPORTANT: this drops ALL existing tables in appointable_lists and
-- recreates them from scratch, matching exactly what the PHP code expects
-- (auth/register.php, auth/login.php, modules/*, includes/functions.php).
-- =============================================================================

CREATE DATABASE IF NOT EXISTS appointable_lists
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE appointable_lists;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS tracked_candidates;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS lists;
DROP TABLE IF EXISTS specialties;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- users: matches auth/register.php, auth/login.php, includes/functions.php
-- -----------------------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  role ENUM('admin', 'candidate') NOT NULL,
  notify_new_lists TINYINT(1) NOT NULL DEFAULT 1,
  notify_position_changes TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- specialties
-- -----------------------------------------------------------------------------
CREATE TABLE specialties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- lists: includes `status` column that modules/admin/manage_lists and
-- includes/functions.php fetch_lists_for_select() expect.
-- -----------------------------------------------------------------------------
CREATE TABLE lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  specialty_id INT NOT NULL,
  year INT NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lists_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_lists_specialty_year (specialty_id, year),
  INDEX idx_lists_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- candidates
-- -----------------------------------------------------------------------------
CREATE TABLE candidates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  birth_year INT DEFAULT NULL,
  specialty_id INT NOT NULL,
  list_id INT NOT NULL,
  position INT NOT NULL,
  points DECIMAL(5, 2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_candidates_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_candidates_list
    FOREIGN KEY (list_id) REFERENCES lists(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_candidates_list_position (list_id, position),
  INDEX idx_candidates_specialty (specialty_id),
  INDEX idx_candidates_points (points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- applications (user links himself to a candidate in the official lists)
-- -----------------------------------------------------------------------------
CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  candidate_id INT NOT NULL,
  verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  verification_notes TEXT DEFAULT NULL,
  linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL DEFAULT NULL,
  verified_by INT DEFAULT NULL,
  CONSTRAINT fk_applications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_applications_candidate
    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_applications_verified_by
    FOREIGN KEY (verified_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY uq_applications_user_candidate (user_id, candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- tracked_candidates (user tracks other candidates)
-- -----------------------------------------------------------------------------
CREATE TABLE tracked_candidates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  candidate_id INT NOT NULL,
  tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tracked_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tracked_candidate
    FOREIGN KEY (candidate_id) REFERENCES candidates(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_tracked_user_candidate (user_id, candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- notifications
-- -----------------------------------------------------------------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA (demo users / lists / candidates) - optional but useful for testing.
-- All demo user passwords are: Password123!
-- =============================================================================

INSERT INTO specialties (id, name, description, is_active) VALUES
  (1, 'Mathematics', 'Mathematics educators and related specialties', 1),
  (2, 'Physics',     'Physics educators and related specialties', 1),
  (3, 'Informatics', 'Computer science educators and related specialties', 1),
  (4, 'Chemistry',   'Chemistry educators and related specialties', 1);

INSERT INTO lists (id, specialty_id, year, status) VALUES
  (1, 1, 2023, 'published'),
  (2, 1, 2024, 'published'),
  (3, 2, 2024, 'published'),
  (4, 3, 2024, 'published'),
  (5, 4, 2025, 'draft');

INSERT INTO candidates (id, name, surname, birth_year, specialty_id, list_id, position, points) VALUES
  (1, 'Maria',     'Ioannou',       1992, 1, 2, 1, 92.40),
  (2, 'Andreas',   'Nicolaou',      1990, 1, 2, 2, 90.10),
  (3, 'Eleni',     'Charalambous',  1994, 1, 2, 3, 88.75),
  (4, 'Nikos',     'Papas',         1989, 2, 3, 1, 91.30),
  (5, 'Sophia',    'Georgiou',      1993, 2, 3, 2, 87.20),
  (6, 'Petros',    'Kleanthous',    1995, 3, 4, 1, 94.10),
  (7, 'Anna',      'Hadjiyianni',   1996, 3, 4, 2, 89.90),
  (8, 'Marios',    'Demetriou',     1991, 4, 5, 1, 86.50),
  (9, 'Christina', 'Sotiriou',      1997, 4, 5, 2, 84.80);

-- All three seed accounts share the password: Password123!
INSERT INTO users (
  id, name, surname, email, password, phone, role, notify_new_lists, notify_position_changes
) VALUES
  (1, 'Admin',   'User', 'admin@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000001', 'admin',     1, 1),
  (2, 'Eleni',   'Demo', 'eleni@example.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000002', 'candidate', 1, 1),
  (3, 'Andreas', 'Demo', 'andreas@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000003', 'candidate', 0, 1);

INSERT INTO applications (
  id, user_id, candidate_id, verification_status, verification_notes, verified_at, verified_by
) VALUES
  (1, 2, 6, 'approved', 'Demo approved link.', CURRENT_TIMESTAMP, 1),
  (2, 3, 2, 'approved', 'Demo approved link.', CURRENT_TIMESTAMP, 1);

INSERT INTO tracked_candidates (id, user_id, candidate_id) VALUES
  (1, 2, 1),
  (2, 2, 4),
  (3, 3, 7);

INSERT INTO notifications (id, user_id, message, is_read) VALUES
  (1, 2, 'Νέα λίστα Πληροφορικής 2024 δημοσιεύτηκε και ο υποψήφιος σας βρίσκεται στη θέση 1.', 0),
  (2, 2, 'Παρακολουθείτε πλέον τη Μαρία Ιωάννου στη λίστα Μαθηματικών 2024.', 1),
  (3, 1, 'Το dashboard στατιστικών ενημερώθηκε με νέα συνοπτικά στοιχεία.', 0);
