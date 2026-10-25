<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $announcement = new Announcement($db);

    if ($announcement->deleteAnnouncement($id)) {
        header("Location: view_announcements.php?deleted=1");
        exit;
    } else {
        header("Location: view_announcements.php?error=delete_failed");
        exit;
    }
}

header("Location: view_announcements.php");
exit;