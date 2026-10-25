<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $announcement = new Announcement($db);

    try {
        $announcement->deleteWithNotifications($id);
        header("Location: view_announcements.php?deleted=1");
        exit;
    } catch (Throwable $e) {
        error_log('Announcement deletion transaction failed: ' . $e->getMessage());
        header("Location: view_announcements.php?error=delete_failed");
        exit;
    }
}

header("Location: view_announcements.php");
exit;