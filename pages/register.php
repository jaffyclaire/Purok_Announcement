<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

$database = new Database();
$db = $database->connect();

$userModel = new User($db);

$error = "";
$success = "";
$first_name = '';
$last_name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $role       = $_POST['role'] ?? 'Resident';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $result = $userModel->registerUser($first_name, $last_name, $email, $password, $role);

        if ($result === 'duplicate') {
            $error = "An account with that email already exists.";
        } elseif ($result) {
            $success = "Account created! You can now log in.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

require_once 'header.php';
?>

<div class="card shadow-sm post-card">
    <div class="card-body p-4">
        <h3 class="mb-4 text-center">Sign Up</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="login.php" class="alert-link">Go to Login</a>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text" name="first_name" class="form-control" required maxlength="45"
                           value="<?php echo htmlspecialchars($first_name); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required maxlength="45"
                           value="<?php echo htmlspecialchars($last_name); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required maxlength="45"
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" class="form-select">
                    <option value="Resident">Resident</option>
                    <option value="Officer">Officer</option>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>

        <p class="text-center text-muted mt-3 small">
            Already have an account? <a href="login.php">Log in</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>