<?php
// Candidate dashboard with access to profile, own applications and tracked people.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);

$countsStatement = pdo()->prepare(
    'SELECT
        (SELECT COUNT(*) FROM applications WHERE user_id = :user_id) AS linked_applications,
        (SELECT COUNT(*) FROM tracked_candidates WHERE user_id = :user_id) AS tracked_people,
        (SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0) AS unread_notifications'
);
$countsStatement->execute(['user_id' => $user['id']]);
$counts = $countsStatement->fetch() ?: [];

$notifications = fetch_user_notifications($user['id']);

$pageTitle = 'Candidate Dashboard';
$pageSubtitle = 'Επισκόπηση προφίλ, προσωπικών αιτήσεων και παρακολούθησης άλλων υποψηφίων.';
$moduleKey = 'candidate';
$pageKey = 'dashboard';
$heroStats = [
    ['value' => $counts['linked_applications'] ?? 0, 'label' => 'My Applications'],
    ['value' => $counts['tracked_people'] ?? 0, 'label' => 'Tracked Others'],
    ['value' => $counts['unread_notifications'] ?? 0, 'label' => 'Unread Notifications'],
];

require __DIR__ . '/../../includes/header.php';
?>

<section class="module-grid">
    <article class="module-card module-card--candidate">
        <span class="module-card__icon">MP</span>
        <h3>My Profile</h3>
        <p>Προβολή και επεξεργασία βασικών στοιχείων, αλλαγή κωδικού και προτιμήσεων ειδοποιήσεων.</p>
        <a class="button" href="<?php echo e(base_url('modules/candidate/profile.php')); ?>">Open Profile</a>
    </article>

    <article class="module-card module-card--candidate">
        <span class="module-card__icon">TA</span>
        <h3>Track My Applications</h3>
        <p>Σύνδεση λογαριασμού με υποψήφιο και timeline παρουσίαση της κατάστασης κάθε αίτησης.</p>
        <a class="button" href="<?php echo e(base_url('modules/candidate/my-applications.php')); ?>">Open Applications</a>
    </article>

    <article class="module-card module-card--candidate">
        <span class="module-card__icon">TO</span>
        <h3>Track Others</h3>
        <p>Παρακολούθηση άλλων υποψηφίων στους πίνακες διοριστέων με εύκολη προσθήκη και αφαίρεση.</p>
        <a class="button" href="<?php echo e(base_url('modules/candidate/track-others.php')); ?>">Open Tracking</a>
    </article>
    <article class="module-card module-card--search">
        <span class="module-card__icon">SR</span>
        <h3>Public Search</h3>
        <p>Άμεση μετάβαση στη δημόσια αναζήτηση για διασταύρωση λιστών και ειδικοτήτων.</p>
        <a class="button button--warm" href="<?php echo e(base_url('modules/search/search.php')); ?>">Open Search</a>
    </article>
</section>

<section class="section-heading">
    <div>
        <h2>Latest Notifications</h2>
        <p>Οι ειδοποιήσεις του candidate module αποθηκεύονται στη βάση και εμφανίζονται συγκεντρωτικά εδώ.</p>
    </div>
</section>

<section class="timeline">
    <?php if ($notifications === []): ?>
        <div class="empty-state">Δεν υπάρχουν ειδοποιήσεις ακόμη για τον τρέχοντα χρήστη.</div>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <article class="timeline-card">
                <span class="badge <?php echo (int) $notification['is_read'] === 1 ? 'badge--linked' : 'badge--success'; ?>">
                    <?php echo (int) $notification['is_read'] === 1 ? 'Read' : 'Unread'; ?>
                </span>
                <h3><?php echo e(date('d/m/Y H:i', strtotime($notification['created_at']))); ?></h3>
                <p><?php echo e($notification['message']); ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
