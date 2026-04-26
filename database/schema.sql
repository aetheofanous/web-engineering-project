CREATE DATABASE IF NOT EXISTS appointable_lists
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE appointable_lists;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS tracked_candidates;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS lists;
DROP TABLE IF EXISTS specialties;
DROP TABLE IF EXISTS users;

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
) ENGINE=InnoDB;

CREATE TABLE specialties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;
