<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'candidate';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<div class="page-wrap">
    <div class="page-card">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
            Καλωσόρισες, <strong><?php echo h($username); ?></strong>.
        </p>

        <div class="quick-grid">
            <div class="quick-box">
                <h3>Στοιχεία Χρήστη</h3>
                <p><strong>Username:</strong> <?php echo h($username); ?></p>
                <p><strong>Role:</strong> <?php echo h($role); ?></p>
            </div>

            <div class="quick-box">
                <h3>Λίστα Υποψηφίων</h3>
                <p>Αναζήτηση υποψηφίων με λέξη-κλειδί.</p>
                <p><a href="list.php">Άνοιγμα λίστας</a></p>
            </div>

            <div class="quick-box">
                <h3>Αποσύνδεση</h3>
                <p>Έξοδος από το σύστημα.</p>
                <p><a href="../auth/logout.php">Logout</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>