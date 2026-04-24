<?php
// API home page that documents the available JSON endpoints for Postman demo.

require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'API Module';
$pageSubtitle = 'Συγκεντρωτική τεκμηρίωση των JSON endpoints για το Postman demo της παρουσίασης.';
$moduleKey = 'api';
$pageKey = 'dashboard';
$showTopbar = false;
$showModuleNav = false;
$showHero = false;
$heroStats = [
    ['value' => 6, 'label' => 'Core Endpoints'],
    ['value' => 'GET/POST/PUT/DELETE', 'label' => 'Supported Methods'],
];

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card narrow">
        <div class="page-banner">
            <div class="banner-row-flex">
                <span class="eyebrow">API Documentation</span>
                <a class="button-link secondary header-back-link" href="../index.php">← Επιστροφή στην Αρχική</a>
            </div>
            <h1 class="auth-title">API Module</h1>
            <p class="auth-subtitle">Συγκεντρωτική τεκμηρίωση των JSON endpoints για το Postman demo της παρουσίασης.</p>
        </div>

        <div class="page-body">
            <section class="section-card">
                <div class="section-header">
                    <h2 class="section-title">Available Endpoints</h2>
                    <p class="section-text">Χρησιμοποιήστε τα παρακάτω endpoints για να αλληλεπιδράσετε με τους πίνακες και τα στατιστικά της εφαρμογής.</p>
                </div>

                <div class="api-grid">
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-get">GET</span> Candidates</h3>
                        <p>Επιστρέφει όλους τους υποψηφίους ή φιλτραρισμένα αποτελέσματα.</p>
                        <code id="api-url-1" class="code-block">/api/candidates.php?keyword=math&amp;year=2024</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-1">Αντιγραφή</button>
                        </div>
                    </article>
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-post">POST</span> Candidates</h3>
                        <p>Δημιουργεί νέο candidate record από JSON body.</p>
                        <code id="api-url-2" class="code-block">/api/candidates.php</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-2">Αντιγραφή</button>
                        </div>
                    </article>
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-put">PUT</span> Lists</h3>
                        <p>Ενημερώνει status ή έτος υπάρχουσας λίστας.</p>
                        <code id="api-url-3" class="code-block">/api/lists.php?id=4</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-3">Αντιγραφή</button>
                        </div>
                    </article>
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-delete">DELETE</span> Tracked</h3>
                        <p>Αφαιρεί εγγραφή από τη λίστα παρακολούθησης.</p>
                        <code id="api-url-4" class="code-block">/api/tracked.php?id=2</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-4">Αντιγραφή</button>
                        </div>
                    </article>
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-get">GET</span> Stats</h3>
                        <p>Επιστρέφει συγκεντρωτικά metrics για charts και reports.</p>
                        <code id="api-url-5" class="code-block">/api/stats.php</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-5">Αντιγραφή</button>
                        </div>
                    </article>
                    <article class="api-card">
                        <h3><span class="endpoint-badge endpoint-badge-get">GET</span> Lists</h3>
                        <p>Επιστρέφει τις λίστες με specialty metadata.</p>
                        <code id="api-url-6" class="code-block">/api/lists.php</code>
                        <div class="code-actions">
                            <button type="button" class="button-link secondary clipboard-button" data-copy-target="#api-url-6">Αντιγραφή</button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="section-card">
                <h2 class="section-title">Example JSON Response</h2>
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
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.clipboard-button');
        if (!button) return;

        var targetSelector = button.getAttribute('data-copy-target');
        var codeElement = targetSelector ? document.querySelector(targetSelector) : null;
        if (!codeElement) return;

        var text = codeElement.textContent.trim();
        navigator.clipboard.writeText(text).then(function () {
            var originalText = button.textContent;
            button.textContent = 'Αντιγράφηκε';
            setTimeout(function () {
                button.textContent = originalText;
            }, 1200);
        });
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
