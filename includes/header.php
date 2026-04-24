<?php
// Shared page header. Each page sets title/module variables before including this file.

require_once __DIR__ . '/bootstrap.php';

$pageTitle = $pageTitle ?? app_config('name');
$pageSubtitle = $pageSubtitle ?? app_config('tagline');
$moduleKey = $moduleKey ?? null;
$pageKey = $pageKey ?? null;
$showHero = $showHero ?? true;
$showTopbar = $showTopbar ?? true;
$showModuleNav = $showModuleNav ?? ($moduleKey !== null);
$heroStats = $heroStats ?? [];
$bodyClass = $bodyClass ?? '';
$user = current_user();
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo e(app_config('name')); ?></title>
    <link rel="stylesheet" href="<?php echo e(base_url('assets/css/style.css')); ?>">
</head>
<body class="<?php echo e($bodyClass); ?>">
    <?php if ($showTopbar): ?>
    <header class="site-topbar">
        <div class="site-topbar__inner">
            <a class="brand-lockup" href="<?php echo e(base_url()); ?>">
                <span class="brand-lockup__mark">ΕΕΥ</span>
                <span>
                    <strong><?php echo e(app_config('name_el')); ?></strong>
                    <span class="brand-lockup__sub"><?php echo e(app_config('description')); ?></span>
                </span>
            </a>

            <div class="site-topbar__actions">
                <a class="top-link" href="<?php echo e(base_url('modules/search/dashboard.php')); ?>">Search</a>
                <a class="top-link" href="<?php echo e(base_url('api/api.php')); ?>">API</a>
                <?php if ($user !== null): ?>
                    <span class="user-chip"><?php echo e($user['name'] . ' ' . $user['surname']); ?> | <?php echo e(strtoupper($user['role'])); ?></span>
                    <a class="button button--ghost" href="<?php echo e(base_url(role_dashboard_path($user['role']))); ?>">My Module</a>
                    <a class="button button--ghost" href="<?php echo e(base_url('auth/logout.php')); ?>">Logout</a>
                <?php else: ?>
                    <a class="button button--ghost" href="<?php echo e(base_url('auth/login.php')); ?>">Login</a>
                    <a class="button button--ghost" href="<?php echo e(base_url('auth/register.php')); ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <?php if ($showModuleNav): ?>
        <?php require __DIR__ . '/nav.php'; ?>
    <?php endif; ?>

    <main class="page-shell">
        <?php if ($showHero): ?>
            <section class="page-hero">
                <div class="page-hero__copy">
                    <?php if ($moduleKey !== null): ?>
                        <span class="eyebrow"><?php echo e(module_label($moduleKey)); ?></span>
                    <?php endif; ?>
                    <h1><?php echo e($pageTitle); ?></h1>
                    <p><?php echo e($pageSubtitle); ?></p>
                </div>

                <?php if ($heroStats !== []): ?>
                    <div class="hero-stats">
                        <?php foreach ($heroStats as $heroStat): ?>
                            <article class="hero-stat">
                                <span class="hero-stat__value"><?php echo e($heroStat['value']); ?></span>
                                <span class="hero-stat__label"><?php echo e($heroStat['label']); ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php foreach ($flashes as $flash): ?>
            <div class="flash flash--<?php echo e($flash['type']); ?>">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endforeach; ?>
