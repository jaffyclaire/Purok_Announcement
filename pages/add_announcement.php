<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Announcement.php';
require_once __DIR__ . '/../classes/Category.php';

$database = new Database();
$db = $database->connect();

$category = new Category($db);
$categories = $category->getAllCategories();

$message = "";
$messageType = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement = new Announcement($db);

    $announcement->title       = $_POST['title'] ?? '';
    $announcement->details     = $_POST['details'] ?? '';
    $announcement->category_id = $_POST['category_id'] ?? null;
    $announcement->is_urgent   = isset($_POST['is_urgent']) ? 1 : 0;
    $announcement->user_id     = $_SESSION['user_id'];
    $announcement->status      = 'Active';

    if (empty($announcement->title) || empty($announcement->details)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } else {
        try {
            $announcement->createWithNotifications($_SESSION['user_id']);
            header("Location: view_announcements.php?added=1");
            exit;
        } catch (Throwable $e) {
            error_log('Announcement creation transaction failed: ' . $e->getMessage());
            $message = "Failed to save the announcement and notify users. No changes were saved.";
            $messageType = "danger";
        }
    }
}

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 640px;">
    <h3 class="mb-3">Create a Post</h3>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm post-card">
        <div class="card-body">
            <form method="POST" action="add_announcement.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="45"
                           placeholder="What's the announcement about?">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Details</label>
                    <textarea name="details" class="form-control" rows="4" required
                              placeholder="Write the full announcement here..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_urgent" id="is_urgent">
                    <label class="form-check-label" for="is_urgent">Mark as urgent</label>
                </div>

                <hr class="my-3">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Post Announcement</button>
                    <a href="view_announcements.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>