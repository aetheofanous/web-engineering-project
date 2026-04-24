<?php
// API home page that documents the available JSON endpoints for Postman demo.

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'API Module';
$pageSubtitle = 'Συγκεντρωτική τεκμηρίωση των JSON endpoints για το Postman demo της παρουσίασης.';
$moduleKey = 'api';
$pageKey = 'dashboard';
$heroStats = [
    ['value' => 6, 'label' => 'Core Endpoints'],
    ['value' => 'GET/POST/PUT/DELETE', 'label' => 'Supported Methods'],
];

require __DIR__ . '/../includes/header.php';
?>

<section class="api-grid">
    <article class="panel api-card">
        <h2>`GET` Candidates</h2>
        <p>Επιστρέφει όλους τους υποψηφίους ή φιλτραρισμένα αποτελέσματα.</p>
        <code>/api/candidates.php?keyword=math&amp;year=2024</code>
    </article>
    <article class="panel api-card">
        <h2>`POST` Candidates</h2>
        <p>Δημιουργεί νέο candidate record από JSON body.</p>
        <code>/api/candidates.php</code>
    </article>
    <article class="panel api-card">
        <h2>`PUT` Lists</h2>
        <p>Ενημερώνει status ή έτος υπάρχουσας λίστας.</p>
        <code>/api/lists.php?id=4</code>
    </article>
    <article class="panel api-card">
        <h2>`DELETE` Tracked</h2>
        <p>Αφαιρεί εγγραφή από τη λίστα παρακολούθησης.</p>
        <code>/api/tracked.php?id=2</code>
    </article>
    <article class="panel api-card">
        <h2>`GET` Stats</h2>
        <p>Επιστρέφει συγκεντρωτικά metrics για charts και reports.</p>
        <code>/api/stats.php</code>
    </article>
    <article class="panel api-card">
        <h2>`GET` Lists</h2>
        <p>Επιστρέφει τις λίστες με specialty metadata.</p>
        <code>/api/lists.php</code>
    </article>
</section>

<section class="table-card">
    <h2>Example JSON Response</h2>
    <pre>{
  "status": "success",
  "count": 2,
  "data": [
    {
      "id": 1,
      "name": "Maria",
      "surname": "Ioannou",
      "specialty": "Mathematics"
    }
  ]
}</pre>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
