<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $eventModel = new Event($db);
    try {
        $eventModel->deleteWithNotifications((int) $id);
        header("Location: events.php?deleted=1");
        exit;
    } catch (Throwable $e) {
        error_log('Event deletion transaction failed: ' . $e->getMessage());
        header("Location: events.php?error=delete_failed");
        exit;
    }
}

header("Location: events.php");
exit;

