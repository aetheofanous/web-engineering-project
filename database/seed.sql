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
  (9, 'Christina', 'Sotiriou', 1997, 4, 5, 2, 84.80),
  (10, 'George', 'Andreou', 1988, 1, 1, 1, 91.80),
  (11, 'Irene', 'Michael', 1991, 1, 1, 2, 89.60),
  (12, 'Stelios', 'Christou', 1993, 1, 1, 3, 86.40),
  (13, 'Panayiota', 'Savva', 1995, 1, 1, 4, 84.90),
  (14, 'Kyriakos', 'Loizou', 1987, 1, 2, 4, 86.20),
  (15, 'Despina', 'Antoniou', 1996, 1, 2, 5, 84.75),
  (16, 'Michalis', 'Constantinou', 1992, 1, 2, 6, 82.30),
  (17, 'Elena', 'Theodorou', 1990, 2, 3, 3, 85.60),
  (18, 'Haris', 'Stylianou', 1986, 2, 3, 4, 83.40),
  (19, 'Vasiliki', 'Panayiotou', 1994, 2, 3, 5, 80.95),
  (20, 'Alexandros', 'Markou', 1991, 2, 3, 6, 78.70),
  (21, 'Dimitra', 'Mouskou', 1993, 3, 4, 3, 87.50),
  (22, 'Christos', 'Iacovou', 1990, 3, 4, 4, 85.20),
  (23, 'Marina', 'Philippou', 1997, 3, 4, 5, 82.85),
  (24, 'Pavlos', 'Sergiou', 1989, 3, 4, 6, 79.40),
  (25, 'Katerina', 'Neophytou', 1994, 4, 5, 3, 82.10),
  (26, 'Rafaella', 'Anastasiou', 1998, 4, 5, 4, 79.90),
  (27, 'Antonis', 'Evripidou', 1985, 4, 5, 5, 77.35),
  (28, 'Georgia', 'Lazarou', 1992, 4, 5, 6, 75.80);

INSERT INTO users (
  id, name, surname, email, password, phone, role, notify_new_lists, notify_position_changes
) VALUES
  (1, 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000001', 'admin', 1, 1),
  (2, 'Eleni', 'Demo', 'eleni@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '22000002', 'candidate', 1, 1),
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
