<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/Announcement.php';

$database = new Database();
$db = $database->connect();

$announcement = new Announcement($db);
$totalActive = $announcement->getTotalActiveCount();
$byCategory  = $announcement->getCountByCategory();
$maxCategoryCount = 0;
foreach ($byCategory as $row) {
    $maxCategoryCount = max($maxCategoryCount, (int) $row['total']);
}

require_once 'header.php';
?>

<div class="stat-grid">
    <section class="stat-card" aria-label="Total active announcements">
        <span class="stat-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5h16v13H4zM4 9h16M8 13h8m-8 3h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
        <div>
            <p class="stat-number"><?php echo $totalActive; ?></p>
            <p class="stat-label">Total Active Announcements</p>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-body">
        <h2 class="h4 mb-3">Announcements by Category</h2>
        <?php if (empty($byCategory)): ?>
            <div class="empty-state py-4">
                <p class="mb-0">Category totals will appear here when categories are available.</p>
            </div>
        <?php else: ?>
            <div class="category-list">
                <?php foreach ($byCategory as $row): ?>
                    <?php $barWidth = $maxCategoryCount > 0 ? ((int) $row['total'] / $maxCategoryCount) * 100 : 0; ?>
                    <div class="category-row">
                        <span class="category-name"><?php echo htmlspecialchars($row['category_name']); ?></span>
                        <div class="category-bar-track" role="img" aria-label="<?php echo htmlspecialchars($row['category_name']); ?>: <?php echo (int) $row['total']; ?> active announcements">
                            <div class="category-bar" style="width: <?php echo $barWidth; ?>%;"></div>
                        </div>
                        <span class="category-count"><?php echo (int) $row['total']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'footer.php'; ?>