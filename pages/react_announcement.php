<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->connect();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. If Officer requesting list of who acknowledged
if ($action === 'list') {
    if (($_SESSION['role'] ?? '') !== 'Officer') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden. Officers only.']);
        exit;
    }

    $announcementId = (int) ($_GET['announcement_id'] ?? $_POST['announcement_id'] ?? 0);
    if ($announcementId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid announcement ID.']);
        exit;
    }

    $query = "SELECT u.user_id, u.first_name, u.last_name, u.role, u.avatar_path, r.reaction_date
              FROM reactions r
              JOIN users u ON r.user_id = u.user_id
              WHERE r.announcement_id = :aid
              ORDER BY r.reaction_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([':aid' => $announcementId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count'   => count($users),
        'users'   => array_map(function($u) {
            return [
                'name'          => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
                'role'          => $u['role'] ?? '',
                'avatar_path'   => $u['avatar_path'] ?? '',
                'reaction_date' => !empty($u['reaction_date']) ? date('M d, Y \a\t g:i A', strtotime($u['reaction_date'])) : '',
            ];
        }, $users)
    ]);
    exit;
}

// 2. Toggle reaction via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$inputData = $_POST;
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $inputData = array_merge($inputData, $json);
    }
}

$announcementId = (int) ($inputData['announcement_id'] ?? 0);
if ($announcementId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid announcement ID.']);
    exit;
}

// Verify announcement exists
$checkAnn = $db->prepare("SELECT announcement_id FROM announcements WHERE announcement_id = :aid LIMIT 1");
$checkAnn->execute([':aid' => $announcementId]);
if (!$checkAnn->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Announcement not found.']);
    exit;
}

// Check existing reaction
$checkReact = $db->prepare("SELECT reaction_id FROM reactions WHERE announcement_id = :aid AND user_id = :uid LIMIT 1");
$checkReact->execute([':aid' => $announcementId, ':uid' => $userId]);
$existing = $checkReact->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Toggle off: delete reaction
    $delStmt = $db->prepare("DELETE FROM reactions WHERE reaction_id = :rid");
    $delStmt->execute([':rid' => (int) $existing['reaction_id']]);
    $acknowledged = false;
} else {
    // Toggle on: insert reaction
    $insStmt = $db->prepare("INSERT INTO reactions (reaction_date, user_id, announcement_id) VALUES (NOW(), :uid, :aid)");
    $insStmt->execute([':uid' => $userId, ':aid' => $announcementId]);
    $acknowledged = true;
}

// Get updated count
$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM reactions WHERE announcement_id = :aid");
$countStmt->execute([':aid' => $announcementId]);
$newCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

echo json_encode([
    'success'      => true,
    'acknowledged' => $acknowledged,
    'count'        => $newCount,
]);
exit;

