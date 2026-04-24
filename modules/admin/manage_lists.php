<?php
require_once __DIR__ . '/../../includes/functions.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';
ensure_specialty_management_schema($pdo);

function read_import_content() {
    $text = trim($_POST['import_text'] ?? '');

    if ($text !== '') {
        return $text;
    }

    if (
        isset($_FILES['list_file']) &&
        is_array($_FILES['list_file']) &&
        ($_FILES['list_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
    ) {
        return trim((string) file_get_contents($_FILES['list_file']['tmp_name']));
    }

    return '';
}

function parse_candidate_rows($rawContent) {
    $rows = preg_split("/\r\n|\n|\r/", trim($rawContent));
    $parsed = [];

    foreach ($rows as $index => $row) {
        if (trim($row) === '') {
            continue;
        }

        $delimiter = ',';
        if (substr_count($row, "\t") >= 4) {
            $delimiter = "\t";
        } elseif (substr_count($row, ';') >= 4) {
            $delimiter = ';';
        }

        $columns = array_map('trim', str_getcsv($row, $delimiter));

        if ($index === 0) {
            $headerTokens = array_map(
                function ($value) {
                    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
                },
                $columns
            );
            if (
                in_array('name', $headerTokens, true) ||
                in_array('surname', $headerTokens, true) ||
                in_array('position', $headerTokens, true) ||
                in_array('όνομα', $headerTokens, true)
            ) {
                continue;
            }
        }

        if (count($columns) < 5) {
            continue;
        }

        $parsed[] = [
            'name' => $columns[0],
            'surname' => $columns[1],
            'birth_year' => $columns[2] !== '' ? (int) $columns[2] : null,
            'position' => (int) $columns[3],
            'points' => $columns[4] !== '' ? (float) $columns[4] : null,
        ];
    }

    return $parsed;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_specialties') {
        $selected = array_map('intval', $_POST['active_specialties'] ?? []);

        $pdo->exec('UPDATE specialties SET is_active = 0');

        if ($selected) {
            $placeholders = implode(',', array_fill(0, count($selected), '?'));
            $stmt = $pdo->prepare("UPDATE specialties SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($selected);
        }

        set_flash_message('success', 'Οι ενεργές ειδικότητες ενημερώθηκαν.');
        redirect_to('manage_lists.php');
    }

    if ($action === 'add_specialty') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = 'Το όνομα ειδικότητας είναι υποχρεωτικό.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO specialties (name, description, is_active)
                 VALUES (:name, :description, 1)'
            );
            $stmt->execute([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
            ]);

            set_flash_message('success', 'Η νέα ειδικότητα προστέθηκε.');
            redirect_to('manage_lists.php');
        }
    }

    if ($action === 'delete_list') {
        $listId = (int) ($_POST['list_id'] ?? 0);

        if ($listId <= 0) {
            $errors[] = 'Ο πίνακας προς διαγραφή δεν βρέθηκε.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('DELETE FROM candidates WHERE list_id = :list_id');
            $stmt->execute(['list_id' => $listId]);

            $stmt = $pdo->prepare('DELETE FROM lists WHERE id = :id');
            $stmt->execute(['id' => $listId]);

            set_flash_message('success', 'Ο πίνακας και οι υποψήφιοί του διαγράφηκαν.');
            redirect_to('manage_lists.php');
        }
    }

    if ($action === 'import_list') {
        $specialtyId = (int) ($_POST['specialty_id'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        $mode = $_POST['import_mode'] ?? 'append';
        $rawContent = read_import_content();

        if ($specialtyId <= 0 || $year <= 0) {
            $errors[] = 'Επιλέξτε ειδικότητα και έτος για τον πίνακα.';
        }

        if ($rawContent === '') {
            $errors[] = 'Δώστε δεδομένα μέσω upload ή επικόλλησης κειμένου.';
        }

        $parsedRows = $rawContent !== '' ? parse_candidate_rows($rawContent) : [];

        if ($rawContent !== '' && !$parsedRows) {
            $errors[] = 'Δεν βρέθηκαν έγκυρες γραμμές υποψηφίων στα δεδομένα εισαγωγής.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM lists WHERE specialty_id = :specialty_id AND year = :year LIMIT 1');
            $stmt->execute([
                'specialty_id' => $specialtyId,
                'year' => $year,
            ]);
            $listId = (int) $stmt->fetchColumn();

            if (!$listId) {
                $stmt = $pdo->prepare(
                    'INSERT INTO lists (specialty_id, year)
                     VALUES (:specialty_id, :year)'
                );
                $stmt->execute([
                    'specialty_id' => $specialtyId,
                    'year' => $year,
                ]);
                $listId = (int) $pdo->lastInsertId();
            }

            if ($mode === 'replace') {
                $stmt = $pdo->prepare('DELETE FROM candidates WHERE list_id = :list_id');
                $stmt->execute(['list_id' => $listId]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO candidates (name, surname, birth_year, specialty_id, list_id, position, points)
                 VALUES (:name, :surname, :birth_year, :specialty_id, :list_id, :position, :points)'
            );

            foreach ($parsedRows as $row) {
                $stmt->execute([
                    'name' => $row['name'],
                    'surname' => $row['surname'],
                    'birth_year' => $row['birth_year'],
                    'specialty_id' => $specialtyId,
                    'list_id' => $listId,
                    'position' => $row['position'],
                    'points' => $row['points'],
                ]);
            }

            set_flash_message('success', 'Ο πίνακας φορτώθηκε επιτυχώς με ' . count($parsedRows) . ' εγγραφές.');
            redirect_to('manage_lists.php');
        }
    }
}

$specialties = $pdo->query(
    'SELECT id, name, description, COALESCE(is_active, 1) AS is_active
     FROM specialties
     ORDER BY name ASC'
)->fetchAll();

$listRows = $pdo->query(
    'SELECT l.id, l.year, s.name AS specialty_name, COALESCE(s.is_active, 1) AS is_active, COUNT(c.id) AS candidates_count
     FROM lists l
     JOIN specialties s ON s.id = l.specialty_id
     LEFT JOIN candidates c ON c.list_id = l.id
     GROUP BY l.id, l.year, s.name, s.is_active
     ORDER BY l.year DESC, s.name ASC'
)->fetchAll();

$messages = array_merge(
    get_flash_messages(),
    array_map(
        function ($error) {
            return ['type' => 'error', 'message' => $error];
        },
        $errors
    )
);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Manage Lists</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Admin Module</p>
                <h1 class="auth-title">Manage Lists</h1>
                <p class="auth-subtitle">Επιλέξτε τις ειδικότητες που θα είναι ενεργές και φορτώστε νέους πίνακες με δεδομένα υποψηφίων μέσω CSV, TSV ή επικόλλησης από εξωτερική πηγή.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="split-grid">
                    <section class="panel-section">
                        <h2 class="section-title">Ενεργές Ειδικότητες</h2>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="save_specialties">

                            <div class="checkbox-grid">
                                <?php foreach ($specialties as $specialty): ?>
                                    <label class="checkbox-card">
                                        <input
                                            type="checkbox"
                                            name="active_specialties[]"
                                            value="<?php echo h($specialty['id']); ?>"
                                            <?php echo (int) $specialty['is_active'] === 1 ? 'checked' : ''; ?>
                                        >
                                        <span>
                                            <strong><?php echo h($specialty['name']); ?></strong><br>
                                            <?php echo h($specialty['description'] ?: 'Χωρίς περιγραφή'); ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <button type="submit">Αποθήκευση Επιλογών</button>
                        </form>
                    </section>

                    <section class="panel-section">
                        <h2 class="section-title">Προσθήκη Ειδικότητας</h2>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_specialty">

                            <label for="name">Όνομα</label>
                            <input type="text" name="name" id="name" placeholder="Enter specialty name" required>

                            <label for="description">Περιγραφή</label>
                            <input type="text" name="description" id="description" placeholder="Short specialty description">
                            <p class="field-help">Μια σύντομη περιγραφή βοηθά στην καλύτερη οργάνωση των ειδικοτήτων.</p>

                            <button type="submit">Προσθήκη Ειδικότητας</button>
                        </form>
                    </section>
                </div>

                <section class="panel-section">
                    <h2 class="section-title">Φόρτωση Πίνακα</h2>
                    <p class="section-text">Αναμενόμενες στήλες ανά γραμμή: `name, surname, birth_year, position, points`. Μπορείτε να εισάγετε αρχείο CSV/TSV ή να επικολλήσετε δεδομένα που αντιγράψατε από τη σελίδα της Επιτροπής.</p>

                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_list">

                        <div class="split-grid">
                            <div>
                                <label for="specialty_id">Ειδικότητα</label>
                                <select name="specialty_id" id="specialty_id" required>
                                    <option value="">-- Επιλέξτε ειδικότητα --</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo h($specialty['id']); ?>">
                                            <?php echo h($specialty['name']); ?><?php echo (int) $specialty['is_active'] !== 1 ? ' (inactive)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="year">Έτος Πίνακα</label>
                                <input type="number" name="year" id="year" min="2000" max="2100" value="<?php echo h((string) date('Y')); ?>" placeholder="Enter list year" required>
                                <p class="field-help">Χρησιμοποιήστε το έτος δημοσίευσης ή ισχύος του πίνακα.</p>

                                <label for="import_mode">Τρόπος Εισαγωγής</label>
                                <select name="import_mode" id="import_mode">
                                    <option value="append">Προσθήκη στις υπάρχουσες εγγραφές</option>
                                    <option value="replace">Αντικατάσταση υπάρχοντος πίνακα</option>
                                </select>

                                <label for="list_file">Αρχείο CSV/TSV</label>
                                <input type="file" name="list_file" id="list_file" accept=".csv,.tsv,.txt">
                                <p class="field-help">Υποστηρίζονται αρχεία `.csv`, `.tsv` και `.txt` με 5 στήλες: name, surname, birth_year, position, points.</p>
                            </div>

                            <div>
                                <label for="import_text">Επικόλληση Δεδομένων</label>
                                <textarea name="import_text" id="import_text" rows="12" placeholder="Anna,Nicolaou,1990,1,92.5&#10;Maria,Ioannou,1992,2,89.3"></textarea>
                                <p class="field-help">Μπορείτε να επικολλήσετε πολλές γραμμές, μία εγγραφή ανά γραμμή, με κόμμα, ελληνικό ερωτηματικό ή tab separator.</p>
                            </div>
                        </div>

                        <button type="submit">Φόρτωση Πίνακα</button>
                    </form>
                </section>

                <section class="panel-section">
                    <div class="section-header">
                        <h2 class="section-title">Υφιστάμενοι Πίνακες</h2>
                        <p class="section-text">Εμφάνιση όλων των πινάκων με πλήθος υποψηφίων ανά ειδικότητα και έτος.</p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ειδικότητα</th>
                                    <th>Έτος</th>
                                    <th>Κατάσταση Ειδικότητας</th>
                                    <th>Υποψήφιοι</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$listRows): ?>
                                    <tr>
                                        <td colspan="6" class="empty-cell">Δεν υπάρχουν πίνακες.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($listRows as $list): ?>
                                        <tr>
                                            <td><?php echo h($list['id']); ?></td>
                                            <td><?php echo h($list['specialty_name']); ?></td>
                                            <td><?php echo h($list['year']); ?></td>
                                            <td>
                                                <span class="status-badge">
                                                    <?php echo (int) $list['is_active'] === 1 ? 'active' : 'inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo h($list['candidates_count']); ?></td>
                                            <td>
                                                <form method="post" action="" onsubmit="return confirm('Είσαι σίγουρος;');">
                                                    <input type="hidden" name="action" value="delete_list">
                                                    <input type="hidden" name="list_id" value="<?php echo h($list['id']); ?>">
                                                    <button class="table-button danger" type="submit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="page-actions">
                    <a class="button-link secondary" href="dashboard.php">Επιστροφή στο Admin Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
