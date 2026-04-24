<?php
// Smart router page. It sends each logged-in user to the right module dashboard.

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();

if ($user === null) {
    redirect_to('modules/search/dashboard.php');
}

redirect_to(role_dashboard_path($user['role']));
