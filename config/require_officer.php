<?php
require_once __DIR__ . '/require_login.php';

if ($_SESSION['role'] !== 'Officer') {
    header("Location: view_announcements.php?error=unauthorized");
    exit;
}