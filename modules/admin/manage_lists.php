<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

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

function import_candidate_key(array $candidate): string
{
    $name = function_exists('mb_strtolower') ? mb_strtolower(trim($candidate['name'])) : strtolower(trim($candidate['name']));
    $surname = function_exists('mb_strtolower') ? mb_strtolower(trim($candidate['surname'])) : strtolower(trim($candidate['surname']));

    return $name . '|' . $surname . '|' . (string) ($candidate['birth_year'] ?? '');
}

function approved_application_positions_for_list(PDO $pdo, int $listId): array
{
    $stmt = $pdo->prepare(
        'SELECT applications.id AS application_id, applications.user_id,
                users.notify_position_changes,
                candidates.position
         FROM applications
         INNER JOIN users ON users.id = applications.user_id
         INNER JOIN candidates ON candidates.id = applications.candidate_id
         WHERE applications.verification_status = :status
           AND candidates.list_id = :list_id'
    );
    $stmt->execute([
        'status' => 'approved',
        'list_id' => $listId,
    ]);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[(int) $row['application_id']] = $row;
    }
    return $rows;
}

function notify_position_changes_for_list(PDO $pdo, array $beforeRows): int
{
    if ($beforeRows === []) {
        return 0;
    }

    $applicationIds = array_keys($beforeRows);
    $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
    $stmt = $pdo->prepare(
        'SELECT applications.id AS application_id, applications.user_id,
                candidates.position, candidates.name, candidates.surname,
                lists.year, specialties.name AS specialty_name
         FROM applications
         INNER JOIN candidates ON candidates.id = applications.candidate_id
         INNER JOIN lists ON lists.id = candidates.list_id
         INNER JOIN specialties ON specialties.id = candidates.specialty_id
         WHERE applications.id IN (' . $placeholders . ')
           AND applications.verification_status = ?'
    );
    $stmt->execute(array_merge($applicationIds, ['approved']));

    $count = 0;
    foreach ($stmt->fetchAll() as $after) {
        $before = $beforeRows[(int) $after['application_id']] ?? null;
        if (!$before || (int) $before['notify_position_changes'] !== 1) {
            continue;
        }

        $oldPosition = (int) $before['position'];
        $newPosition = (int) $after['position'];
        if ($oldPosition === $newPosition) {
            continue;
        }

        create_notification(
            (int) $after['user_id'],
            sprintf(
                'Your approved application for %s %s in %s %s changed position from #%d to #%d.',
                $after['name'],
                $after['surname'],
                $after['specialty_name'],
                $after['year'],
                $oldPosition,
                $newPosition
            )
        );
        $count++;
    }

    return $count;
}

function notify_new_list_subscribers(PDO $pdo, string $specialtyName, int $year): int
{
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'candidate' AND notify_new_lists = 1");

    $count = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        create_notification(
            (int) $userId,
            sprintf('A new appointable list was published for %s %d.', $specialtyName, $year)
        );
        $count++;
    }

    return $count;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['delete_specialty_id']) ? 'delete_specialty' : ($_POST['action'] ?? '');

    if ($action === 'save_specialties') {
        $selected = array_map('intval', $_POST['active_specialties'] ?? []);

        $pdo->exec('UPDATE specialties SET is_active = 0');

        if ($selected) {
            $placeholders = implode(',', array_fill(0, count($selected), '?'));
            $stmt = $pdo->prepare("UPDATE specialties SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($selected);
        }

        set_flash_message('success', 'Οι ενεργές ειδικότητες ενημερώθηκαν.');
        redirect_to('modules/admin/manage_lists.php');
    }

    if ($action === 'add_specialty') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = 'Το όνομα ειδικότητας είναι υποχρεωτικό.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM specialties WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1');
            $stmt->execute(['name' => $name]);

            if ($stmt->fetch()) {
                $errors[] = 'This specialty already exists.';
            }
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
            redirect_to('modules/admin/manage_lists.php');
        }
    }

    if ($action === 'delete_specialty') {
        $specialtyId = (int) ($_POST['delete_specialty_id'] ?? 0);

        if ($specialtyId <= 0) {
            $errors[] = 'Specialty not found.';
        }

        if (!$errors) {
            $listStmt = $pdo->prepare('SELECT id FROM lists WHERE specialty_id = :specialty_id');
            $listStmt->execute(['specialty_id' => $specialtyId]);
            $listIds = array_map('intval', $listStmt->fetchAll(PDO::FETCH_COLUMN));

            if ($listIds !== []) {
                $placeholders = implode(',', array_fill(0, count($listIds), '?'));

                $stmt = $pdo->prepare('DELETE FROM candidates WHERE list_id IN (' . $placeholders . ')');
                $stmt->execute($listIds);

                $stmt = $pdo->prepare('DELETE FROM lists WHERE id IN (' . $placeholders . ')');
                $stmt->execute($listIds);
            }

            $stmt = $pdo->prepare('DELETE FROM candidates WHERE specialty_id = :specialty_id');
            $stmt->execute(['specialty_id' => $specialtyId]);

            $stmt = $pdo->prepare('DELETE FROM specialties WHERE id = :id');
            $stmt->execute(['id' => $specialtyId]);

            set_flash_message('success', 'Specialty deleted.');
            redirect_to('modules/admin/manage_lists.php');
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
            redirect_to('modules/admin/manage_lists.php');
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
            $isNewList = $listId <= 0;

            $specialtyStmt = $pdo->prepare('SELECT name FROM specialties WHERE id = :id LIMIT 1');
            $specialtyStmt->execute(['id' => $specialtyId]);
            $specialtyName = (string) $specialtyStmt->fetchColumn();

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

            $positionSnapshot = $mode === 'replace'
                ? approved_application_positions_for_list($pdo, $listId)
                : [];

            if ($mode === 'replace') {
                $existingStmt = $pdo->prepare(
                    'SELECT id, name, surname, birth_year
                     FROM candidates
                     WHERE list_id = :list_id'
                );
                $existingStmt->execute(['list_id' => $listId]);

                $existingCandidates = [];
                foreach ($existingStmt->fetchAll() as $candidate) {
                    $existingCandidates[import_candidate_key($candidate)] = (int) $candidate['id'];
                }

                $pdo->prepare('UPDATE candidates SET position = -id WHERE list_id = :list_id')
                    ->execute(['list_id' => $listId]);
            }

            $insertStmt = $pdo->prepare(
                'INSERT INTO candidates (name, surname, birth_year, specialty_id, list_id, position, points)
                 VALUES (:name, :surname, :birth_year, :specialty_id, :list_id, :position, :points)'
            );
            $updateStmt = $pdo->prepare(
                'UPDATE candidates
                 SET name = :name, surname = :surname, birth_year = :birth_year,
                     specialty_id = :specialty_id, list_id = :list_id,
                     position = :position, points = :points
                 WHERE id = :id'
            );
            $keptIds = [];

            foreach ($parsedRows as $row) {
                $candidateId = $mode === 'replace'
                    ? ($existingCandidates[import_candidate_key($row)] ?? 0)
                    : 0;

                if ($candidateId > 0) {
                    $updateStmt->execute([
                        'name' => $row['name'],
                        'surname' => $row['surname'],
                        'birth_year' => $row['birth_year'],
                        'specialty_id' => $specialtyId,
                        'list_id' => $listId,
                        'position' => $row['position'],
                        'points' => $row['points'],
                        'id' => $candidateId,
                    ]);
                    $keptIds[] = $candidateId;
                } else {
                    $insertStmt->execute([
                        'name' => $row['name'],
                        'surname' => $row['surname'],
                        'birth_year' => $row['birth_year'],
                        'specialty_id' => $specialtyId,
                        'list_id' => $listId,
                        'position' => $row['position'],
                        'points' => $row['points'],
                    ]);
                    $keptIds[] = (int) $pdo->lastInsertId();
                }
            }

            if ($mode === 'replace' && $keptIds !== []) {
                $deletePlaceholders = implode(',', array_fill(0, count($keptIds), '?'));
                $deleteStmt = $pdo->prepare(
                    'DELETE FROM candidates
                     WHERE list_id = ? AND id NOT IN (' . $deletePlaceholders . ')'
                );
                $deleteStmt->execute(array_merge([$listId], $keptIds));
            }

            $positionNotifications = notify_position_changes_for_list($pdo, $positionSnapshot);
            $newListNotifications = $isNewList
                ? notify_new_list_subscribers($pdo, $specialtyName, $year)
                : 0;

            if ($newListNotifications > 0 || $positionNotifications > 0) {
                set_flash_message('info', 'Notifications sent: ' . ($newListNotifications + $positionNotifications) . '.');
            }

            set_flash_message('success', 'Ο πίνακας φορτώθηκε επιτυχώς με ' . count($parsedRows) . ' εγγραφές.');
            redirect_to('modules/admin/manage_lists.php');
        }
    }
}

$listKeyword = trim($_GET['keyword'] ?? '');
$listSpecialtyFilter = (int) ($_GET['specialty_id'] ?? 0);
$listYearFilter = trim($_GET['year_filter'] ?? '');
$listStatusFilter = trim($_GET['status_filter'] ?? '');
$listOrder = $_GET['order'] ?? 'year_desc';

$listOrderSql = 'l.year DESC, s.name ASC';
switch ($listOrder) {
    case 'year_asc':
        $listOrderSql = 'l.year ASC, s.name ASC';
        break;
    case 'specialty_asc':
        $listOrderSql = 's.name ASC, l.year DESC';
        break;
    case 'candidates_desc':
        $listOrderSql = 'candidates_count DESC, l.year DESC';
        break;
    case 'candidates_asc':
        $listOrderSql = 'candidates_count ASC, l.year DESC';
        break;
}

$specialties = $pdo->query(
    'SELECT id, name, description, COALESCE(is_active, 1) AS is_active
     FROM specialties
     ORDER BY name ASC'
)->fetchAll();

$listSql = 'SELECT l.id, l.year, s.name AS specialty_name, COALESCE(s.is_active, 1) AS is_active, COUNT(c.id) AS candidates_count
     FROM lists l
     JOIN specialties s ON s.id = l.specialty_id
     LEFT JOIN candidates c ON c.list_id = l.id
     WHERE 1 = 1';
$listParams = [];

if ($listKeyword !== '') {
    $listSql .= ' AND (s.name LIKE :keyword OR CAST(l.year AS CHAR) LIKE :keyword_year)';
    $listParams['keyword'] = '%' . $listKeyword . '%';
    $listParams['keyword_year'] = '%' . $listKeyword . '%';
}

if ($listSpecialtyFilter > 0) {
    $listSql .= ' AND s.id = :specialty_filter';
    $listParams['specialty_filter'] = $listSpecialtyFilter;
}

if ($listYearFilter !== '' && ctype_digit($listYearFilter)) {
    $listSql .= ' AND l.year = :year_filter';
    $listParams['year_filter'] = (int) $listYearFilter;
}

if ($listStatusFilter === 'active') {
    $listSql .= ' AND COALESCE(s.is_active, 1) = 1';
} elseif ($listStatusFilter === 'inactive') {
    $listSql .= ' AND COALESCE(s.is_active, 1) = 0';
}

$listSql .= ' GROUP BY l.id, l.year, s.name, s.is_active ORDER BY ' . $listOrderSql;
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($listParams);
$listRows = $listStmt->fetchAll();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lists</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<script>
    // Delete List Modal functions
    function openDeleteListModal(listId) {
        document.getElementById("deleteListId").value = listId;
        document.getElementById("deleteListModal").classList.add("show");
    }

    function closeDeleteListModal() {
        document.getElementById("deleteListModal").classList.remove("show");
        document.getElementById("deleteListId").value = "";
    }

    // Close modals when clicking outside
    window.onclick = function(e) {
        if (e.target.classList.contains("modal")) {
            closeDeleteListModal();
        }
    }

    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            closeDeleteListModal();
        }
    });
</script>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'admin'; $pageKey = 'manage_lists'; require __DIR__ . '/../../includes/nav.php'; ?>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Admin Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Manage Lists</h1>
                <p class="auth-subtitle">Επιλέξτε τις ειδικότητες που θα είναι ενεργές και φορτώστε νέους πίνακες με δεδομένα υποψηφίων μέσω CSV, TSV ή επικόλλησης από εξωτερική πηγή.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>


                <div class="split-grid">
                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Ενεργές Ειδικότητες</h2>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="save_specialties">
                            <?php 
                            $activeCount = count(array_filter($specialties, function($s) { return $s['is_active']; }));
                            ?>
                            <p class="active-counter"><strong><?php echo $activeCount; ?></strong> από <strong><?php echo count($specialties); ?></strong> ειδικότητες ενεργές</p>
                            <div class="specialties-compact-grid">
                                <?php foreach ($specialties as $specialty): ?>
                                    <div class="specialty-compact-card">
                                        <input type="checkbox" name="active_specialties[]" value="<?php echo h($specialty['id']); ?>" id="spec_<?php echo h($specialty['id']); ?>" <?php echo (int) $specialty['is_active'] === 1 ? 'checked' : ''; ?>>
                                        <div class="specialty-info">
                                            <label for="spec_<?php echo h($specialty['id']); ?>" class="specialty-label-row">
                                                <span class="specialty-name"><?php echo h($specialty['name']); ?></span>
                                                <span class="spec-status-badge <?php echo $specialty['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $specialty['is_active'] ? '✓' : '✗'; ?>
                                                </span>
                                            </label>
                                            <span class="specialty-desc">
                                                <?php echo h($specialty['description'] ?: 'Χωρίς περιγραφή'); ?>
                                            </span>
                                            <button type="submit" name="delete_specialty_id" value="<?php echo h($specialty['id']); ?>" class="table-button danger specialty-delete-button">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="primary-button">Αποθήκευση Επιλογών</button>
                        </form>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Προσθήκη Ειδικότητας</h2>
                        
                        <form method="post" action="" class="add-specialty-form">
                            <input type="hidden" name="action" value="add_specialty">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Όνομα Ειδικότητας</label>
                                    <input type="text" name="name" id="name" class="form-input" 
                                           placeholder="π.χ. Πληροφορική, Χημεία, Φυσική" required>
                                    <span class="form-hint">Το όνομα είναι υποχρεωτικό.</span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Περιγραφή (προαιρετικό)</label>
                                    <input type="text" name="description" id="description" class="form-input"
                                           placeholder="Σύντομη περιγραφή της ειδικότητας">
                                    <span class="form-hint">Μια σύντομη περιγραφή βοηθά στην καλύτερη οργάνωση.</span>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="primary-button">
                                    <span class="btn-icon">+</span> Προσθήκη Ειδικότητας
                                </button>
                            </div>
                        </form>
                    </div>
                </div>


                <div class="section-card section-card-compact">
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

                        <button type="submit" class="primary-button">Φόρτωση Πίνακα</button>
                    </form>
                </div>


                <div class="section-card section-card-compact">
                    <div class="section-header">
                        <h2 class="section-title">Υφιστάμενοι Πίνακες</h2>
                        <p class="section-text">Εμφάνιση όλων των πινάκων με πλήθος υποψηφίων ανά ειδικότητα και έτος.</p>
                    </div>

                    <div class="search-panel">
                        <form method="get" action="" class="search-bar" role="search" aria-label="Search lists">
                            <label class="search-bar__field">
                                <svg class="search-bar__field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                                <input class="search-bar__field-input" type="search" name="keyword" value="<?php echo h($listKeyword); ?>" placeholder="Search specialty or year..." autocomplete="off">
                            </label>

                            <select class="search-bar__filter" name="specialty_id" aria-label="Specialty filter">
                                <option value="0">All specialties</option>
                                <?php foreach ($specialties as $specialty): ?>
                                    <option value="<?php echo h($specialty['id']); ?>" <?php echo $listSpecialtyFilter === (int) $specialty['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($specialty['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input class="search-bar__filter" type="number" name="year_filter" value="<?php echo h($listYearFilter); ?>" placeholder="Year" min="2000" max="2100" aria-label="Year filter">

                            <select class="search-bar__filter" name="status_filter" aria-label="Specialty status filter">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo $listStatusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $listStatusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>

                            <select class="search-bar__filter" name="order" aria-label="Order lists">
                                <option value="year_desc" <?php echo $listOrder === 'year_desc' ? 'selected' : ''; ?>>Newest first</option>
                                <option value="year_asc" <?php echo $listOrder === 'year_asc' ? 'selected' : ''; ?>>Oldest first</option>
                                <option value="specialty_asc" <?php echo $listOrder === 'specialty_asc' ? 'selected' : ''; ?>>Specialty A-Z</option>
                                <option value="candidates_desc" <?php echo $listOrder === 'candidates_desc' ? 'selected' : ''; ?>>Most candidates</option>
                                <option value="candidates_asc" <?php echo $listOrder === 'candidates_asc' ? 'selected' : ''; ?>>Fewest candidates</option>
                            </select>

                            <button type="submit" class="search-bar__btn search-bar__btn--primary">Search</button>
                            <a class="search-bar__btn search-bar__btn--ghost" href="manage_lists.php">Clear</a>
                        </form>
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
                                                <span class="status-badge <?php echo (int)$list['is_active'] === 1 ? 'admin' : 'candidate'; ?>">
                                                    <?php echo (int) $list['is_active'] === 1 ? 'active' : 'inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo h($list['candidates_count']); ?></td>
                                            <td class="table-actions">
                                                <button type="button" class="table-button danger" onclick="openDeleteListModal(<?php echo h($list['id']); ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
    </div> <!-- close auth-card -->
    <!-- Delete List Modal -->
    <div id="deleteListModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeDeleteListModal()">×</button>
            <h3 class="section-title">Διαγραφή Πίνακα</h3>
            <p class="section-text">
                Είσαι σίγουρος ότι θέλεις να διαγράψεις αυτόν τον πίνακα;<br>
                <strong>Οι υποψήφιοι θα διαγραφούν οριστικά.</strong>
            </p>
            <form method="post">
                <input type="hidden" name="action" value="delete_list">
                <input type="hidden" name="list_id" id="deleteListId">
                <div class="modal-actions">
                    <button type="submit" class="table-button danger">Delete</button>
                    <button type="button" class="button-link secondary" onclick="closeDeleteListModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Delete List Modal functions
        function openDeleteListModal(listId) {
            document.getElementById("deleteListId").value = listId;
            document.getElementById("deleteListModal").classList.add("show");
        }

        function closeDeleteListModal() {
            document.getElementById("deleteListModal").classList.remove("show");
            document.getElementById("deleteListId").value = "";
        }

        // Close modals when clicking outside
        window.onclick = function(e) {
            if (e.target.classList.contains("modal")) {
                closeDeleteListModal();
            }
        }

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                closeDeleteListModal();
            }
        });
    </script>
    </div> <!-- close auth-container -->
</body>
</html>
