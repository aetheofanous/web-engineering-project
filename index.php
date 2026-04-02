<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/nav.php'; ?>

<div class="page-wrap">
    <div class="page-card">

        <h1 class="page-title">Σύστημα Πινάκων Διοριστέων</h1>
        <p class="page-subtitle">Κεντρική σελίδα εφαρμογής</p>

        <div class="quick-grid">

            <div class="quick-box">
                <h3>Login</h3>
                <a href="auth/login.php">Μετάβαση</a>
            </div>

            <div class="quick-box">
                <h3>Register</h3>
                <a href="auth/register.php">Μετάβαση</a>
            </div>

            <div class="quick-box">
                <h3>Dashboard</h3>
                <a href="modules/dashboard.php">Μετάβαση</a>
            </div>

            <div class="quick-box">
                <h3>Λίστα</h3>
                <a href="modules/list.php">Μετάβαση</a>
            </div>

        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>