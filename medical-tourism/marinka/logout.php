<?php
require_once __DIR__ . '/../includes/init.php';
$_SESSION = [];
session_destroy();
redirect(BASE_URL . '/marinka/login.php');
