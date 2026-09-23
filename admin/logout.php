<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
require_once __DIR__ . '/../includes/config.php';
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
