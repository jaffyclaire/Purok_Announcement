<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->connect();

if ($db) {
    $connectionMessage = "Connected to the database successfully!";
    $connectionClass = "success";
} else {
    $connectionMessage = "Connection failed.";
    $connectionClass = "danger";
}

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 680px;">
    <h1 class="mb-4">Database Connection</h1>
    <div class="card">
        <div class="card-body">
            <div class="alert alert-<?php echo $connectionClass; ?> mb-0" role="<?php echo $connectionClass === 'danger' ? 'alert' : 'status'; ?>">
                <?php echo htmlspecialchars($connectionMessage); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
