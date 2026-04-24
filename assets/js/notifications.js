// Notifications bell dropdown widget.
// Wires up:
//   - open/close toggle with outside-click + Escape,
//   - click on a notification card to mark it as read,
//   - click on the card's X button to delete it,
//   - "Delete all read" header button.
// All mutations go through /api/notifications.php and re-render from the
// payload the server returns, so the UI always matches the DB.
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    onReady(function () {
        var bell = document.getElementById('notifBell');
        if (!bell) {
            return;
        }

        var apiUrl = bell.getAttribute('data-api-url') || 'api/notifications.php';
        var btn = document.getElementById('notifBellBtn');
        var panel = document.getElementById('notifPanel');
        var badge = document.getElementById('notifBellBadge');
        var list = document.getElementById('notifPanelList');
        var deleteAllReadBtn = document.getElementById('notifDeleteAllRead');

        if (!btn || !panel || !list) {
            return;
        }

        function escapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function setOpen(open) {
            if (open) {
                bell.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                bell.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }
        }

        function isOpen() {
            return bell.classList.contains('is-open');
        }

        function renderBadge(count) {
            if (!badge) return;
            badge.textContent = count;
            if (count > 0) {
                badge.classList.remove('is-hidden');
            } else {
                badge.classList.add('is-hidden');
            }
        }

        function renderList(notifications) {
            if (!notifications || notifications.length === 0) {
                list.innerHTML = '<div class="notif-panel__empty">Δεν υπάρχουν ειδοποιήσεις ακόμη.</div>';
                return;
            }
            var html = '';
            for (var i = 0; i < notifications.length; i++) {
                var n = notifications[i];
                var unreadClass = n.is_read ? '' : ' is-unread';
                html += ''
                    + '<article class="notif-item' + unreadClass + '" data-id="' + n.id + '">'
                    +   '<button type="button" class="notif-item__close" aria-label="Διαγραφή">×</button>'
                    +   '<div class="notif-item__body">'
                    +     '<p class="notif-item__message">' + escapeHtml(n.message) + '</p>'
                    +     '<p class="notif-item__date">' + escapeHtml(n.created_at_display) + '</p>'
                    +   '</div>'
                    + '</article>';
            }
            list.innerHTML = html;
        }

        function applyPayload(payload) {
            if (!payload || !payload.ok) return;
            renderBadge(payload.unread_count || 0);
            renderList(payload.notifications || []);
        }

        function request(action, params) {
            var url = apiUrl + '?action=' + encodeURIComponent(action);
            var opts = { method: 'GET', credentials: 'same-origin' };
            if (params) {
                opts.method = 'POST';
                var formData = new FormData();
                Object.keys(params).forEach(function (k) {
                    formData.append(k, params[k]);
                });
                opts.body = formData;
            }
            return fetch(url, opts)
                .then(function (res) { return res.json(); })
                .then(function (data) { applyPayload(data); return data; })
                .catch(function () { /* swallow, keep UI responsive */ });
        }

        // Open / close handlers
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (isOpen()) {
                setOpen(false);
                return;
            }
            setOpen(true);
            // Refresh on open so badge + list are current even if another
            // tab created notifications in the meantime.
            request('list');
        });

        document.addEventListener('click', function (e) {
            if (!isOpen()) return;
            if (bell.contains(e.target)) return;
            setOpen(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                setOpen(false);
            }
        });

        // Delegated list actions: X deletes, clicking the card marks as read.
        list.addEventListener('click', function (e) {
            var target = e.target;
            var card = target.closest ? target.closest('.notif-item') : null;
            if (!card) return;
            var id = parseInt(card.getAttribute('data-id'), 10);
            if (!id) return;

            if (target.classList && target.classList.contains('notif-item__close')) {
                e.stopPropagation();
                request('delete', { id: id });
                return;
            }

            // Click on the card body -> mark as read (if currently unread)
            if (card.classList.contains('is-unread')) {
                request('mark_read', { id: id });
            }
        });

        // Delete all read
        if (deleteAllReadBtn) {
            deleteAllReadBtn.addEventListener('click', function () {
                request('delete_all_read', { _: '1' });
            });
        }
    });
})();
