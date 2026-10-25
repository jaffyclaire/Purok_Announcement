<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Announcement.php';
require_once __DIR__ . '/classes/ImageUploader.php';

echo "=== STARTING MULTI-IMAGE TESTS ===\n\n";

$database = new Database();
$db = $database->connect();

// Fetch a valid user_id and category_id for testing
$uStmt = $db->query("SELECT user_id FROM users LIMIT 1");
$testUserId = (int)$uStmt->fetchColumn();
if (!$testUserId) {
    die("ERROR: No users found in database for testing.\n");
}

$cStmt = $db->query("SELECT category_id FROM categories LIMIT 1");
$testCatId = (int)$cStmt->fetchColumn();
if (!$testCatId) {
    die("ERROR: No categories found in database for testing.\n");
}

echo "Using test user_id: $testUserId, category_id: $testCatId\n";

// Helper to generate a valid test PNG file
function createTestJpeg($name) {
    $path = __DIR__ . '/uploads/' . $name;
    // Valid 1x1 transparent PNG binary
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    file_put_contents($path, base64_decode($pngBase64));
    return $path;
}

// -------------------------------------------------------------
// TEST 1: Single image upload (1 image)
// -------------------------------------------------------------
echo "\n--- TEST 1: Uploading 1 image ---\n";
$file1 = createTestJpeg('test_single.jpg');
$files1 = [
    'name'     => ['test_single.png'],
    'type'     => ['image/png'],
    'tmp_name' => [$file1],
    'error'    => [UPLOAD_ERR_OK],
    'size'     => [filesize($file1)],
];

$ann1 = new Announcement($db);
$ann1->title = "Test Multi-Image 1 Single";
$ann1->details = "Testing single image upload.";
$ann1->category_id = $testCatId;
$ann1->status = "Active";
$ann1->is_urgent = 0;

$createdId1 = $ann1->createWithNotifications($testUserId, $files1);
echo "Created announcement ID: $createdId1\n";

$atts1 = $ann1->getAttachmentsByAnnouncementId($createdId1);
assert(count($atts1) === 1, "Expected 1 attachment, got " . count($atts1));
$diskFile1 = __DIR__ . '/' . $atts1[0]['file_path'];
assert(file_exists($diskFile1), "Attachment file does not exist on disk: $diskFile1");
echo "PASS: 1 image created and verified on disk: " . $atts1[0]['file_path'] . "\n";


// -------------------------------------------------------------
// TEST 2: Multi image upload (3 images)
// -------------------------------------------------------------
echo "\n--- TEST 2: Uploading 3 images ---\n";
$f2a = createTestJpeg('test_multi_a.jpg');
$f2b = createTestJpeg('test_multi_b.jpg');
$f2c = createTestJpeg('test_multi_c.jpg');

$files3 = [
    'name'     => ['test_a.png', 'test_b.png', 'test_c.png'],
    'type'     => ['image/png', 'image/png', 'image/png'],
    'tmp_name' => [$f2a, $f2b, $f2c],
    'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
    'size'     => [filesize($f2a), filesize($f2b), filesize($f2c)],
];

$ann2 = new Announcement($db);
$ann2->title = "Test Multi-Image 3 Images";
$ann2->details = "Testing 3 images upload.";
$ann2->category_id = $testCatId;
$ann2->status = "Active";
$ann2->is_urgent = 0;

$createdId2 = $ann2->createWithNotifications($testUserId, $files3);
echo "Created announcement ID: $createdId2\n";

$atts2 = $ann2->getAttachmentsByAnnouncementId($createdId2);
assert(count($atts2) === 3, "Expected 3 attachments, got " . count($atts2));
foreach ($atts2 as $a) {
    assert(file_exists(__DIR__ . '/' . $a['file_path']), "File does not exist: " . $a['file_path']);
}
echo "PASS: 3 images created and verified on disk.\n";


// -------------------------------------------------------------
// TEST 3: Uploading 6 images (MUST FAIL and save 0 rows)
// -------------------------------------------------------------
echo "\n--- TEST 3: Uploading 6 images (Must fail) ---\n";
$sixPaths = [];
$sixNames = [];
$sixTypes = [];
$sixErrors = [];
$sixSizes = [];

for ($i = 1; $i <= 6; $i++) {
    $p = createTestJpeg("test_six_$i.png");
    $sixPaths[] = $p;
    $sixNames[] = "six_$i.png";
    $sixTypes[] = 'image/png';
    $sixErrors[] = UPLOAD_ERR_OK;
    $sixSizes[] = filesize($p);
}

$files6 = [
    'name'     => $sixNames,
    'type'     => $sixTypes,
    'tmp_name' => $sixPaths,
    'error'    => $sixErrors,
    'size'     => $sixSizes,
];

$ann3 = new Announcement($db);
$ann3->title = "Test 6 Images (Should Fail)";
$ann3->details = "Should be rejected.";
$ann3->category_id = $testCatId;

$caught = false;
try {
    $ann3->createWithNotifications($testUserId, $files6);
} catch (Throwable $e) {
    $caught = true;
    echo "Caught expected exception: " . $e->getMessage() . "\n";
}

assert($caught === true, "Expected 6 images upload to fail, but it succeeded!");

// Clean up temporary dummy files
foreach ($sixPaths as $sp) {
    if (file_exists($sp)) @unlink($sp);
}
echo "PASS: 6 images upload was rejected with clear error.\n";


// -------------------------------------------------------------
// TEST 4: Edit announcement: remove 1 image and add 1 new image
// -------------------------------------------------------------
echo "\n--- TEST 4: Edit announcement (remove 1, add 1 new) ---\n";
$existingBefore = $ann2->getAttachmentsByAnnouncementId($createdId2);
$removeId = (int)$existingBefore[0]['attachment_id'];
$removePathOnDisk = __DIR__ . '/' . $existingBefore[0]['file_path'];
assert(file_exists($removePathOnDisk), "Old file to remove should exist before edit");

$fNew = createTestJpeg('test_edit_new.png');
$newFiles = [
    'name'     => ['test_edit_new.png'],
    'type'     => ['image/png'],
    'tmp_name' => [$fNew],
    'error'    => [UPLOAD_ERR_OK],
    'size'     => [filesize($fNew)],
];

$ann2->title = "Updated Announcement Title";
$ann2->details = "Updated details with 1 removed and 1 added.";
$ann2->status = "Active";
$ann2->is_urgent = 0;
$ann2->category_id = $testCatId;

$ann2->updateWithNotifications($createdId2, $testUserId, $newFiles, [$removeId]);

$existingAfter = $ann2->getAttachmentsByAnnouncementId($createdId2);
assert(count($existingAfter) === 3, "Expected 3 attachments after (3 - 1 + 1), got " . count($existingAfter));
assert(!file_exists($removePathOnDisk), "Removed attachment file should be deleted from disk: $removePathOnDisk");

$newAttachmentFound = false;
foreach ($existingAfter as $ea) {
    assert((int)$ea['attachment_id'] !== $removeId, "Removed attachment ID should not be present in DB");
    assert(file_exists(__DIR__ . '/' . $ea['file_path']), "Existing file must exist: " . $ea['file_path']);
    if (strpos($ea['file_path'], 'uploads/announcements/') === 0) {
        $newAttachmentFound = true;
    }
}
assert($newAttachmentFound, "New attachment file should be present");
echo "PASS: Edit removed old file from disk/DB and added new file.\n";


// -------------------------------------------------------------
// TEST 5: Forced rollback test (zero orphan files)
// -------------------------------------------------------------
echo "\n--- TEST 5: Forced rollback (zero orphan files) ---\n";
// Record directory contents before
$uploadDir = __DIR__ . '/uploads/announcements';
$filesBefore = is_dir($uploadDir) ? scandir($uploadDir) : [];

// Create a subclass that throws inside the transaction after moving files or before commit
class FailingAnnouncement extends Announcement {
    public function insertNotificationsForOtherUsers($announcementId, $authorId, $message) {
        throw new RuntimeException("Simulated mid-transaction failure for rollback testing!");
    }
}

$failAnn = new FailingAnnouncement($db);
$failAnn->title = "Rollback Test";
$failAnn->details = "Details for rollback test.";
$failAnn->category_id = $testCatId;
$failAnn->status = "Active";
$failAnn->is_urgent = 0;

$fRoll1 = createTestJpeg('test_rb_1.png');
$fRoll2 = createTestJpeg('test_rb_2.png');
$rollbackFiles = [
    'name'     => ['rb_1.png', 'rb_2.png'],
    'type'     => ['image/png', 'image/png'],
    'tmp_name' => [$fRoll1, $fRoll2],
    'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
    'size'     => [filesize($fRoll1), filesize($fRoll2)],
];

$rbCaught = false;
try {
    $failAnn->createWithNotifications($testUserId, $rollbackFiles);
} catch (Throwable $e) {
    $rbCaught = true;
    echo "Caught expected rollback simulation: " . $e->getMessage() . "\n";
}
assert($rbCaught === true, "Forced failure should have been caught");

// Clean up temporary dummy source files if still present
if (file_exists($fRoll1)) @unlink($fRoll1);
if (file_exists($fRoll2)) @unlink($fRoll2);

$filesAfter = is_dir($uploadDir) ? scandir($uploadDir) : [];
$diff = array_diff($filesAfter, $filesBefore);

echo "Orphan files in $uploadDir after rollback: " . count($diff) . "\n";
assert(count($diff) === 0, "No orphan files should remain in uploads/announcements/ after rollback! Found: " . implode(', ', $diff));
echo "PASS: Zero orphan files left in uploads/announcements/ after rollback.\n";


// -------------------------------------------------------------
// TEST 6: Delete announcement removes all remaining attachments
// -------------------------------------------------------------
echo "\n--- TEST 6: Delete announcement cleans all attachments ---\n";
$attsToDelete = $ann2->getAttachmentsByAnnouncementId($createdId2);
$ann2->deleteWithNotifications($createdId2);

foreach ($attsToDelete as $delAtt) {
    assert(!file_exists(__DIR__ . '/' . $delAtt['file_path']), "Deleted announcement's file should be removed: " . $delAtt['file_path']);
}
$ann1->deleteWithNotifications($createdId1);
assert(!file_exists($diskFile1), "Single image announcement file should be removed on delete");
echo "PASS: Deletion cleanly unlinked all image files from disk.\n";

echo "\n=== ALL TESTS PASSED SUCCESSFULLY! ===\n";
