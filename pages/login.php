<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->connect();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $query = "SELECT user_id, first_name, last_name, email, password, role
              FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];

        header("Location: view_announcements.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}

require_once 'header.php';
?>

<div class="card shadow-sm post-card">
            <div class="card-body p-4">
                <h3 class="mb-4 text-center">Log In</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                </form>

                <p class="text-center text-muted mt-3 small">
                    Don't have an account? <a href="register.php">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>