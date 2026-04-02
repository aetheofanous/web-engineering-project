USE appointable_lists;

INSERT INTO specialties (id, name, description) VALUES
  (1, 'Mathematics', 'Mathematics educators and related specialties'),
  (2, 'Physics', 'Physics educators and related specialties'),
  (3, 'Informatics', 'Computer science educators and related specialties');

INSERT INTO lists (id, specialty_id, year) VALUES
  (1, 1, 2024),
  (2, 2, 2024),
  (3, 3, 2024);

INSERT INTO candidates (id, name, surname, birth_year, specialty_id, list_id, position, points) VALUES
  (1, 'Maria', 'Ioannou', 1992, 1, 1, 1, 89.50),
  (2, 'Giorgos', 'Papas', 1990, 1, 1, 2, 85.75),
  (3, 'Eleni', 'Kosta', 1991, 2, 2, 1, 92.10),
  (4, 'Nikos', 'Andreou', 1993, 3, 3, 1, 90.00);

-- Demo password for all users below: password
INSERT INTO users (id, name, surname, email, password, phone, role) VALUES
  (1, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '999999999', 'admin'),
  (2, 'Eleni', 'Demo', 'eleni@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '888888888', 'candidate');

INSERT INTO applications (id, user_id, candidate_id) VALUES
  (1, 2, 4);

INSERT INTO tracked_candidates (id, user_id, candidate_id) VALUES
  (1, 2, 1),
  (2, 2, 3);

INSERT INTO notifications (id, user_id, message, is_read) VALUES
  (1, 2, 'New list published for Informatics (2024).', 0),
  (2, 1, 'New candidate registered: Eleni Demo.', 0);
