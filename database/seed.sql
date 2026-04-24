USE appointable_lists;

DELETE FROM notifications;
DELETE FROM tracked_candidates;
DELETE FROM applications;
DELETE FROM candidates;
DELETE FROM lists;
DELETE FROM specialties;
DELETE FROM users;

INSERT INTO specialties (id, name, description, is_active) VALUES
  (1, 'Mathematics', 'Mathematics educators and related specialties', 1),
  (2, 'Physics', 'Physics educators and related specialties', 1),
  (3, 'Informatics', 'Computer science educators and related specialties', 1),
  (4, 'Chemistry', 'Chemistry educators and related specialties', 1);

INSERT INTO lists (id, specialty_id, year) VALUES
  (1, 1, 2024),
  (2, 2, 2024),
  (3, 3, 2024),
  (4, 4, 2024);

INSERT INTO candidates (id, name, surname, birth_year, specialty_id, list_id, position, points) VALUES
  (1, 'Maria', 'Ioannou', 1992, 1, 1, 1, 89.50),
  (2, 'Giorgos', 'Papas', 1990, 1, 1, 2, 85.75),
  (3, 'Eleni', 'Kosta', 1991, 2, 2, 1, 92.10),
  (4, 'Nikos', 'Andreou', 1993, 3, 3, 1, 90.00),
  (5, 'Anna', 'Nicolaou', 1994, 4, 4, 1, 88.40);

-- Demo password for all users below: password
INSERT INTO users (id, username, email, password_hash, role) VALUES
  (1, 'adminuser', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  (2, 'eleni_demo', 'eleni@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate'),
  (3, 'maria_demo', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate');

INSERT INTO applications (id, user_id, candidate_id) VALUES
  (1, 2, 4),
  (2, 3, 1);

INSERT INTO tracked_candidates (id, user_id, candidate_id) VALUES
  (1, 2, 1),
  (2, 2, 3),
  (3, 3, 5);

INSERT INTO notifications (id, user_id, message, is_read) VALUES
  (1, 2, 'New list published for Informatics (2024).', 0),
  (2, 1, 'New candidate registered: eleni_demo.', 0),
  (3, 3, 'Your tracked candidate Anna Nicolaou moved into the Chemistry list.', 0);
