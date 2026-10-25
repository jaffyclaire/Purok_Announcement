<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ImageUploader.php';

$database = new Database();
$db = $database->connect();
$userModel = new User($db);

$userId = (int) $_SESSION['user_id'];
$currentUser = $userModel->getUserById($userId);

if (!$currentUser) {
    header("Location: logout.php");
    exit;
}

$profileSuccess = "";
$profileError = "";
$passwordSuccess = "";
$passwordError = "";
$photoSuccess = "";
$photoError = "";

// 1. Action: Update Profile Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $validation = ImageUploader::validate($_FILES['avatar'], 'avatars');
    if (!$validation['valid']) {
        $photoError = $validation['error'];
    } else {
        try {
            ImageUploader::moveFile($validation);
            $oldAvatar = $currentUser['avatar_path'] ?? null;
            $userModel->updateAvatar($userId, $validation['relative_path']);
            $_SESSION['avatar_path'] = $validation['relative_path'];
            if ($oldAvatar) {
                ImageUploader::deleteFile($oldAvatar);
            }
            $photoSuccess = "Profile photo updated successfully.";
            $currentUser = $userModel->getUserById($userId);
        } catch (Throwable $e) {
            $photoError = "Failed to upload photo: " . $e->getMessage();
        }
    }
}

// 2. Action: Remove Profile Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_avatar'])) {
    $oldAvatar = $currentUser['avatar_path'] ?? null;
    $userModel->updateAvatar($userId, null);
    $_SESSION['avatar_path'] = null;
    if ($oldAvatar) {
        ImageUploader::deleteFile($oldAvatar);
    }
    $photoSuccess = "Profile photo removed.";
    $currentUser = $userModel->getUserById($userId);
}

// 3. Action: Edit Name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $profileError = "First name and last name are required.";
    } else {
        try {
            $userModel->updateName($userId, $firstName, $lastName);
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name']  = $lastName;
            $profileSuccess = "Profile details updated successfully.";
            $currentUser = $userModel->getUserById($userId);
        } catch (Throwable $e) {
            $profileError = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// 4. Action: Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordError = "Please fill in all password fields.";
    } elseif (!password_verify($currentPassword, $currentUser['password'])) {
        $passwordError = "Current password is incorrect.";
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = "New passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $passwordError = "New password must be at least 6 characters.";
    } else {
        // TRANSACTION - change password
        try {
            $db->beginTransaction();
            $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $pwdStmt = $db->prepare("UPDATE users SET password = :password WHERE user_id = :id");
            $pwdStmt->execute([
                ':password' => $newHashed,
                ':id'       => $userId
            ]);
            $db->commit();
            $passwordSuccess = "Password changed successfully.";
            $currentUser = $userModel->getUserById($userId);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $passwordError = "Failed to change password. No changes were saved.";
        }
    }
}

require_once 'header.php';

$userFullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
$avatarPath = $currentUser['avatar_path'] ?? null;
$hasAvatar = !empty($avatarPath) && file_exists(__DIR__ . '/../' . $avatarPath);

$memberSince = !empty($currentUser['created_at'])
    ? date('F Y', strtotime($currentUser['created_at']))
    : '2026';
?>

<div class="feed-column">
    <div class="mb-4">
        <h1 class="h2 mb-1">My Profile</h1>
        <p class="text-muted mb-0">Manage your personal details and account security.</p>
    </div>

    <?php if ($photoSuccess): ?>
        <div class="alert alert-success" role="status"><?php echo htmlspecialchars($photoSuccess); ?></div>
    <?php elseif ($photoError): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($photoError); ?></div>
    <?php endif; ?>

    <!-- Profile Header Card -->
    <div class="card post-card mb-4">
        <div class="card-body">
            <div class="profile-header-layout">
                <div class="profile-avatar-block text-center">
                    <div class="profile-large-avatar avatar-container-lg" aria-label="<?php echo htmlspecialchars($userFullName); ?>" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; margin: 0 auto;">
                        <?php if ($hasAvatar): ?>
                            <img src="../<?php echo htmlspecialchars($avatarPath); ?>" alt="<?php echo htmlspecialchars($userFullName); ?>" class="avatar-img avatar-lg profile-large-img" width="120" height="120" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; display: block;">
                        <?php else: ?>
                            <div class="profile-large-initials">
                                <?php echo getInitials($userFullName ?: '?'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="profile.php" enctype="multipart/form-data" id="avatarUploadForm" class="mt-2 text-center">
                        <input type="file" name="avatar" id="avatarFileInput" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="document.getElementById('avatarUploadForm').submit()">
                        <button type="button" class="btn btn-sm btn-outline-success btn-change-photo" onclick="document.getElementById('avatarFileInput').click()">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
                            <span>Change photo</span>
                        </button>
                        <?php if ($hasAvatar): ?>
                            <button type="submit" name="remove_avatar" value="1" class="btn btn-sm btn-link text-danger p-0 d-block mx-auto mt-1" onclick="return confirm('Remove your profile photo?');">Remove</button>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="profile-meta-block">
                    <h2 class="h3 mb-1 text-dark"><?php echo htmlspecialchars($userFullName); ?></h2>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="role-chip"><?php echo htmlspecialchars($currentUser['role'] ?? 'Resident'); ?></span>
                        <span class="meta-sep" aria-hidden="true">&middot;</span>
                        <span class="text-muted small">Member since <?php echo htmlspecialchars($memberSince); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Section -->
    <div class="card post-card mb-4">
        <div class="card-body">
            <h3 class="h5 mb-3 text-dark">Personal Information</h3>
            <?php if ($profileSuccess): ?>
                <div class="alert alert-success" role="status"><?php echo htmlspecialchars($profileSuccess); ?></div>
            <?php elseif ($profileError): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($profileError); ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <input type="hidden" name="update_profile" value="1">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="firstNameInput">First Name</label>
                        <input type="text" name="first_name" id="firstNameInput" class="form-control" required
                               value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" maxlength="45">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="lastNameInput">Last Name</label>
                        <input type="text" name="last_name" id="lastNameInput" class="form-control" required
                               value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" maxlength="45">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="emailInput">Email Address</label>
                        <input type="email" id="emailInput" class="form-control bg-light"
                               value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" disabled readonly>
                        <small class="text-muted">Email address is associated with your official purok account.</small>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Section -->
    <div class="card post-card mb-4">
        <div class="card-body">
            <h3 class="h5 mb-3 text-dark">Change Password</h3>
            <?php if ($passwordSuccess): ?>
                <div class="alert alert-success" role="status"><?php echo htmlspecialchars($passwordSuccess); ?></div>
            <?php elseif ($passwordError): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($passwordError); ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <input type="hidden" name="change_password" value="1">
                <div class="mb-3">
                    <label class="form-label" for="currentPasswordInput">Current Password</label>
                    <input type="password" name="current_password" id="currentPasswordInput" class="form-control" required autocomplete="current-password">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="newPasswordInput">New Password</label>
                        <input type="password" name="new_password" id="newPasswordInput" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="confirmPasswordInput">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-outline-success">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
