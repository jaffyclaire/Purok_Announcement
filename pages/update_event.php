<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();
$eventModel = new Event($db);

$id = $_GET['id'] ?? ($_POST['event_id'] ?? null);
if (!$id) {
    header("Location: events.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventModel->title       = $_POST['title'] ?? '';
    $eventModel->description = $_POST['description'] ?? '';
    $eventModel->event_date  = $_POST['event_date'] ?? '';
    $eventModel->location    = $_POST['location'] ?? '';

    if (empty($eventModel->title) || empty($eventModel->event_date)) {
        $message = "Please provide an event title and date/time.";
    } else {
        try {
            $uploadedFile = $_FILES['image'] ?? null;
            $eventModel->updateEvent($id, $_SESSION['user_id'], $uploadedFile);
            header("Location: events.php?updated=1");
            exit;
        } catch (Throwable $e) {
            error_log('Event update failed: ' . $e->getMessage());
            $message = $e->getMessage() ?: "Failed to update the event.";
        }
    }
}

$record = $eventModel->getEventById($id);
if (!$record) {
    header("Location: events.php");
    exit;
}

$dateVal = !empty($record['event_date']) ? date('Y-m-d\TH:i', strtotime($record['event_date'])) : '';

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 720px;">
    <h1 class="mb-4">Edit Event</h1>

    <?php if ($message): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card post-card">
        <div class="card-body">
            <form method="POST" action="update_event.php?id=<?php echo $record['event_id']; ?>" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?php echo $record['event_id']; ?>">

                <div class="mb-3">
                    <label class="form-label" for="eventTitle">Event Title</label>
                    <input type="text" name="title" id="eventTitle" class="form-control" required maxlength="150"
                           value="<?php echo htmlspecialchars($record['title']); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="eventDate">Date &amp; Time</label>
                        <input type="datetime-local" name="event_date" id="eventDate" class="form-control" required
                               value="<?php echo htmlspecialchars($dateVal); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="eventLocation">Location</label>
                        <input type="text" name="location" id="eventLocation" class="form-control" maxlength="255"
                           value="<?php echo htmlspecialchars($record['location'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="eventDesc">Description</label>
                    <textarea name="description" id="eventDesc" class="form-control" rows="4"><?php echo htmlspecialchars($record['description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="eventImage">Banner Image <small class="text-muted">(Optional &middot; JPG, PNG, WebP &middot; max 2 MB)</small></label>
                    <?php if (!empty($record['image_path']) && file_exists(__DIR__ . '/../' . $record['image_path'])): ?>
                        <div class="mb-2">
                            <img src="../<?php echo htmlspecialchars($record['image_path']); ?>" alt="Current event image" style="max-height: 120px; border-radius: 8px; display: block;">
                            <small class="text-muted">Uploading a new image will replace the current image.</small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" id="eventImage" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="events.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

