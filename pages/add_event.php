<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_officer.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();

$message = "";
$messageType = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventModel = new Event($db);
    $eventModel->title       = $_POST['title'] ?? '';
    $eventModel->description = $_POST['description'] ?? '';
    $eventModel->event_date  = $_POST['event_date'] ?? '';
    $eventModel->location    = $_POST['location'] ?? '';

    if (empty($eventModel->title) || empty($eventModel->event_date)) {
        $message = "Please provide an event title and date/time.";
        $messageType = "danger";
    } else {
        try {
            $uploadedFile = $_FILES['image'] ?? null;
            $eventModel->createWithNotifications($_SESSION['user_id'], $uploadedFile);
            header("Location: events.php?added=1");
            exit;
        } catch (Throwable $e) {
            error_log('Event creation transaction failed: ' . $e->getMessage());
            $message = $e->getMessage() ?: "Failed to save the event. No changes were saved.";
            $messageType = "danger";
        }
    }
}

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 720px;">
    <h1 class="mb-4">Create an Event</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="<?php echo $messageType === 'danger' ? 'alert' : 'status'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card post-card">
        <div class="card-body">
            <form method="POST" action="add_event.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label" for="eventTitle">Event Title</label>
                    <input type="text" name="title" id="eventTitle" class="form-control" required maxlength="150"
                           placeholder="e.g. Barangay General Assembly"
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="eventDate">Date &amp; Time</label>
                        <input type="datetime-local" name="event_date" id="eventDate" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="eventLocation">Location</label>
                        <input type="text" name="location" id="eventLocation" class="form-control" maxlength="255"
                           placeholder="e.g. Purok Covered Court"
                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="eventDesc">Description</label>
                    <textarea name="description" id="eventDesc" class="form-control" rows="4"
                              placeholder="Describe the event, agenda, or what to bring..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="eventImage">Banner Image <small class="text-muted">(Optional &middot; JPG, PNG, WebP &middot; max 2 MB)</small></label>
                    <input type="file" name="image" id="eventImage" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success">Save &amp; Notify Users</button>
                    <a href="events.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

