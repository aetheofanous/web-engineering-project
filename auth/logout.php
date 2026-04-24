<?php
// Logout endpoint. Sessions are cleared and the user returns to the login page.

require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
add_flash('success', 'Η αποσύνδεση ολοκληρώθηκε με επιτυχία.');
redirect_to('auth/login.php');
