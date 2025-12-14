<?php
// File: pages/auth/logout.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/auth.php';

// Destroy Session
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();

// Redirect to Global Login
header("Location: " . BASE_URL . "/index.php");
exit;
?>