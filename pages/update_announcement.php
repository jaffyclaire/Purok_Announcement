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
        $uploadedFiles = $_FILES['images'] ?? ($_FILES['image'] ?? null);
        $removedAttachmentIds = $_POST['remove_images'] ?? [];
        $announcement->updateWithNotifications($id, $_SESSION['user_id'], $uploadedFiles, $removedAttachmentIds);
        header("Location: view_announcements.php?updated=1");
        exit;
    } catch (Throwable $e) {
        error_log('Announcement update transaction failed: ' . $e->getMessage());
        $message = $e->getMessage() ?: "Failed to update the announcement. No changes were saved.";
    }
}

$record = $announcement->getAnnouncementById($id);
if (!$record) {
    header("Location: view_announcements.php");
    exit;
}
$existingImages = $record['images'] ?? [];

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 720px;">
    <h1 class="mb-4">Edit Announcement</h1>

    <?php if ($message): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card post-card">
        <div class="card-body">
            <form method="POST" action="update_announcement.php?id=<?php echo $record['announcement_id']; ?>" enctype="multipart/form-data">
                <input type="hidden" name="announcement_id" value="<?php echo $record['announcement_id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="45"
                           value="<?php echo htmlspecialchars($record['title']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Details</label>
                    <textarea name="details" class="form-control" rows="4" required><?php echo htmlspecialchars($record['details']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($cat['category_id'] == $record['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
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

                <div class="mb-3">
                    <label class="form-label">Attachments <small class="text-muted">(Max 5 images total &middot; JPG, PNG, WebP &middot; max 2 MB each)</small></label>
                    <?php if (!empty($existingImages)): ?>
                        <div class="mb-2">
                            <span class="form-text d-block mb-2">Current images (check "Remove" to delete on save):</span>
                            <div class="existing-images-grid" id="existingImagesGrid">
                                <?php foreach ($existingImages as $img): ?>
                                    <div class="existing-image-card" id="existingCard_<?php echo $img['attachment_id']; ?>">
                                        <img src="../<?php echo htmlspecialchars($img['file_path']); ?>" alt="Attachment thumbnail" class="existing-image-thumb">
                                        <label class="existing-image-check-label">
                                            <input type="checkbox" name="remove_images[]" value="<?php echo $img['attachment_id']; ?>" class="existing-remove-checkbox">
                                            <span>Remove</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <label class="form-label mt-2" for="imagesInput">Upload New Images</label>
                    <input type="file" name="images[]" id="imagesInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <div id="imageUploadError" class="text-danger small mt-1" style="display: none;"></div>
                    <div id="imagePreviewContainer" class="image-preview-grid" style="display: none;"></div>
                </div>

                <div class="form-check form-switch d-flex align-items-center gap-2 mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_urgent" id="is_urgent"
                        <?php echo $record['is_urgent'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_urgent">Mark as urgent</label>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="view_announcements.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const input = document.getElementById('imagesInput');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const errorEl = document.getElementById('imageUploadError');
    const existingCheckboxes = document.querySelectorAll('.existing-remove-checkbox');
    const totalExisting = <?php echo count($existingImages); ?>;

    let dt = new DataTransfer();
    const MAX_TOTAL = 5;
    const MAX_SIZE = 2 * 1024 * 1024; // 2 MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function getKeptExistingCount() {
        let removed = 0;
        existingCheckboxes.forEach(cb => {
            if (cb.checked) removed++;
        });
        return Math.max(0, totalExisting - removed);
    }

    function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
    }

    function clearError() {
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    }

    existingCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const card = cb.closest('.existing-image-card');
            if (card) {
                card.classList.toggle('is-marked-delete', cb.checked);
            }
            clearError();
            const allowedNew = MAX_TOTAL - getKeptExistingCount();
            if (dt.items.length > allowedNew) {
                showError('You currently have ' + dt.items.length + ' new image(s) selected, but only ' + allowedNew + ' slot(s) remain.');
            }
        });
    });

    if (input && previewContainer) {
        input.addEventListener('change', function() {
            clearError();
            const kept = getKeptExistingCount();
            const allowedNew = MAX_TOTAL - kept;
            const incoming = Array.from(input.files);

            for (const file of incoming) {
                if (dt.items.length >= allowedNew) {
                    showError('Maximum 5 images total. You have ' + kept + ' existing image(s) and can only add up to ' + allowedNew + ' new image(s).');
                    break;
                }
                if (!ALLOWED_TYPES.includes(file.type)) {
                    showError('File "' + file.name + '" is not an allowed format (JPG, PNG, WebP only).');
                    continue;
                }
                if (file.size > MAX_SIZE) {
                    showError('File "' + file.name + '" exceeds the 2 MB size limit.');
                    continue;
                }
                dt.items.add(file);
            }

            input.files = dt.files;
            renderPreviews();
        });
    }

    function renderPreviews() {
        if (!previewContainer) return;
        previewContainer.innerHTML = '';
        if (dt.files.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }
        previewContainer.style.display = 'flex';

        Array.from(dt.files).forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'preview-thumb-card';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            card.appendChild(img);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'preview-remove-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                removeFile(index);
            });
            card.appendChild(removeBtn);

            previewContainer.appendChild(card);
        });
    }

    function removeFile(indexToRemove) {
        const nextDt = new DataTransfer();
        Array.from(dt.files).forEach((file, idx) => {
            if (idx !== indexToRemove) {
                nextDt.items.add(file);
            }
        });
        dt = nextDt;
        input.files = dt.files;
        clearError();
        renderPreviews();
    }
})();
</script>

<?php require_once 'footer.php'; ?>
