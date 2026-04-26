<?php
// Legacy alias kept as a permanent redirect.
//
// This page used to host an early version of the candidate listing UI before
// the dedicated Search module (modules/search/search.php) was introduced.
// Nothing in the project links here any more, but we keep the redirect so
// any stale bookmark or external link still lands on a working page.

require_once __DIR__ . '/../includes/bootstrap.php';
redirect_to('modules/search/search.php');
