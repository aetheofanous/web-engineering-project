<?php
require_once __DIR__ . '/../includes/functions.php';

require_login('../auth/login.php');

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'candidate';

if ($role === 'admin') {
    redirect_to('admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πίνακας Ελέγχου</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Προσωπικός Χώρος</p>
                <h1 class="auth-title">Πίνακας Ελέγχου</h1>
                <p class="auth-subtitle">Καλωσορίσατε, <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>. Από εδώ μπορείτε να μεταβείτε στους καταλόγους διοριστέων και στις βασικές λειτουργίες της εφαρμογής.</p>
            </div>

            <div class="page-body">
                <div class="info-grid">
                    <section class="info-box">
                        <h2>Στοιχεία Λογαριασμού</h2>
                        <p class="section-text">Όνομα χρήστη: <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <p class="section-text">Ρόλος: <span class="status-badge"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></span></p>
                    </section>

                    <section class="info-box">
                        <h2>Διαθέσιμες Ενέργειες</h2>
                        <ul class="plain-list">
                            <li>Προβολή και αναζήτηση στους καταλόγους διοριστέων.</li>
                            <li>Πλοήγηση με ασφαλή συνεδρία χρήστη.</li>
                            <li>Άμεση αποσύνδεση από την εφαρμογή.</li>
                        </ul>
                    </section>
                </div>

                <div class="page-actions">
                    <a class="button-link" href="list.php">Μετάβαση στους Καταλόγους</a>
                    <a class="button-link secondary" href="../auth/logout.php">Αποσύνδεση</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>