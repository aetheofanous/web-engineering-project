<?php
// Shared official-style topbar for pages that use the custom auth-card layout.

if (!function_exists('current_user')) {
    require_once __DIR__ . '/bootstrap.php';
}

$topbarUser = current_user();
?>
<header class="site-topbar">
    <div class="site-topbar__inner">
        <a class="brand-lockup" href="<?php echo e(base_url('index.php')); ?>">
            <span class="brand-lockup__mark">ΕΕΥ</span>
            <span>
                <strong><?php echo e(app_config('name_el')); ?></strong>
                <span class="brand-lockup__sub"><?php echo e(app_config('description')); ?></span>
            </span>
        </a>

        <div class="site-topbar__actions">
            <a class="top-link" href="<?php echo e(base_url('modules/search/dashboard.php')); ?>">Search</a>
            <a class="top-link" href="<?php echo e(base_url('api/api.php')); ?>">API</a>
            <?php if ($topbarUser !== null): ?>
                <span class="user-chip">
                    <?php echo e(trim(($topbarUser['name'] ?? '') . ' ' . ($topbarUser['surname'] ?? ''))); ?>
                    | <?php echo e(strtoupper($topbarUser['role'] ?? '')); ?>
                </span>
                <a class="button button--ghost" href="<?php echo e(base_url(role_dashboard_path($topbarUser['role'] ?? ''))); ?>">My Module</a>
                <a class="button button--ghost" href="<?php echo e(base_url('auth/logout.php')); ?>">Logout</a>
            <?php else: ?>
                <a class="button button--ghost" href="<?php echo e(base_url('auth/login.php')); ?>">Login</a>
                <a class="button button--ghost" href="<?php echo e(base_url('auth/register.php')); ?>">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
