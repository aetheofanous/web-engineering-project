<?php
// Module-level navigation bar.
//
// Each page that requires this file sets two variables BEFORE the require:
//   $moduleKey  - one of: admin, candidate, search, api
//   $pageKey    - the active link key, matching one of the entries in
//                 config.php > module_links[<moduleKey>][*]['key']
//
// The PDF (point #4) requires every module to expose a menu with the same
// links as its dashboard. We read that list from config.php so there is a
// single source of truth, and we fall back to nothing if the key is unknown.
//
// UX decision (admin & candidate): the dashboard already exposes every action
// as a tile inside "Ενέργειες Διαχείρισης". Showing the same actions in the
// top nav too would just duplicate the buttons, so we keep the top nav minimal
// (Dashboard + My Profile only). Search/API modules don't have such a tile
// section, so their nav remains the full list from config.php.

if (!function_exists('module_links')) {
    require_once __DIR__ . '/functions.php';
}

$navModuleKey = $moduleKey ?? null;
$navPageKey = $pageKey ?? null;
$navLinks = $navModuleKey !== null ? module_links($navModuleKey) : [];

// Per-module whitelist of link keys that should appear in the top nav. A
// module key not present here means "show every link from config.php" (used
// by search and api).
$navWhitelist = [
    'admin'     => ['dashboard', 'profile'],
    'candidate' => ['dashboard', 'profile'],
];

if (isset($navWhitelist[$navModuleKey])) {
    $allowedKeys = $navWhitelist[$navModuleKey];
    $navLinks = array_values(array_filter(
        $navLinks,
        function ($link) use ($allowedKeys) {
            return in_array($link['key'] ?? '', $allowedKeys, true);
        }
    ));
}

if ($navLinks === []) {
    return;
}
?>
<nav class="module-nav" aria-label="Module navigation">
    <div class="module-nav__inner">
        <?php foreach ($navLinks as $navLink): ?>
            <?php $isActive = ($navPageKey !== null) && ($navLink['key'] ?? '') === $navPageKey; ?>
            <a class="module-nav__link<?php echo $isActive ? ' is-active' : ''; ?>"
               href="<?php echo h(base_url($navLink['path'] ?? '')); ?>"
               <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                <?php echo h($navLink['label'] ?? ''); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
