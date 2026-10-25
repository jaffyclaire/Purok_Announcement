<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $eventModel = new Event($db);
    $record = $eventModel->getEventById((int) $id);
    if (!$record || empty($record['created_by']) || (int)$record['created_by'] !== (int)$_SESSION['user_id']) {
        http_response_code(403);
        header("Location: events.php?error=forbidden");
        exit;
    }

    try {
        $eventModel->deleteWithNotifications((int) $id, $_SESSION['user_id']);
        header("Location: events.php?deleted=1");
        exit;
    } catch (Throwable $e) {
        if ($e->getMessage() === 'You can only edit or delete your own posts.') {
            http_response_code(403);
            header("Location: events.php?error=forbidden");
            exit;
        }
        error_log('Event deletion transaction failed: ' . $e->getMessage());
        header("Location: events.php?error=delete_failed");
        exit;
    }
}

header("Location: events.php");
exit;

