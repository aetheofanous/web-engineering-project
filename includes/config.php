<?php
// Central application configuration used by pages, navigation and README references.

$appConfig = [
    'name' => 'Appointable Lists',
    'name_el' => 'Παρακολούθηση Πινάκων Διοριστέων',
    'tagline' => 'Ενιαία πλατφόρμα αναζήτησης, παρακολούθησης και διαχείρισης διοριστέων εκπαιδευτικών.',
    'description' => 'Student-built PHP/PDO application inspired by the public structure of the Educational Service Commission portal.',
    'inspiration' => 'https://www.gov.cy/eey/',
    'security_rules' => [
        'ΠΑΝΤΑ Prepared Statements - ποτέ string concatenation σε SQL',
        'ΠΑΝΤΑ password_hash() - ποτέ plain-text password στη βάση',
        'ΠΑΝΤΑ htmlspecialchars() σε κάθε echo user data',
        'ΠΑΝΤΑ exit μετά από κάθε header() redirect',
        'ΠΟΤΕ die($e->getMessage()) - εκθέτει credentials βάσης',
    ],
    'module_links' => [
        'admin' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'modules/admin/dashboard.php'],
            ['key' => 'manage_users', 'label' => 'Manage Users', 'path' => 'modules/admin/manage_users.php'],
            ['key' => 'manage_lists', 'label' => 'Manage Lists', 'path' => 'modules/admin/manage_lists.php'],
            ['key' => 'reports', 'label' => 'Reports', 'path' => 'modules/admin/reports.php'],
            ['key' => 'profile', 'label' => 'My Profile', 'path' => 'modules/admin/profile.php'],
        ],
        'candidate' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'modules/candidate/dashboard.php'],
            ['key' => 'profile', 'label' => 'My Profile', 'path' => 'modules/candidate/profile.php'],
            ['key' => 'my-applications', 'label' => 'Track My Applications', 'path' => 'modules/candidate/my-applications.php'],
            ['key' => 'track-others', 'label' => 'Track Others', 'path' => 'modules/candidate/track-others.php'],
        ],
        'search' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'modules/search/dashboard.php'],
            ['key' => 'search', 'label' => 'Search', 'path' => 'modules/search/search.php'],
            ['key' => 'register', 'label' => 'Register', 'path' => 'auth/register.php'],
            ['key' => 'statistics', 'label' => 'Statistics', 'path' => 'modules/search/statistics.php'],
        ],
        'api' => [
            ['key' => 'dashboard', 'label' => 'API Home', 'path' => 'api/api.php'],
            ['key' => 'candidates', 'label' => 'Candidates', 'path' => 'api/candidates.php'],
            ['key' => 'lists', 'label' => 'Lists', 'path' => 'api/lists.php'],
            ['key' => 'tracked', 'label' => 'Tracked', 'path' => 'api/tracked.php'],
            ['key' => 'stats', 'label' => 'Stats', 'path' => 'api/stats.php'],
        ],
    ],
];
