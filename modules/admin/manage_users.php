<?php
require_once __DIR__ . '/../../includes/functions.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';

$errors = [];
$editUser = null;
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $role === '' || $password === '') {
            $errors[] = 'Συμπληρώστε όλα τα πεδία για νέο χρήστη.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Το email του νέου χρήστη δεν είναι έγκυρο.';
        }

        if ($role !== '' && !in_array($role, ['admin', 'candidate'], true)) {
            $errors[] = 'Ο ρόλος του νέου χρήστη δεν είναι έγκυρος.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Ο κωδικός του νέου χρήστη πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $errors[] = 'Υπάρχει ήδη χρήστης με αυτό το email.';
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role)
                 VALUES (:username, :email, :password_hash, :role)'
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);

            set_flash_message('success', 'Ο νέος χρήστης δημιουργήθηκε επιτυχώς.');
            redirect_to('manage_users.php');
        }
    }

    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';


        if ($userId <= 0 || $username === '' || $email === '' || $role === '') {
            $errors[] = 'Τα στοιχεία ενημέρωσης χρήστη δεν είναι πλήρη.';
        } else {
            // Extra safety: check if user exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            if (!$stmt->fetch()) {
                $errors[] = 'Ο χρήστης δεν βρέθηκε.';
            }
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Το email δεν είναι έγκυρο.';
        }

        if ($role !== '' && !in_array($role, ['admin', 'candidate'], true)) {
            $errors[] = 'Ο ρόλος δεν είναι έγκυρος.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
        }

        if ($userId === $currentUserId && $role !== 'admin') {
            $errors[] = 'Δεν μπορείτε να αφαιρέσετε τα admin δικαιώματα από τον δικό σας λογαριασμό.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $stmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);

            if ($stmt->fetch()) {
                $errors[] = 'Το email χρησιμοποιείται ήδη από άλλον χρήστη.';
            }
        }

        if (!$errors) {
            if ($password !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET username = :username, email = :email, role = :role, password_hash = :password_hash
                     WHERE id = :id'
                );
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $userId,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET username = :username, email = :email, role = :role
                     WHERE id = :id'
                );
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                    'id' => $userId,
                ]);
            }

            if ($userId === $currentUserId) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }

            set_flash_message('success', 'Τα στοιχεία του χρήστη ενημερώθηκαν.');
            redirect_to('manage_users.php');
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

        if (!$errors) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);

            set_flash_message('success', 'Ο χρήστης διαγράφηκε.');
            redirect_to('manage_users.php');
        }
    }
}


$users = $pdo->query(
    'SELECT id, username, email, role, created_at
     FROM users
     ORDER BY created_at DESC, id DESC'
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
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <span class="eyebrow">Admin Module</span>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        Επιστροφή στο Admin Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Manage Users</h1>
                <p class="auth-subtitle">Ο διαχειριστής μπορεί να βλέπει όλους τους εγγεγραμμένους χρήστες και να εκτελεί πλήρες CRUD στις εγγραφές τους.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <section class="panel-section">
                    <div class="section-header">
                        <h2 class="section-title">Εγγεγραμμένοι Χρήστες</h2>
                        <p class="section-text">Σύνολο χρηστών: <strong><?php echo h(count($users)); ?></strong></p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo h($user['id']); ?></td>
                                        <td><?php echo h($user['username']); ?></td>
                                        <td><?php echo h($user['email']); ?></td>
                                        <td><span class="status-badge <?php echo h($user['role']); ?>"><?php echo h($user['role']); ?></span></td>
                                        <td><?php echo h($user['created_at']); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button 
                                                    class="button-link table-button secondary"
                                                    onclick='openEditModal(<?php echo $user["id"]; ?>, <?php echo json_encode($user["username"]); ?>, <?php echo json_encode($user["email"]); ?>, <?php echo json_encode($user["role"]); ?>)'>
                                                    Edit
                                                </button>
                                                <button type="button" class="table-button danger"
                                                    onclick="openDeleteModal(<?php echo $user['id']; ?>)">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="split-grid">
                    <section class="panel-section">
                        <h2 class="section-title">Add User</h2>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="add_user">

                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" required>

                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" required>

                            <label for="role">Role</label>
                            <select name="role" id="role" required>
                                <option value="">-- Επιλέξτε ρόλο --</option>
                                <option value="candidate">candidate</option>
                                <option value="admin">admin</option>
                            </select>

                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" required>

                            <button type="submit" class="primary-button">Create User</button>
                        </form>
                    </section>

                    <!-- Update User section removed: editing now handled by modal -->
                </div>

                <!-- page-actions removed: button now in banner -->
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeDeleteModal()">×</button>
            <h3 class="section-title">Delete User</h3>
            <p class="section-text">
                Είσαι σίγουρος ότι θέλεις να διαγράψεις αυτόν τον χρήστη;
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

    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeEditModal()">×</button>
            <h3 class="section-title">Edit User</h3>
            <form method="post">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="editUserId">

                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>

                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>

                <label>Role</label>
                <select name="role" id="edit_role">
                    <option value="candidate">candidate</option>
                    <option value="admin">admin</option>
                </select>

                <label>New Password</label>
                <input type="password" name="password" placeholder="Αφήστε κενό για διατήρηση">

                <div class="modal-actions">
                    <button type="submit" class="primary-button">Save Changes</button>
                    <button type="button" class="button-link secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(id) {
            document.getElementById("deleteUserId").value = id;
            document.getElementById("deleteModal").classList.add("show");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.remove("show");
            document.getElementById("deleteUserId").value = "";
        }

        function openEditModal(id, username, email, role) {
            console.log("clicked", id, username, email, role);
            document.getElementById("editUserId").value = id;
            document.getElementById("edit_username").value = username;
            document.getElementById("edit_email").value = email;
            const roleSelect = document.getElementById("edit_role");
            if (roleSelect.querySelector(`option[value="${role}"]`)) {
                roleSelect.value = role;
            }
            document.getElementById("editModal").classList.add("show");
        }

        function closeEditModal() {
            document.getElementById("editModal").classList.remove("show");
            document.getElementById("editUserId").value = "";
            document.getElementById("edit_username").value = "";
            document.getElementById("edit_email").value = "";
            document.getElementById("edit_role").value = "candidate";
        }

        window.onclick = function(e) {
            if (e.target.classList.contains("modal")) {
                closeDeleteModal();
                closeEditModal();
            }
        }

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                closeDeleteModal();
                closeEditModal();
            }
        });
    </script>
</body>
</html>