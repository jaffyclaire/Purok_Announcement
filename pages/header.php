<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$authPages = ['login.php', 'register.php'];
$isAuthPage = in_array($currentPage, $authPages);

$unreadCount = 0;
if (!$isAuthPage && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../classes/Notification.php';
    $notifModel = new Notification($db);
    $unreadCount = $notifModel->getUnreadCount($_SESSION['user_id']);

    if (!isset($_SESSION['avatar_path'])) {
        $uStmt = $db->prepare("SELECT avatar_path FROM users WHERE user_id = :uid LIMIT 1");
        $uStmt->execute([':uid' => (int) $_SESSION['user_id']]);
        $uRow = $uStmt->fetch();
        $_SESSION['avatar_path'] = $uRow['avatar_path'] ?? null;
    }
}

if (!function_exists('getInitials')) {
    function getInitials($name) {
        $parts = explode(' ', trim((string) $name));
        $initials = '';
        foreach ($parts as $p) {
            if ($p !== '') {
                $initials .= strtoupper(substr($p, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PurokInform | Announcement Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="<?php echo $isAuthPage ? 'auth-document' : 'app-document'; ?>">
<?php if ($isAuthPage): ?>
    <div class="auth-page">
        <div class="auth-shell">
            <div class="auth-inner">
                <div class="auth-content">
<?php else: ?>
<div class="app-wrapper">
    <header class="topnav-shell">
        <div class="topnav-inner">
            <div class="topnav-left">
                <a class="topnav-brand" href="view_announcements.php" aria-label="PurokInform home">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 21v-7h6v7" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    <span>PurokInform</span>
                </a>

                <nav class="topnav-links" id="primaryNavigation" aria-label="Main navigation">
                    <a class="topnav-link <?php echo in_array($currentPage, ['view_announcements.php', 'add_announcement.php', 'update_announcement.php', 'delete_announcement.php', 'search_announcements.php']) ? 'active' : ''; ?>" href="view_announcements.php">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5h16v13H4zM4 9h16" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 6.8h.01M9.5 6.8h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                        <span>Feed</span>
                    </a>
                    <a class="topnav-link <?php echo in_array($currentPage, ['events.php', 'add_event.php', 'update_event.php', 'delete_event.php']) ? 'active' : ''; ?>" href="events.php">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <span>Events</span>
                    </a>
                    <a class="topnav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg>
                        <span>Dashboard</span>
                    </a>
                </nav>
            </div>

            <div class="topnav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="notifications.php" class="topnav-icon-btn notif-bell-btn <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>" aria-label="Notifications" title="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php if ($unreadCount > 0): ?><span class="notif-count" aria-hidden="true"></span><?php endif; ?>
                    </a>
                    <div class="topnav-popover profile-wrap">
                        <?php
                        $userInitials = strtoupper(substr($_SESSION['first_name'] ?? '', 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1));
                        $userAvatar = $_SESSION['avatar_path'] ?? null;
                        $hasNavAvatar = !empty($userAvatar) && file_exists(__DIR__ . '/../' . $userAvatar);
                        ?>
                        <button type="button" class="profile-avatar avatar-container-nav <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>" onclick="toggleProfileDropdown()" aria-label="Open account menu" aria-expanded="false" aria-controls="profileDropdown" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; padding: 0;">
                            <?php if ($hasNavAvatar): ?>
                                <img src="../<?php echo htmlspecialchars($userAvatar); ?>" alt="Profile avatar" class="avatar-img avatar-nav profile-avatar-img" width="40" height="40" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; display: block;">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($userInitials ?: '?'); ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-details">
                                <span class="profile-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></span>
                                <span class="role-chip"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                            </div>
                            <div class="profile-dropdown-divider"></div>
                            <a class="profile-menu-link" href="profile.php">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
                                <span>My Profile</span>
                            </a>
                            <a class="profile-logout" href="logout.php">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3m10-8h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="topnav-login" href="login.php">Log in</a>
                <?php endif; ?>
                <button type="button" class="mobile-menu-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="primaryNavigation">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
    </header>
    <main class="main-content">
    <dialog class="delete-confirm-dialog" id="deleteConfirmDialog" aria-labelledby="deleteConfirmTitle">
        <div class="delete-confirm-card">
            <span class="delete-confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 13h11l1-13M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h2 id="deleteConfirmTitle">Delete announcement?</h2>
            <p>This action cannot be undone.</p>
            <div class="delete-confirm-actions">
                <button type="button" class="btn btn-outline-secondary" data-delete-cancel>Cancel</button>
                <a class="btn btn-outline-danger" id="confirmDeleteLink" href="#" aria-label="Confirm delete announcement">Delete announcement</a>
            </div>
        </div>
    </dialog>

    <dialog class="acknowledgements-dialog" id="acknowledgementsDialog" aria-labelledby="ackDialogTitle">
        <div class="acknowledgements-card">
            <div class="ack-card-header">
                <h2 id="ackDialogTitle" class="h5 mb-0">Acknowledged by</h2>
                <button type="button" class="btn-close-ack" data-ack-close aria-label="Close dialog">&times;</button>
            </div>
            <div class="ack-card-body" id="ackUsersList">
                <div class="text-muted text-center py-3">Loading...</div>
            </div>
        </div>
    </dialog>

    <div id="imageLightbox" class="lightbox-modal" role="dialog" aria-modal="true" aria-label="Image viewer" style="display: none;">
        <div class="lightbox-backdrop" onclick="closeLightbox()"></div>
        <button type="button" class="lightbox-btn lightbox-close" onclick="closeLightbox()" aria-label="Close image viewer">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <button type="button" class="lightbox-btn lightbox-prev" id="lightboxPrevBtn" onclick="prevLightbox(event)" aria-label="Previous image">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <button type="button" class="lightbox-btn lightbox-next" id="lightboxNextBtn" onclick="nextLightbox(event)" aria-label="Next image">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
        <div class="lightbox-body">
            <img id="lightboxImg" class="lightbox-image" src="" alt="Enlarged view">
            <div id="lightboxCounter" class="lightbox-counter">1 / 1</div>
        </div>
    </div>
<?php endif; ?>
<script>
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    const button = document.querySelector('.profile-avatar');
    if (!dropdown || !button) return;
    dropdown.classList.toggle('show');
    button.setAttribute('aria-expanded', dropdown.classList.contains('show') ? 'true' : 'false');
}
function togglePostMenu(button) {
    const wrap = button.closest('.post-menu-wrap');
    if (!wrap) return;
    const dropdown = wrap.querySelector('.post-menu-dropdown');
    if (!dropdown) return;
    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    document.querySelectorAll('.post-menu-dropdown.show').forEach(function (menu) {
        if (menu !== dropdown) {
            menu.classList.remove('show');
            const btn = menu.closest('.post-menu-wrap')?.querySelector('.post-menu-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    if (isExpanded) {
        dropdown.classList.remove('show');
        button.setAttribute('aria-expanded', 'false');
    } else {
        dropdown.classList.add('show');
        button.setAttribute('aria-expanded', 'true');
    }
}

function toggleReaction(button, announcementId) {
    button.disabled = true;
    fetch('react_announcement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ announcement_id: announcementId })
    })
    .then(r => r.json())
    .then(data => {
        button.disabled = false;
        if (!data.success) {
            alert(data.error || 'Failed to update acknowledgement.');
            return;
        }
        const wrap = button.closest('.reaction-wrap');
        if (!wrap) return;
        if (data.acknowledged) {
            button.classList.add('is-active');
            button.setAttribute('aria-label', 'Remove acknowledgement');
        } else {
            button.classList.remove('is-active');
            button.setAttribute('aria-label', 'Acknowledge announcement');
        }
        const countEl = wrap.querySelector('.reaction-count');
        if (countEl) countEl.textContent = data.count;
    })
    .catch(err => {
        button.disabled = false;
        console.error('Reaction error:', err);
    });
}

function openAcknowledgeList(announcementId) {
    const dialog = document.getElementById('acknowledgementsDialog');
    const listEl = document.getElementById('ackUsersList');
    if (!dialog || !listEl) return;
    listEl.innerHTML = '<div class="text-muted text-center py-3">Loading...</div>';
    dialog.showModal();

    fetch('react_announcement.php?action=list&announcement_id=' + announcementId)
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.users || data.users.length === 0) {
            listEl.innerHTML = '<div class="text-muted text-center py-3">No acknowledgements yet.</div>';
            return;
        }
        let html = '<ul class="ack-list">';
        data.users.forEach(u => {
            const avatar = u.avatar_path ? `<div class="avatar-container-sm" style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0;"><img src="../${escapeHtml(u.avatar_path)}" class="avatar-img avatar-sm ack-avatar-img" width="36" height="36" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; display: block;" alt="${escapeHtml(u.name)}"></div>` : `<div class="ack-avatar-initials">${escapeHtml(u.name.substring(0, 2).toUpperCase())}</div>`;
            html += `<li class="ack-list-item">
                ${avatar}
                <div class="ack-info">
                    <span class="ack-name">${escapeHtml(u.name)}</span>
                    <span class="ack-meta"><span class="role-chip">${escapeHtml(u.role)}</span> &middot; ${escapeHtml(u.reaction_date)}</span>
                </div>
            </li>`;
        });
        html += '</ul>';
        listEl.innerHTML = html;
    })
    .catch(err => {
        listEl.innerHTML = '<div class="text-danger text-center py-3">Failed to load acknowledgements.</div>';
    });
}

function escapeHtml(str) {
    if (!str) return '';
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

let currentGallery = [];
let currentGalleryIndex = 0;

function openLightbox(gallery, startIndex) {
    if (!Array.isArray(gallery) || gallery.length === 0) return;
    currentGallery = gallery;
    currentGalleryIndex = Math.max(0, Math.min(startIndex || 0, gallery.length - 1));
    updateLightboxUI();
    const modal = document.getElementById('imageLightbox');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    const modal = document.getElementById('imageLightbox');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    currentGallery = [];
    currentGalleryIndex = 0;
}

function prevLightbox(e) {
    if (e) e.stopPropagation();
    if (currentGallery.length <= 1) return;
    currentGalleryIndex = (currentGalleryIndex - 1 + currentGallery.length) % currentGallery.length;
    updateLightboxUI();
}

function nextLightbox(e) {
    if (e) e.stopPropagation();
    if (currentGallery.length <= 1) return;
    currentGalleryIndex = (currentGalleryIndex + 1) % currentGallery.length;
    updateLightboxUI();
}

function updateLightboxUI() {
    if (currentGallery.length === 0) return;
    const imgEl = document.getElementById('lightboxImg');
    const counterEl = document.getElementById('lightboxCounter');
    const prevBtn = document.getElementById('lightboxPrevBtn');
    const nextBtn = document.getElementById('lightboxNextBtn');

    if (imgEl) {
        imgEl.src = currentGallery[currentGalleryIndex];
    }
    if (counterEl) {
        counterEl.textContent = (currentGalleryIndex + 1) + ' / ' + currentGallery.length;
        counterEl.style.display = currentGallery.length > 1 ? 'inline-block' : 'none';
    }
    if (prevBtn) {
        prevBtn.style.display = currentGallery.length > 1 ? 'flex' : 'none';
    }
    if (nextBtn) {
        nextBtn.style.display = currentGallery.length > 1 ? 'flex' : 'none';
    }
}

document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightbox');
    if (!modal || modal.style.display === 'none') return;
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowLeft') {
        prevLightbox();
    } else if (e.key === 'ArrowRight') {
        nextLightbox();
    }
});

document.addEventListener('click', function(e) {
    const profileWrap = document.querySelector('.profile-wrap');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileWrap && profileDropdown && !profileWrap.contains(e.target)) {
        profileDropdown.classList.remove('show');
        const button = profileWrap.querySelector('.profile-avatar');
        if (button) button.setAttribute('aria-expanded', 'false');
    }
    if (!e.target.closest('.post-menu-wrap')) {
        document.querySelectorAll('.post-menu-dropdown.show').forEach(function (menu) {
            menu.classList.remove('show');
            const btn = menu.closest('.post-menu-wrap')?.querySelector('.post-menu-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }
    const nav = document.getElementById('primaryNavigation');
    const toggle = document.querySelector('.mobile-menu-toggle');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target) && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation menu');
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifDropdown && notifDropdown.classList.contains('show')) {
            notifDropdown.classList.remove('show');
            const btn = document.querySelector('.notif-bell-btn');
            if (btn) { btn.setAttribute('aria-expanded', 'false'); btn.focus(); }
        }
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileDropdown && profileDropdown.classList.contains('show')) {
            profileDropdown.classList.remove('show');
            const btn = document.querySelector('.profile-avatar');
            if (btn) { btn.setAttribute('aria-expanded', 'false'); btn.focus(); }
        }
        const openPostMenu = document.querySelector('.post-menu-dropdown.show');
        if (openPostMenu) {
            openPostMenu.classList.remove('show');
            const btn = openPostMenu.closest('.post-menu-wrap')?.querySelector('.post-menu-btn');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
                btn.focus();
            }
        }
        const ackDialog = document.getElementById('acknowledgementsDialog');
        if (ackDialog && ackDialog.open) {
            ackDialog.close();
        }
        const nav = document.getElementById('primaryNavigation');
        const toggle = document.querySelector('.mobile-menu-toggle');
        if (nav && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open navigation menu');
                toggle.focus();
            }
        }
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const deleteDialog = document.getElementById('deleteConfirmDialog');
    const confirmDeleteLink = document.getElementById('confirmDeleteLink');
    const cancelDelete = document.querySelector('[data-delete-cancel]');
    document.querySelectorAll('[data-delete-confirm]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!deleteDialog || typeof deleteDialog.showModal !== 'function') return;
            event.preventDefault();
            document.querySelectorAll('.post-menu-dropdown.show').forEach(function (menu) {
                menu.classList.remove('show');
                const btn = menu.closest('.post-menu-wrap')?.querySelector('.post-menu-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
            confirmDeleteLink.href = link.href;
            deleteDialog.showModal();
        });
    });
    if (cancelDelete && deleteDialog) {
        cancelDelete.addEventListener('click', function () {
            deleteDialog.close();
        });
    }
    if (deleteDialog) {
        deleteDialog.addEventListener('click', function (event) {
            if (event.target === deleteDialog) deleteDialog.close();
        });
    }

    const ackDialog = document.getElementById('acknowledgementsDialog');
    const closeAck = document.querySelector('[data-ack-close]');
    if (closeAck && ackDialog) {
        closeAck.addEventListener('click', function () {
            ackDialog.close();
        });
    }
    if (ackDialog) {
        ackDialog.addEventListener('click', function (event) {
            if (event.target === ackDialog) ackDialog.close();
        });
    }

    const toggle = document.querySelector('.mobile-menu-toggle');
    const navigation = document.getElementById('primaryNavigation');
    if (!toggle || !navigation) return;
    toggle.addEventListener('click', function () {
        const open = navigation.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    });
});
</script>