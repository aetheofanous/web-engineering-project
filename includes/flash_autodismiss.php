<?php
// Tiny inline script that auto-dismisses success / info flash messages after a
// few seconds with a smooth fade-out. Errors are intentionally left visible so
// the user has time to read them.
//
// Included once by both app_topbar.php (most authenticated pages + auth flows)
// and header.php (api/api.php). Safe to include twice — the IIFE just registers
// another listener; the duplicate selectors won't double-dismiss because each
// element is only matched once and removed from the DOM after fade-out.
?>
<script>
(function () {
    var DELAY_MS = 4500;
    var FADE_MS  = 350;
    var SELECTOR = '.message.success, .message.info, .flash--success, .flash--info';

    function dismiss(el) {
        if (!el || !el.parentNode || el.dataset.flashDismissed === '1') return;
        el.dataset.flashDismissed = '1';

        var height = el.offsetHeight;
        el.style.maxHeight = height + 'px';
        el.style.overflow  = 'hidden';
        el.style.transition = [
            'opacity '       + FADE_MS + 'ms ease',
            'transform '     + FADE_MS + 'ms ease',
            'max-height '    + FADE_MS + 'ms ease ' + FADE_MS + 'ms',
            'margin '        + FADE_MS + 'ms ease ' + FADE_MS + 'ms',
            'padding '       + FADE_MS + 'ms ease ' + FADE_MS + 'ms',
            'border-width '  + FADE_MS + 'ms ease ' + FADE_MS + 'ms'
        ].join(', ');

        // Force reflow so the transition starts cleanly, then animate.
        // eslint-disable-next-line no-unused-expressions
        el.offsetHeight;
        requestAnimationFrame(function () {
            el.style.opacity      = '0';
            el.style.transform    = 'translateY(-6px)';
            el.style.maxHeight    = '0px';
            el.style.marginTop    = '0';
            el.style.marginBottom = '0';
            el.style.paddingTop   = '0';
            el.style.paddingBottom= '0';
            el.style.borderWidth  = '0';
        });

        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, FADE_MS * 2 + 80);
    }

    function init() {
        var nodes = document.querySelectorAll(SELECTOR);
        Array.prototype.forEach.call(nodes, function (el) {
            setTimeout(function () { dismiss(el); }, DELAY_MS);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
