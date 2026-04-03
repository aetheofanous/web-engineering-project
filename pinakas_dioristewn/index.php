<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Πίνακες Διοριστέων</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="page-banner">
        <p class="eyebrow">Ηλεκτρονική Υπηρεσία</p>
        <h1 class="auth-title">Σύστημα Παρακολούθησης Πινάκων Διοριστέων</h1>
        <p class="auth-subtitle">Πρότυπη διαδικτυακή εφαρμογή για πρόσβαση σε καταλόγους υποψηφίων, αυθεντικοποίηση χρηστών και αναζήτηση ανά ειδικότητα, έτος και στοιχεία υποψηφίου.</p>
      </div>

      <div class="page-body">
        <div class="info-grid">
          <section class="info-box">
            <h2>Υπηρεσίες Σύνδεσης</h2>
            <ul class="portal-links">
              <li><a href="auth/login.php">Είσοδος Χρήστη</a></li>
              <li><a href="auth/register.php">Εγγραφή Νέου Χρήστη</a></li>
              <li><a href="auth/logout.php">Αποσύνδεση</a></li>
            </ul>
          </section>

          <section class="info-box">
            <h2>Κεντρικές Λειτουργίες</h2>
            <ul class="portal-links">
              <li><a href="modules/dashboard.php">Πίνακας Ελέγχου</a></li>
              <li><a href="modules/list.php">Κατάλογοι Διοριστέων</a></li>
            </ul>
          </section>
        </div>

        <section>
          <h2 class="section-title">Γενικές Πληροφορίες</h2>
          <p class="section-text">Η παρούσα εφαρμογή αναπτύχθηκε για εκπαιδευτικούς σκοπούς και ακολουθεί τη λογική ενός απλού κυβερνητικού portal, με έμφαση στην ασφαλή σύνδεση χρηστών, στην προστασία των συνεδριών και στην αναζήτηση δεδομένων μέσω PDO Prepared Statements.</p>
        </section>
      </div>
    </div>
  </div>
</body>
</html>
