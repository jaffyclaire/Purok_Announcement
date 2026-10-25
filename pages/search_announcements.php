<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$announcement = new Announcement($db);

$keyword = $_GET['keyword'] ?? '';
$results = [];
$searched = false;

if (!empty($keyword)) {
    $searched = true;
    $results = $announcement->searchAnnouncements($keyword);
}

require_once 'header.php';
?>

<h3 class="mb-4">Search Announcements</h3>

<form method="GET" action="search_announcements.php" class="mb-4">
    <div class="input-group">
        <input type="text" name="keyword" class="form-control"
               placeholder="Search by title, details, category, status, or posted-by name..."
               value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit" class="btn btn-success">Search</button>
    </div>
</form>

<?php if ($searched): ?>
    <p class="text-muted"><?php echo count($results); ?> result(s) found for "<?php echo htmlspecialchars($keyword); ?>"</p>

    <div class="card shadow-sm">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Urgent</th>
                    <th>Status</th>
                    <th>Posted By</th>
                    <th>Date Posted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No matching announcements found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>A-<?php echo str_pad($row['announcement_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>
                                <?php if ($row['is_urgent']): ?>
                                    <span class="badge badge-urgent">URGENT</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['posted_by_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_posted'])); ?></td>
                            <td>
                                <a href="update_announcement.php?id=<?php echo $row['announcement_id']; ?>"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="delete_announcement.php?id=<?php echo $row['announcement_id']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this announcement?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>