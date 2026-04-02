USE appointable_lists;

INSERT INTO users (id, username, email, password_hash, role) VALUES
  (1, 'adminuser', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  (2, 'eleni', 'eleni@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate'),
  (3, 'marios', 'marios@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate');

INSERT INTO specialties (id, name, description) VALUES
  (1, 'Mathematics', 'Mathematics educators and related specialties'),
  (2, 'Physics', 'Physics educators and related specialties'),
  (3, 'Informatics', 'Computer science educators and related specialties');

INSERT INTO lists (id, specialty_id, year) VALUES
  (1, 1, 2024),
  (2, 2, 2024),
  (3, 3, 2024);

INSERT INTO candidates (id, user_id, name, surname, birth_year, specialty_id, list_id, position, points) VALUES
  (1, 2, 'Eleni', 'Kosta', 1991, 2, 2, 1, 92.10),
  (2, 3, 'Marios', 'Georgiou', 1990, 1, 1, 2, 88.50),
  (3, NULL, 'Maria', 'Ioannou', 1992, 1, 1, 1, 89.50),
  (4, NULL, 'Giorgos', 'Papas', 1990, 1, 1, 3, 85.75),
  (5, NULL, 'Nikos', 'Andreou', 1993, 3, 3, 1, 90.00);

INSERT INTO applications (id, user_id, candidate_id) VALUES
  (1, 2, 1),
  (2, 3, 2);

INSERT INTO tracked_candidates (id, user_id, candidate_id) VALUES
  (1, 2, 3),
  (2, 2, 5),
  (3, 3, 1);

INSERT INTO notifications (id, user_id, message, is_read) VALUES
  (1, 2, 'New list published for Physics (2024).', 0),
  (2, 3, 'Your tracked candidate changed position.', 0),
  (3, 1, 'New candidate registrations added.', 0);