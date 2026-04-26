<?php
// Search dashboard removed: this hub used to host the same tiles as the topbar.
// Kept as a permanent redirect so any bookmarks or stale links still work.
require_once __DIR__ . '/../../includes/bootstrap.php';

redirect_to('modules/search/search.php');
