<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Announcement.php';
require_once __DIR__ . '/classes/Event.php';

echo "=== STARTING OWNERSHIP TESTS ===\n\n";

$database = new Database();
$db = $database->connect();

// Fetch two distinct users for testing
$uStmt = $db->query("SELECT user_id, email, role FROM users LIMIT 2");
$users = $uStmt->fetchAll();
if (count($users) < 2) {
    // Create a temporary second user for test
    $ins = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES ('Officer2', 'Test', 'officer2@test.local', 'hash', 'Officer')");
    $ins->execute();
    $secondId = (int)$db->lastInsertId();
    $uStmt = $db->query("SELECT user_id, email, role FROM users LIMIT 2");
    $users = $uStmt->fetchAll();
}

$userAId = (int)$users[0]['user_id'];
$userBId = (int)$users[1]['user_id'];

echo "User A ID: $userAId\n";
echo "User B ID: $userBId\n";

$cStmt = $db->query("SELECT category_id FROM categories LIMIT 1");
$catId = (int)$cStmt->fetchColumn();

// --- TEST 1: Officer A creates an announcement ---
echo "\n--- TEST 1: Officer A creates announcement ---\n";
$annModel = new Announcement($db);
$annModel->title = "Officer A Original Post";
$annModel->details = "Details by Officer A";
$annModel->category_id = $catId;
$annModel->status = "Active";
$annModel->is_urgent = 0;
$annId = $annModel->createWithNotifications($userAId);
echo "Created announcement ID: $annId by User A ($userAId)\n";

// --- TEST 2: Officer B tries to update Officer A's announcement ---
echo "\n--- TEST 2: Officer B attempts to update Officer A's announcement ---\n";
$caughtUpdateB = false;
$annModel->title = "Hacked by Officer B";
$annModel->details = "Details modified by B";
try {
    $annModel->updateWithNotifications($annId, $userBId);
} catch (Throwable $e) {
    $caughtUpdateB = true;
    echo "Caught expected rejection: " . $e->getMessage() . "\n";
    assert($e->getMessage() === 'You can only edit or delete your own posts.');
}
assert($caughtUpdateB === true, "Officer B update should have been rejected!");

$checkAnn = $annModel->getAnnouncementById($annId);
assert($checkAnn['title'] === "Officer A Original Post", "Title should NOT have been changed by Officer B");
echo "PASS: Officer B was rejected and title remained unchanged.\n";

// --- TEST 3: Officer B tries to delete Officer A's announcement ---
echo "\n--- TEST 3: Officer B attempts to delete Officer A's announcement ---\n";
$caughtDeleteB = false;
try {
    $annModel->deleteWithNotifications($annId, $userBId);
} catch (Throwable $e) {
    $caughtDeleteB = true;
    echo "Caught expected rejection: " . $e->getMessage() . "\n";
    assert($e->getMessage() === 'You can only edit or delete your own posts.');
}
assert($caughtDeleteB === true, "Officer B deletion should have been rejected!");

$checkAnnStillExists = $annModel->getAnnouncementById($annId);
assert(!empty($checkAnnStillExists), "Announcement should still exist in database");
echo "PASS: Officer B was rejected and announcement was NOT deleted.\n";

// --- TEST 4: Officer A updates their own announcement ---
echo "\n--- TEST 4: Officer A updates own announcement ---\n";
$annModel->title = "Officer A Legitimate Update";
$annModel->details = "Updated details by A";
$annModel->category_id = $catId;
$annModel->status = "Active";
$annModel->is_urgent = 0;
$annModel->updateWithNotifications($annId, $userAId);

$checkAnnUpdated = $annModel->getAnnouncementById($annId);
assert($checkAnnUpdated['title'] === "Officer A Legitimate Update", "Title should be updated by author");
echo "PASS: Officer A successfully updated own announcement.\n";

// --- TEST 5: Officer A deletes their own announcement ---
echo "\n--- TEST 5: Officer A deletes own announcement ---\n";
$annModel->deleteWithNotifications($annId, $userAId);
$checkAnnDeleted = $annModel->getAnnouncementById($annId);
assert(empty($checkAnnDeleted), "Announcement should be deleted by author");
echo "PASS: Officer A successfully deleted own announcement.\n";


// --- TEST 6: Event ownership tests ---
echo "\n--- TEST 6: Event ownership enforcement ---\n";
$eventModel = new Event($db);
$eventModel->title = "Event by Officer A";
$eventModel->description = "Description by A";
$eventModel->event_date = date('Y-m-d H:i:s', strtotime('+2 days'));
$eventModel->location = "Community Hall";
$evId = $eventModel->createWithNotifications($userAId);
echo "Created event ID: $evId by User A ($userAId)\n";

// Officer B tries to update event
$caughtEvUpdateB = false;
$eventModel->title = "Event Hacked by B";
try {
    $eventModel->updateEvent($evId, $userBId);
} catch (Throwable $e) {
    $caughtEvUpdateB = true;
    echo "Caught expected event update rejection: " . $e->getMessage() . "\n";
    assert($e->getMessage() === 'You can only edit or delete your own posts.');
}
assert($caughtEvUpdateB === true, "Officer B event update should have been rejected");

// Officer B tries to delete event
$caughtEvDeleteB = false;
try {
    $eventModel->deleteWithNotifications($evId, $userBId);
} catch (Throwable $e) {
    $caughtEvDeleteB = true;
    echo "Caught expected event delete rejection: " . $e->getMessage() . "\n";
    assert($e->getMessage() === 'You can only edit or delete your own posts.');
}
assert($caughtEvDeleteB === true, "Officer B event delete should have been rejected");

// Officer A deletes own event
$eventModel->deleteWithNotifications($evId, $userAId);
$checkEv = $eventModel->getEventById($evId);
assert(empty($checkEv), "Event should be deleted by author");
echo "PASS: Event ownership fully enforced for create, update, and delete.\n";

echo "\n=== ALL OWNERSHIP TESTS PASSED SUCCESSFULLY! ===\n";

