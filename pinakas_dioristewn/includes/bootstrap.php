<?php
// Bootstrap file loaded by every page so configuration and helpers stay consistent.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

ensure_session_started();
