<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Announcement.php';
require_once __DIR__ . '/../classes/Category.php';

$database = new Database();
$db = $database->connect();

$category = new Category($db);
$categories = $category->getAllCategories();

$announcement = new Announcement($db);

$id = $_GET['id'] ?? ($_POST['announcement_id'] ?? null);
if (!$id) {
    header("Location: view_announcements.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement->title       = $_POST['title'] ?? '';
    $announcement->details     = $_POST['details'] ?? '';
    $announcement->category_id = $_POST['category_id'] ?? null;
    $announcement->is_urgent   = isset($_POST['is_urgent']) ? 1 : 0;
    $announcement->user_id     = $_SESSION['user_id'];
    $announcement->status      = $_POST['status'] ?? 'Active';

    try {
        $announcement->updateWithNotifications($id, $_SESSION['user_id']);
        header("Location: view_announcements.php?updated=1");
        exit;
    } catch (Throwable $e) {
        error_log('Announcement update transaction failed: ' . $e->getMessage());
        $message = "Failed to update the announcement and notify users. No changes were saved.";
    }
}

$record = $announcement->getAnnouncementById($id);
if (!$record) {
    header("Location: view_announcements.php");
    exit;
}

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 640px;">
    <h3 class="mb-3">Edit Post</h3>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm post-card">
        <div class="card-body">
            <form method="POST" action="update_announcement.php?id=<?php echo $record['announcement_id']; ?>">
                <input type="hidden" name="announcement_id" value="<?php echo $record['announcement_id']; ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="45"
                           value="<?php echo htmlspecialchars($record['title']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Details</label>
                    <textarea name="details" class="form-control" rows="4" required><?php echo htmlspecialchars($record['details']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($cat['category_id'] == $record['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Active', 'Archived'] as $st): ?>
                                <option value="<?php echo $st; ?>"
                                    <?php echo ($record['status'] === $st) ? 'selected' : ''; ?>>
                                    <?php echo $st; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_urgent" id="is_urgent"
                        <?php echo $record['is_urgent'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_urgent">Mark as urgent</label>
                </div>

                <hr class="my-3">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Save Changes</button>
                    <a href="view_announcements.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>