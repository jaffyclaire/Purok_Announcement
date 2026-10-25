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
            $uploadedFiles = $_FILES['images'] ?? ($_FILES['image'] ?? null);
            $announcement->createWithNotifications($_SESSION['user_id'], $uploadedFiles);
            header("Location: view_announcements.php?added=1");
            exit;
        } catch (Throwable $e) {
            error_log('Announcement creation transaction failed: ' . $e->getMessage());
            $message = $e->getMessage() ?: "Failed to save the announcement. No changes were saved.";
            $messageType = "danger";
        }
    }
}

require_once 'header.php';
?>

<div class="mx-auto" style="max-width: 720px;">
    <h1 class="mb-4">Create an Announcement</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="<?php echo $messageType === 'danger' ? 'alert' : 'status'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card post-card">
        <div class="card-body">
            <form method="POST" action="add_announcement.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="45"
                           placeholder="What's the announcement about?"
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Details</label>
                    <textarea name="details" class="form-control" rows="4" required
                              placeholder="Write the full announcement here..."><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="imagesInput">
                        Attachment Images <small class="text-muted">(Optional &middot; Max 5 images &middot; JPG, PNG, WebP &middot; max 2 MB each)</small>
                    </label>
                    <input type="file" name="images[]" id="imagesInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <div id="imageUploadError" class="text-danger small mt-1" style="display: none;"></div>
                    <div id="imagePreviewContainer" class="image-preview-grid" style="display: none;"></div>
                </div>

                <div class="form-check form-switch d-flex align-items-center gap-2 mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_urgent" id="is_urgent" <?php echo !empty($_POST['is_urgent']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_urgent">Mark as urgent</label>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success">Post Announcement</button>
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
    if (!input || !previewContainer) return;

    let dt = new DataTransfer();
    const MAX_FILES = 5;
    const MAX_SIZE = 2 * 1024 * 1024; // 2 MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

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

    input.addEventListener('change', function() {
        clearError();
        const incoming = Array.from(input.files);

        for (const file of incoming) {
            if (dt.items.length >= MAX_FILES) {
                showError('You can upload a maximum of ' + MAX_FILES + ' images.');
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

    function renderPreviews() {
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
