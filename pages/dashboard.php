<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$announcement = new Announcement($db);
$totalActive = $announcement->getTotalActiveCount();
$byCategory  = $announcement->getCountByCategory();

require_once 'header.php';
?>

<h3 class="mb-4">Dashboard</h3>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Active Announcements</h6>
                <h2 class="mb-0"><?php echo $totalActive; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Announcements by Category</h5>
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Active Announcements</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byCategory as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>