<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$announcement = new Announcement($db);
$records = $announcement->getAnnouncements();

require_once 'header.php';

// Helper to turn a name into initials, e.g. "Jane Cruz" -> "JC"
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        $initials .= strtoupper(substr($p, 0, 1));
    }
    return substr($initials, 0, 2);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Announcements</h3>
        <small class="text-muted"><?php echo count($records); ?> total posted</small>
    </div>
    <?php if ($_SESSION['role'] === 'Officer'): ?>
        <a href="add_announcement.php" class="btn btn-warning fw-semibold">+ Add Announcement</a>
    <?php endif; ?>
</div>

<div class="mx-auto mb-4" style="max-width: 640px;">
    <form method="GET" action="search_announcements.php">
        <div class="input-group">
            <input type="text" name="keyword" class="form-control"
                   placeholder="Search announcements...">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Announcement added successfully.</div>
<?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Announcement updated successfully.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Announcement deleted successfully.</div>
<?php endif; ?>

<div class="feed mx-auto" style="max-width: 640px;">
    <?php if (empty($records)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">No announcements yet.</div>
        </div>
    <?php else: ?>
        <?php foreach ($records as $row): ?>
            <div class="card shadow-sm post-card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="post-avatar me-2">
                            <?php echo getInitials($row['posted_by_name'] ?? 'Unknown'); ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?php echo htmlspecialchars($row['posted_by_name'] ?? 'Unknown'); ?></div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($row['posted_by_role'] ?? ''); ?>
                                &middot; <?php echo date('M d, Y \a\t g:i A', strtotime($row['date_posted'])); ?>
                            </small>
                        </div>
                        <?php if ($row['is_urgent']): ?>
                            <span class="badge badge-urgent">URGENT</span>
                        <?php endif; ?>
                    </div>

                    <span class="badge category-tag mb-2"><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></span>
                    <h5 class="mt-2 mb-2"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($row['details'])); ?></p>

                    <?php if ($_SESSION['role'] === 'Officer'): ?>
                        <hr class="my-2">
                        <div class="d-flex gap-3">
                            <a href="update_announcement.php?id=<?php echo $row['announcement_id']; ?>"
                               class="post-action">✏️ Edit</a>
                            <a href="delete_announcement.php?id=<?php echo $row['announcement_id']; ?>"
                               class="post-action text-danger"
                               onclick="return confirm('Delete this announcement?');">🗑️ Delete</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>