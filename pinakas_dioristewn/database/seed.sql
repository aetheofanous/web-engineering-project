USE appointable_lists;

INSERT INTO specialties (id, name, description) VALUES
  (1, 'Mathematics', 'Υποψήφιοι για διδασκαλία μαθηματικών στη Μέση Εκπαίδευση.'),
  (2, 'Physics', 'Υποψήφιοι για φυσικές επιστήμες και εργαστηριακή διδασκαλία.'),
  (3, 'Informatics', 'Υποψήφιοι πληροφορικής και ψηφιακών τεχνολογιών.'),
  (4, 'Philology', 'Υποψήφιοι φιλολογικών μαθημάτων και γλωσσικής διδασκαλίας.');

INSERT INTO lists (id, specialty_id, year, status) VALUES
  (1, 1, 2023, 'published'),
  (2, 1, 2024, 'published'),
  (3, 2, 2024, 'published'),
  (4, 3, 2024, 'published'),
  (5, 4, 2025, 'draft');

INSERT INTO candidates (id, name, surname, birth_year, specialty_id, list_id, position, points) VALUES
  (1, 'Maria', 'Ioannou', 1992, 1, 2, 1, 92.40),
  (2, 'Andreas', 'Nicolaou', 1990, 1, 2, 2, 90.10),
  (3, 'Eleni', 'Charalambous', 1994, 1, 2, 3, 88.75),
  (4, 'Nikos', 'Papas', 1989, 2, 3, 1, 91.30),
  (5, 'Sophia', 'Georgiou', 1993, 2, 3, 2, 87.20),
  (6, 'Petros', 'Kleanthous', 1995, 3, 4, 1, 94.10),
  (7, 'Anna', 'Hadjiyianni', 1996, 3, 4, 2, 89.90),
  (8, 'Marios', 'Demetriou', 1991, 4, 5, 1, 86.50),
  (9, 'Christina', 'Sotiriou', 1997, 4, 5, 2, 84.80);

INSERT INTO users (
  id, name, surname, email, password, phone, role, notify_new_lists, notify_position_changes
) VALUES
  (1, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000001', 'admin', 1, 1),
  (2, 'Eleni', 'Demo', 'eleni@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000002', 'candidate', 1, 1),
  (3, 'Andreas', 'Demo', 'andreas@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000003', 'candidate', 0, 1);

INSERT INTO applications (id, user_id, candidate_id) VALUES
  (1, 2, 6),
  (2, 3, 2);

INSERT INTO tracked_candidates (id, user_id, candidate_id) VALUES
  (1, 2, 1),
  (2, 2, 4),
  (3, 3, 7);

INSERT INTO notifications (id, user_id, message, is_read) VALUES
  (1, 2, 'Νέα λίστα Πληροφορικής 2024 δημοσιεύτηκε και ο υποψήφιος σας βρίσκεται στη θέση 1.', 0),
  (2, 2, 'Παρακολουθείτε πλέον τη Μαρία Ιωάννου στη λίστα Μαθηματικών 2024.', 1),
  (3, 1, 'Το dashboard στατιστικών ενημερώθηκε με νέα συνοπτικά στοιχεία.', 0);
