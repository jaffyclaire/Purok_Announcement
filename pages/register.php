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
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $result = $userModel->registerUser($first_name, $last_name, $email, $password);

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
        max-width: 560px !important;
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
        margin-bottom: 22px;
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
        margin: 0 0 26px;
        color: var(--muted);
        text-align: center;
        font-size: 0.94rem;
    }
    .auth-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 16px;
    }
    .auth-field {
        min-width: 0;
        margin-bottom: 18px;
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
        margin-bottom: 20px;
        padding: 12px 14px;
        border: 0;
        border-radius: 12px;
        background: #FBE4E1;
        color: #C0392B;
        font-size: 0.9rem;
    }
    .auth-alert-success {
        background: var(--mint);
        color: var(--green-900);
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
    @media (max-width: 600px) {
        .auth-grid {
            grid-template-columns: 1fr;
            gap: 0;
        }
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

    <h1 class="auth-heading">Create your account</h1>
    <p class="auth-subtitle">Join your community and stay up to date.</p>

    <?php if ($error): ?>
        <div class="auth-alert" role="alert">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 7.5v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="auth-alert auth-alert-success" role="status">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="m8 12 2.5 2.5L16.5 9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?php echo htmlspecialchars($success); ?> <a href="login.php" class="auth-link">Go to Login</a></span>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="register.php">
        <div class="auth-grid">
            <div class="auth-field">
                <label class="auth-label" for="register-first-name">First Name</label>
                <div class="auth-input-wrap">
                    <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M4.5 20c.6-3.4 3.3-5.2 7.5-5.2s6.9 1.8 7.5 5.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input id="register-first-name" type="text" name="first_name" class="auth-input" placeholder="First name" autocomplete="given-name" required maxlength="45"
                           value="<?php echo htmlspecialchars($first_name); ?>">
                </div>
            </div>
            <div class="auth-field">
                <label class="auth-label" for="register-last-name">Last Name</label>
                <div class="auth-input-wrap">
                    <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M4.5 20c.6-3.4 3.3-5.2 7.5-5.2s6.9 1.8 7.5 5.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input id="register-last-name" type="text" name="last_name" class="auth-input" placeholder="Last name" autocomplete="family-name" required maxlength="45"
                           value="<?php echo htmlspecialchars($last_name); ?>">
                </div>
            </div>
        </div>

        <div class="auth-field">
            <label class="auth-label" for="register-email">Email address</label>
            <div class="auth-input-wrap">
                <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                    <path d="m4.5 7 7.5 5.5L19.5 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input id="register-email" type="email" name="email" class="auth-input" placeholder="you@example.com" autocomplete="email" required maxlength="45"
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
        </div>

        <div class="auth-grid">
            <div class="auth-field">
                <label class="auth-label" for="register-password">Password</label>
                <div class="auth-input-wrap">
                    <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input id="register-password" type="password" name="password" class="auth-input" placeholder="At least 6 characters" autocomplete="new-password" required minlength="6">
                    <button class="auth-eye" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="auth-field">
                <label class="auth-label" for="register-confirm-password">Confirm Password</label>
                <div class="auth-input-wrap">
                    <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input id="register-confirm-password" type="password" name="confirm_password" class="auth-input" placeholder="Enter password again" autocomplete="new-password" required minlength="6">
                    <button class="auth-eye" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn auth-submit w-100">Create account</button>
    </form>

    <p class="auth-footer">Already have an account? <a class="auth-link" href="login.php">Log in</a></p>
    <?php endif; ?>
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