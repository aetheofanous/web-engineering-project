<?php
// Admin Manage Users — full CRUD (add / update / remove) on the users table.
// Rewritten to match the new schema (name/surname/password/phone) and to
// provide search/filter/order on top of the user list.

require_once __DIR__ . '/../../includes/bootstrap.php';

$adminUser = require_admin_role();
$pdo = pdo();

$errors = [];
$currentUserId = (int) $adminUser['id'];

// --------------------------------------------------------------------------
// Helpers for this page (kept inline so we don't pollute global functions.php).
// --------------------------------------------------------------------------
function validate_user_basics(array $input, bool $passwordRequired = true, ?int $ignoreId = null): array
{
    $errors = [];
    $name     = trim($input['name'] ?? '');
    $surname  = trim($input['surname'] ?? '');
    $email    = trim($input['email'] ?? '');
    $phone    = trim($input['phone'] ?? '');
    $role     = trim($input['role'] ?? '');
    $password = $input['password'] ?? '';

    if ($name === '' || $surname === '' || $email === '' || $role === '') {
        $errors[] = 'Όνομα, επίθετο, email και ρόλος είναι υποχρεωτικά.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Το email δεν είναι έγκυρο.';
    }

    if ($role !== '' && !in_array($role, ['admin', 'candidate'], true)) {
        $errors[] = 'Ο ρόλος δεν είναι έγκυρος.';
    }

    if ($passwordRequired && $password === '') {
        $errors[] = 'Ο κωδικός του νέου χρήστη είναι υποχρεωτικός.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    return [
        'errors' => $errors,
        'data'   => [
            'name'     => $name,
            'surname'  => $surname,
            'email'    => $email,
            'phone'    => $phone !== '' ? $phone : null,
            'role'     => $role,
            'password' => $password,
        ],
    ];
}

// --------------------------------------------------------------------------
// POST handling
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $validation = validate_user_basics($_POST, true);
        $errors     = $validation['errors'];
        $data       = $validation['data'];

        // Check for duplicate email.
        if ($errors === []) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $data['email']]);

            if ($stmt->fetch()) {
                $errors[] = 'Υπάρχει ήδη χρήστης με αυτό το email.';
            }
        }

        if ($errors === []) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, surname, email, password, phone, role, notify_new_lists, notify_position_changes)
                 VALUES (:name, :surname, :email, :password, :phone, :role, 1, 1)'
            );
            $stmt->execute([
                'name'     => $data['name'],
                'surname'  => $data['surname'],
                'email'    => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'phone'    => $data['phone'],
                'role'     => $data['role'],
            ]);

            add_flash('success', 'Ο νέος χρήστης δημιουργήθηκε επιτυχώς.');
            redirect_to('modules/admin/manage_users.php');
        }
    }

    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $validation = validate_user_basics($_POST, false);
        $errors     = $validation['errors'];
        $data       = $validation['data'];

        if ($userId <= 0) {
            $errors[] = 'Ο χρήστης δεν βρέθηκε.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);

            if (!$stmt->fetch()) {
                $errors[] = 'Ο χρήστης δεν βρέθηκε.';
            }
        }

        // Prevent the admin from removing their own admin role.
        if ($userId === $currentUserId && $data['role'] !== 'admin') {
            $errors[] = 'Δεν μπορείτε να αφαιρέσετε τα admin δικαιώματα από τον δικό σας λογαριασμό.';
        }

        // Duplicate email check (excluding current user).
        if ($errors === []) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $stmt->execute([
                'email' => $data['email'],
                'id'    => $userId,
            ]);

            if ($stmt->fetch()) {
                $errors[] = 'Το email χρησιμοποιείται ήδη από άλλον χρήστη.';
            }
        }

        if ($errors === []) {
            if ($data['password'] !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET name = :name, surname = :surname, email = :email, phone = :phone,
                         role = :role, password = :password
                     WHERE id = :id'
                );
                $stmt->execute([
                    'name'     => $data['name'],
                    'surname'  => $data['surname'],
                    'email'    => $data['email'],
                    'phone'    => $data['phone'],
                    'role'     => $data['role'],
                    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                    'id'       => $userId,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET name = :name, surname = :surname, email = :email, phone = :phone, role = :role
                     WHERE id = :id'
                );
                $stmt->execute([
                    'name'    => $data['name'],
                    'surname' => $data['surname'],
                    'email'   => $data['email'],
                    'phone'   => $data['phone'],
                    'role'    => $data['role'],
                    'id'      => $userId,
                ]);
            }

            // Keep session in sync when the admin updates their own record.
            if ($userId === $currentUserId) {
                $refreshStmt = $pdo->prepare('SELECT id, name, surname, email, role, phone FROM users WHERE id = :id');
                $refreshStmt->execute(['id' => $userId]);
                $refreshed = $refreshStmt->fetch();
                if ($refreshed) {
                    login_user($refreshed);
                }
            }

            add_flash('success', 'Τα στοιχεία του χρήστη ενημερώθηκαν.');
            redirect_to('modules/admin/manage_users.php');
        }
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $errors[] = 'Ο χρήστης προς διαγραφή δεν βρέθηκε.';
        }

        if ($userId === $currentUserId) {
            $errors[] = 'Δεν μπορείτε να διαγράψετε τον λογαριασμό που χρησιμοποιείτε αυτή τη στιγμή.';
        }

        if ($errors === []) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);

            add_flash('success', 'Ο χρήστης διαγράφηκε.');
            redirect_to('modules/admin/manage_users.php');
        }
    }
}

// --------------------------------------------------------------------------
// Search / filter / order (G3 requirement)
// --------------------------------------------------------------------------
$keyword     = trim($_GET['keyword'] ?? '');
$roleFilter  = trim($_GET['role_filter'] ?? '');
$order       = $_GET['order'] ?? 'created_desc';

$orderSql = 'created_at DESC, id DESC';
switch ($order) {
    case 'created_asc':
        $orderSql = 'created_at ASC, id ASC';
        break;
    case 'name_asc':
        $orderSql = 'surname ASC, name ASC';
        break;
    case 'name_desc':
        $orderSql = 'surname DESC, name DESC';
        break;
    case 'email_asc':
        $orderSql = 'email ASC';
        break;
}

$sql = 'SELECT id, name, surname, email, phone, role, created_at FROM users WHERE 1 = 1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (name LIKE :kw OR surname LIKE :kw OR email LIKE :kw)';
    $params['kw'] = '%' . $keyword . '%';
}

if ($roleFilter !== '' && in_array($roleFilter, ['admin', 'candidate'], true)) {
    $sql .= ' AND role = :role_filter';
    $params['role_filter'] = $roleFilter;
}

$sql .= ' ORDER BY ' . $orderSql;

$usersStmt = $pdo->prepare($sql);
$usersStmt->execute($params);
$users = $usersStmt->fetchAll();

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
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/style.css') ?: time(); ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <span class="eyebrow">Admin Module</span>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Admin Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Manage Users</h1>
                <p class="auth-subtitle">Διαχείριση εγγεγραμμένων χρηστών της εφαρμογής: πλήρες CRUD (add / update / remove) μαζί με αναζήτηση, φίλτρο ρόλου και ταξινόμηση.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="search-panel">
                    <form method="get" action="" class="search-bar" role="search" aria-label="Αναζήτηση χρηστών">
                        <label class="search-bar__field">
                            <svg class="search-bar__field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input class="search-bar__field-input" type="search" name="keyword" id="keyword" value="<?php echo h($keyword); ?>" placeholder="Αναζήτηση σε όνομα, επίθετο ή email…" autocomplete="off">
                        </label>

                        <select class="search-bar__filter" name="role_filter" id="role_filter" aria-label="Φίλτρο ρόλου">
                            <option value="">Όλοι οι ρόλοι</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="candidate" <?php echo $roleFilter === 'candidate' ? 'selected' : ''; ?>>Candidate</option>
                        </select>

                        <select class="search-bar__filter" name="order" id="order" aria-label="Ταξινόμηση">
                            <option value="created_desc" <?php echo $order === 'created_desc' ? 'selected' : ''; ?>>Νεότερες πρώτα</option>
                            <option value="created_asc"  <?php echo $order === 'created_asc'  ? 'selected' : ''; ?>>Παλαιότερες πρώτα</option>
                            <option value="name_asc"     <?php echo $order === 'name_asc'     ? 'selected' : ''; ?>>Επίθετο A→Z</option>
                            <option value="name_desc"    <?php echo $order === 'name_desc'    ? 'selected' : ''; ?>>Επίθετο Z→A</option>
                            <option value="email_asc"    <?php echo $order === 'email_asc'    ? 'selected' : ''; ?>>Email A→Z</option>
                        </select>

                        <button type="submit" class="search-bar__btn search-bar__btn--primary">Search</button>
                        <a class="search-bar__btn search-bar__btn--ghost" href="manage_users.php">Clear</a>
                    </form>
                    <span class="search-bar-hint">Φιλτράρετε με όνομα/επίθετο/email, ρόλο και ταξινόμηση.</span>
                </div>

                <section class="section-card section-card-compact">
                    <div class="section-header">
                        <h2 class="section-title">Εγγεγραμμένοι Χρήστες</h2>
                        <p class="section-text">Σύνολο: <strong><?php echo h(count($users)); ?></strong></p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Όνομα</th>
                                    <th>Επίθετο</th>
                                    <th>Email</th>
                                    <th>Τηλέφωνο</th>
                                    <th>Ρόλος</th>
                                    <th>Εγγραφή</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users === []): ?>
                                    <tr>
                                        <td colspan="8" class="empty-cell">Δεν βρέθηκαν χρήστες για τα επιλεγμένα φίλτρα.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?php echo h($u['id']); ?></td>
                                            <td><?php echo h($u['name']); ?></td>
                                            <td><?php echo h($u['surname']); ?></td>
                                            <td><?php echo h($u['email']); ?></td>
                                            <td><?php echo h($u['phone'] ?? '-'); ?></td>
                                            <td><span class="status-badge <?php echo h($u['role']); ?>"><?php echo h($u['role']); ?></span></td>
                                            <td><?php echo h(date('d/m/Y', strtotime($u['created_at']))); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button" class="button-link table-button secondary"
                                                        onclick='openEditModal(<?php echo (int) $u["id"]; ?>, <?php echo json_encode($u["name"]); ?>, <?php echo json_encode($u["surname"]); ?>, <?php echo json_encode($u["email"]); ?>, <?php echo json_encode($u["phone"] ?? ""); ?>, <?php echo json_encode($u["role"]); ?>)'>
                                                        Edit
                                                    </button>
                                                    <?php if ((int) $u['id'] !== $currentUserId): ?>
                                                        <button type="button" class="table-button danger"
                                                            onclick="openDeleteModal(<?php echo (int) $u['id']; ?>, <?php echo json_encode($u['name'] . ' ' . $u['surname']); ?>)">
                                                            Delete
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="section-card section-card-compact">
                    <h2 class="section-title">Προσθήκη Νέου Χρήστη</h2>
                    <p class="section-text">Ο νέος χρήστης θα μπορεί να συνδεθεί αμέσως με το email και τον κωδικό που θα ορίσετε.</p>

                    <form method="post" action="" class="add-specialty-form">
                        <input type="hidden" name="action" value="add_user">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_name">Όνομα</label>
                                <input type="text" name="name" id="new_name" class="form-input" required>
                                <span class="form-hint">Υποχρεωτικό.</span>
                            </div>
                            <div class="form-group">
                                <label for="new_surname">Επίθετο</label>
                                <input type="text" name="surname" id="new_surname" class="form-input" required>
                                <span class="form-hint">Υποχρεωτικό.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_email">Email</label>
                                <input type="email" name="email" id="new_email" class="form-input" required>
                                <span class="form-hint">Πρέπει να είναι μοναδικό στο σύστημα.</span>
                            </div>
                            <div class="form-group">
                                <label for="new_phone">Τηλέφωνο</label>
                                <input type="text" name="phone" id="new_phone" class="form-input"
                                       placeholder="π.χ. +357 99 123456">
                                <span class="form-hint">Προαιρετικό.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_role">Ρόλος</label>
                                <select name="role" id="new_role" class="form-input" required>
                                    <option value="">-- Επιλέξτε ρόλο --</option>
                                    <option value="candidate">candidate</option>
                                    <option value="admin">admin</option>
                                </select>
                                <span class="form-hint">Ο ρόλος ορίζει ποιο module θα βλέπει.</span>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Κωδικός</label>
                                <input type="password" name="password" id="new_password" class="form-input"
                                       minlength="8" required>
                                <span class="form-hint">Τουλάχιστον 8 χαρακτήρες.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">+</span> Δημιουργία Χρήστη
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeDeleteModal()">×</button>
            <h3 class="section-title">Διαγραφή Χρήστη</h3>
            <p class="section-text">
                Είσαι σίγουρος/η ότι θέλεις να διαγράψεις τον/την <strong id="deleteUserLabel"></strong>;
                <br><strong>Η ενέργεια είναι μη αναστρέψιμη.</strong>
            </p>
            <form method="post">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-actions">
                    <button type="submit" class="table-button danger">Delete</button>
                    <button type="button" class="button-link secondary" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeEditModal()">×</button>
            <h3 class="section-title">Επεξεργασία Χρήστη</h3>
            <form method="post">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">

                <label for="edit_name">Όνομα</label>
                <input type="text" name="name" id="edit_name" required>

                <label for="edit_surname">Επίθετο</label>
                <input type="text" name="surname" id="edit_surname" required>

                <label for="edit_email">Email</label>
                <input type="email" name="email" id="edit_email" required>

                <label for="edit_phone">Τηλέφωνο</label>
                <input type="text" name="phone" id="edit_phone" placeholder="π.χ. +357 99 123456">

                <label for="edit_role">Ρόλος</label>
                <select name="role" id="edit_role" required>
                    <option value="candidate">candidate</option>
                    <option value="admin">admin</option>
                </select>

                <label for="edit_password">Νέος Κωδικός</label>
                <input type="password" name="password" id="edit_password" minlength="8"
                       placeholder="Αφήστε κενό για διατήρηση">

                <div class="modal-actions">
                    <button type="submit" class="primary-button">Αποθήκευση</button>
                    <button type="button" class="button-link secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(id, label) {
            document.getElementById("deleteUserId").value = id;
            document.getElementById("deleteUserLabel").textContent = label || ("ID " + id);
            document.getElementById("deleteModal").classList.add("show");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.remove("show");
            document.getElementById("deleteUserId").value = "";
            document.getElementById("deleteUserLabel").textContent = "";
        }

        function openEditModal(id, name, surname, email, phone, role) {
            document.getElementById("editUserId").value = id;
            document.getElementById("edit_name").value = name || "";
            document.getElementById("edit_surname").value = surname || "";
            document.getElementById("edit_email").value = email || "";
            document.getElementById("edit_phone").value = phone || "";
            var roleSelect = document.getElementById("edit_role");
            if (roleSelect.querySelector('option[value="' + role + '"]')) {
                roleSelect.value = role;
            }
            document.getElementById("edit_password").value = "";
            document.getElementById("editModal").classList.add("show");
        }

        function closeEditModal() {
            document.getElementById("editModal").classList.remove("show");
            document.getElementById("editUserId").value = "";
        }

        window.onclick = function (e) {
            if (e.target.classList.contains("modal")) {
                closeDeleteModal();
                closeEditModal();
            }
        };

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeDeleteModal();
                closeEditModal();
            }
        });
    </script>
</body>
</html>
