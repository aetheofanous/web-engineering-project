<?php
// Router-only file: forwards logged-in users to their role-specific dashboard.
require_once __DIR__ . '/../includes/functions.php';

require_login('../auth/login.php');

$role = $_SESSION['role'] ?? 'candidate';

if ($role === 'admin') {
    redirect_to('modules/admin/dashboard.php');
} elseif ($role === 'candidate') {
    redirect_to('modules/candidate/dashboard.php');
}

// Fallback for unknown roles
redirect_to('auth/login.php');