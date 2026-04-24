<?php
// Shared module navigation. Each module receives the same dashboard links on every page.

$links = $moduleKey !== null ? module_links($moduleKey) : [];
?>
<?php if ($links !== []): ?>
    <nav class="module-nav">
        <div class="module-nav__inner">
            <?php foreach ($links as $link): ?>
                <a class="module-nav__link<?php echo $pageKey === $link['key'] ? ' is-active' : ''; ?>" href="<?php echo e(base_url($link['path'])); ?>">
                    <?php echo e($link['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
<?php endif; ?>
