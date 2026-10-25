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

<style>
    :root {
        --green-900: #1F4D2B;
        --green-700: #3F7A2E;
        --green-500: #6FA04F;
        --sage: #A9C487;
        --mint: #EAF3DC;
        --cream: #FAF8F1;
        --text: #1E2A22;
        --muted: #6B7A70;
    }
    .auth-page {
        min-height: 100vh;
        padding: 24px;
        background: linear-gradient(145deg, var(--cream) 0%, var(--mint) 100%);
        color: var(--text);
        font-family: 'Inter', sans-serif;
    }
    .auth-page > div {
        max-width: 440px !important;
    }
    .auth-page > div > .sidebar-brand {
        display: none !important;
    }
    .auth-page h1,
    .auth-page h2,
    .auth-page h3 {
        color: var(--green-900);
        font-family: 'Playfair Display', serif;
    }
    .auth-card {
        border: 1px solid rgba(31, 77, 43, 0.08);
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 20px 55px rgba(31, 77, 43, 0.12);
        padding: 36px;
    }
    .auth-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        margin-bottom: 24px;
        color: var(--green-900);
        font-family: 'Playfair Display', serif;
        font-size: 1.35rem;
        font-weight: 700;
        text-decoration: none;
    }
    .auth-brand svg {
        width: 22px;
        height: 22px;
        color: var(--green-700);
    }
    .auth-heading {
        margin: 0 0 8px;
        text-align: center;
        font-size: 2rem;
        font-weight: 700;
    }
    .auth-subtitle {
        margin: 0 0 28px;
        color: var(--muted);
        text-align: center;
        font-size: 0.94rem;
    }
    .auth-field {
        margin-bottom: 20px;
    }
    .auth-label {
        display: block;
        margin-bottom: 8px;
        color: var(--text);
        font-size: 0.88rem;
        font-weight: 600;
    }
    .auth-input-wrap {
        position: relative;
    }
    .auth-input-icon {
        position: absolute;
        top: 50%;
        left: 15px;
        width: 18px;
        height: 18px;
        color: var(--muted);
        transform: translateY(-50%);
        pointer-events: none;
    }
    .auth-input {
        width: 100%;
        height: 48px;
        padding: 0 46px 0 44px;
        border: 1px solid #d9e2d3;
        border-radius: 14px;
        background-color: #fff;
        color: var(--text);
        font: inherit;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }
    .auth-input:focus {
        border-color: var(--green-500);
        outline: none;
        box-shadow: 0 0 0 4px rgba(111, 160, 79, 0.18);
    }
    .auth-input::placeholder {
        color: #99a59b;
    }
    .auth-input:-webkit-autofill,
    .auth-input:-webkit-autofill:hover,
    .auth-input:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--text);
        -webkit-box-shadow: 0 0 0 1000px #fff inset;
        transition: background-color 9999s ease-out 0s;
    }
    .auth-eye {
        position: absolute;
        top: 50%;
        right: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: var(--muted);
        transform: translateY(-50%);
        cursor: pointer;
    }
    .auth-eye:hover,
    .auth-eye:focus-visible {
        color: var(--green-700);
        outline: none;
    }
    .auth-eye svg {
        width: 18px;
        height: 18px;
    }
    .auth-forgot {
        margin: -4px 0 18px;
        text-align: right;
        font-size: 0.86rem;
    }
    .auth-link {
        color: var(--green-700);
        font-weight: 600;
        text-decoration: none;
    }
    .auth-link:hover {
        color: var(--green-900);
        text-decoration: underline;
    }
    .auth-submit {
        min-height: 48px;
        border: 1px solid var(--green-700);
        border-radius: 999px;
        background: var(--green-700);
        color: #fff;
        font-weight: 600;
        transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease;
    }
    .auth-submit:hover,
    .auth-submit:focus {
        border-color: var(--green-900);
        background: var(--green-900);
        color: #fff;
        box-shadow: 0 5px 14px rgba(31, 77, 43, 0.2);
    }
    .auth-submit:active {
        transform: translateY(1px);
    }
    .auth-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 22px;
        padding: 12px 14px;
        border: 0;
        border-radius: 12px;
        background: #FBE4E1;
        color: #C0392B;
        font-size: 0.9rem;
    }
    .auth-alert svg {
        width: 19px;
        height: 19px;
        flex: 0 0 19px;
    }
    .auth-footer {
        margin: 22px 0 0;
        color: var(--muted);
        text-align: center;
        font-size: 0.88rem;
    }
    @media (max-width: 480px) {
        .auth-page {
            padding: 16px;
        }
        .auth-card {
            padding: 28px 22px;
        }
        .auth-heading {
            font-size: 1.75rem;
        }
    }
</style>

<main class="auth-card">
    <a href="view_announcements.php" class="auth-brand">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M20.5 3.5C12.7 3.6 6.5 5.1 4 9.7c-1.4 2.6-.3 5.5 2.3 6.2 2.7.8 5.2-.7 6.8-3.3 1.8-2.6 2.9-5.9 7.4-9.1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3.5 20.5c2.7-5.1 6.1-7.8 10.1-9.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <span>PurokInform</span>
    </a>

    <h1 class="auth-heading">Welcome back</h1>
    <p class="auth-subtitle">Sign in to stay connected with your community.</p>

    <?php if ($error): ?>
        <div class="auth-alert" role="alert">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 7.5v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="auth-field">
            <label class="auth-label" for="login-email">Email address</label>
            <div class="auth-input-wrap">
                <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                    <path d="m4.5 7 7.5 5.5L19.5 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input id="login-email" type="email" name="email" class="auth-input" placeholder="you@example.com" autocomplete="email" required>
            </div>
        </div>

        <div class="auth-field">
            <label class="auth-label" for="login-password">Password</label>
            <div class="auth-input-wrap">
                <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                <input id="login-password" type="password" name="password" class="auth-input" placeholder="Enter your password" autocomplete="current-password" required>
                <button class="auth-eye" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                </button>
            </div>
        </div>

        <p class="auth-forgot"><a class="auth-link" href="forgot_password.php">Forgot password?</a></p>
        <button type="submit" class="btn auth-submit w-100">Log in</button>
    </form>

    <p class="auth-footer">Don't have an account? <a class="auth-link" href="register.php">Sign up</a></p>
</main>

<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = button.parentElement.querySelector('input');
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', showing ? 'true' : 'false');
    });
});
</script>

</div>
</div>

<?php require_once 'footer.php'; ?>
