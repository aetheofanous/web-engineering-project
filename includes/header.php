<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πίνακες Διοριστέων</title>
    <link rel="stylesheet" href="http://localhost/pinakas_dioristewn/assets/css/style.css?v=123">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <div class="brand-block">
            <h1>Πίνακες Διοριστέων</h1>
            <p>Εφαρμογή Παρακολούθησης Υποψηφίων και Ειδικοτήτων</p>
        </div>
    </div>
</header>