<?php
// Shared footer with quick reminders for presentation.
?>
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <div>
                <strong><?php echo e(app_config('name')); ?></strong>
                <p><?php echo e(app_config('tagline')); ?></p>
            </div>

            <div>
                <strong>Presentation Checklist</strong>
                <p>Landing page, role-based modules, search, candidate tracking, statistics and JSON API responses are included.</p>
            </div>

            <div>
                <strong>Design Inspiration</strong>
                <p>
                    Inspired by
                    <a href="<?php echo e(app_config('inspiration')); ?>" target="_blank" rel="noreferrer">gov.cy/eey</a>
                    and adapted into a student project with a custom layout.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
