<?php
// Bootstrap file included at the top of every public-facing page.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

detect_suspicious_request($pdo);
track_pageview($pdo);
