<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/Announcement.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();

$announcement = new Announcement($db);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$records = $announcement->getAnnouncements($currentUserId);

$eventModel = new Event($db);
$upcomingEvents = $eventModel->getUpcomingStrip(3);

require_once 'header.php';

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

<div class="feed-column">
    <?php if (!empty($upcomingEvents)): ?>
        <section class="card events-strip-card mb-4" aria-label="Upcoming community events">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="events-strip-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <h2 class="h6 mb-0 fw-bold">Upcoming Events</h2>
                    </div>
                    <a href="events.php" class="events-strip-all">View all &rarr;</a>
                </div>
                <div class="events-strip-list">
                    <?php foreach ($upcomingEvents as $ev): ?>
                        <?php
                        $ts = strtotime($ev['event_date']);
                        $m = date('M', $ts);
                        $d = date('d', $ts);
                        $t = date('g:i A', $ts);
                        ?>
                        <a href="events.php" class="events-strip-item">
                            <div class="event-mini-date">
                                <span class="mini-month"><?php echo $m; ?></span>
                                <span class="mini-day"><?php echo $d; ?></span>
                            </div>
                            <div class="events-strip-content">
                                <span class="events-strip-title"><?php echo htmlspecialchars($ev['title']); ?></span>
                                <span class="events-strip-sub">
                                    <span><?php echo $t; ?></span>
                                    <?php if (!empty($ev['location'])): ?>
                                        &middot; <span><?php echo htmlspecialchars($ev['location']); ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="feed-toolbar">
        <form method="GET" action="search_announcements.php" class="search-form" id="feedSearch">
            <div class="search-input-wrap">
                <button type="submit" class="search-icon-btn" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/>
                        <path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
                <input type="text" name="keyword" id="searchKeyword" class="form-control search-input" placeholder="Search announcements..." aria-label="Search announcements" autocomplete="off">
                <button type="button" class="search-clear-btn" id="searchClearBtn" aria-label="Clear search" style="display: none;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </form>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Officer'): ?>
            <a href="add_announcement.php" class="btn btn-success btn-add-pill">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Add Announcement</span>
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
        <div class="alert alert-danger" role="alert">You can only edit or delete your own posts.</div>
    <?php elseif (isset($_GET['added'])): ?>
        <div class="alert alert-success" role="status">Announcement added successfully.</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success" role="status">Announcement updated successfully.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" role="status">Announcement deleted successfully.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
        <div class="alert alert-danger" role="alert">Announcement deletion failed. No changes were saved.</div>
    <?php endif; ?>

    <div class="feed">
        <?php if (empty($records)): ?>
            <div class="card post-card">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 21v-7h6v7M8 10h.01M12 10h.01M16 10h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <h4>No announcements yet</h4>
                    <p class="mb-0">Community announcements will appear here when they are posted.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="feed-grid">
            <?php foreach ($records as $row): ?>
                <article class="card post-card" id="announcement-<?php echo $row['announcement_id']; ?>">
                    <div class="card-body">
                        <div class="post-header-row">
                            <div class="post-avatar avatar-container-md" style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                                <?php if (!empty($row['avatar_path']) && file_exists(__DIR__ . '/../' . $row['avatar_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['avatar_path']); ?>" alt="<?php echo htmlspecialchars($row['posted_by_name'] ?? ''); ?>" class="avatar-img avatar-md post-avatar-img" width="44" height="44" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; display: block;">
                                <?php else: ?>
                                    <span><?php echo getInitials($row['posted_by_name'] ?? 'Unknown'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="post-author-info">
                                <div class="post-author-name"><?php echo htmlspecialchars($row['posted_by_name'] ?? 'Unknown'); ?></div>
                                <div class="post-meta-line">
                                    <?php if (!empty($row['is_urgent'])): ?>
                                        <span class="urgent-text">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <span>Urgent</span>
                                        </span>
                                        <span class="meta-sep" aria-hidden="true">&middot;</span>
                                    <?php endif; ?>
                                    <span><?php echo date('M d, Y \a\t g:i A', strtotime($row['date_posted'])); ?></span>
                                    <span class="meta-sep" aria-hidden="true">&middot;</span>
                                    <span><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></span>
                                </div>
                            </div>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Officer' && !empty($row['user_id']) && (int)$row['user_id'] === (int)($_SESSION['user_id'] ?? 0)): ?>
                                <div class="post-menu-wrap">
                                    <button type="button" class="post-menu-btn" aria-label="More actions" aria-haspopup="true" aria-expanded="false" onclick="togglePostMenu(this)">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                                            <circle cx="5" cy="12" r="2"/>
                                            <circle cx="12" cy="12" r="2"/>
                                            <circle cx="19" cy="12" r="2"/>
                                        </svg>
                                    </button>
                                    <div class="post-menu-dropdown" role="menu">
                                        <a href="update_announcement.php?id=<?php echo $row['announcement_id']; ?>" class="post-menu-item" role="menuitem">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 5 5 5M4 20l4.2-.8L19 8.4a2.1 2.1 0 0 0-3-3L5.2 16.2 4 20Z"/></svg>
                                            <span>Edit</span>
                                        </a>
                                        <a href="delete_announcement.php?id=<?php echo $row['announcement_id']; ?>" class="post-menu-item post-menu-item-delete" role="menuitem" data-delete-confirm>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 13h11l1-13M9 7V4h6v3"/></svg>
                                            <span>Delete</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h2 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h2>

                        <p class="post-details mb-0"><?php echo nl2br(htmlspecialchars($row['details'])); ?></p>

                        <?php
                        $cardImages = [];
                        if (!empty($row['images']) && is_array($row['images'])) {
                            foreach ($row['images'] as $imgItem) {
                                if (!empty($imgItem['file_path']) && file_exists(__DIR__ . '/../' . $imgItem['file_path'])) {
                                    $cardImages[] = $imgItem['file_path'];
                                }
                            }
                        } elseif (!empty($row['image_path']) && file_exists(__DIR__ . '/../' . $row['image_path'])) {
                            $cardImages[] = $row['image_path'];
                        }
                        $imgCount = count($cardImages);
                        ?>

                        <?php if ($imgCount > 0): ?>
                            <?php
                            $galleryUrls = array_map(function($p) { return '../' . $p; }, $cardImages);
                            $galleryJson = htmlspecialchars(json_encode($galleryUrls), ENT_QUOTES, 'UTF-8');
                            ?>
                            <?php if ($imgCount === 1): ?>
                                <div class="post-media-wrap media-grid-1">
                                    <div class="media-tile" onclick='openLightbox(<?php echo $galleryJson; ?>, 0)' role="button" tabindex="0" aria-label="View image full size">
                                        <img src="../<?php echo htmlspecialchars($cardImages[0]); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="media-img" loading="lazy">
                                    </div>
                                </div>
                            <?php elseif ($imgCount === 2): ?>
                                <div class="post-media-wrap media-grid-2">
                                    <?php foreach ($cardImages as $idx => $imgPath): ?>
                                        <div class="media-tile" onclick='openLightbox(<?php echo $galleryJson; ?>, <?php echo $idx; ?>)' role="button" tabindex="0" aria-label="View image <?php echo $idx + 1; ?> full size">
                                            <img src="../<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="media-img" loading="lazy">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($imgCount === 3): ?>
                                <div class="post-media-wrap media-grid-3">
                                    <?php foreach ($cardImages as $idx => $imgPath): ?>
                                        <div class="media-tile <?php echo $idx === 0 ? 'media-tile-lead' : 'media-tile-sub'; ?>" onclick='openLightbox(<?php echo $galleryJson; ?>, <?php echo $idx; ?>)' role="button" tabindex="0" aria-label="View image <?php echo $idx + 1; ?> full size">
                                            <img src="../<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="media-img" loading="lazy">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="post-media-wrap media-grid-4">
                                    <?php for ($idx = 0; $idx < 4; $idx++): ?>
                                        <div class="media-tile <?php echo $idx === 0 ? 'media-tile-lead' : 'media-tile-sub'; ?>" onclick='openLightbox(<?php echo $galleryJson; ?>, <?php echo $idx; ?>)' role="button" tabindex="0" aria-label="View image <?php echo $idx + 1; ?> full size">
                                            <img src="../<?php echo htmlspecialchars($cardImages[$idx]); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="media-img" loading="lazy">
                                            <?php if ($idx === 3 && $imgCount > 4): ?>
                                                <div class="media-more-overlay" aria-hidden="true">+<?php echo ($imgCount - 4); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="post-card-footer mt-3 pt-2 d-flex align-items-center">
                            <div class="reaction-wrap" data-announcement-id="<?php echo $row['announcement_id']; ?>">
                                <button type="button" class="btn-heart <?php echo !empty($row['user_reacted']) ? 'is-active' : ''; ?>" aria-label="<?php echo !empty($row['user_reacted']) ? 'Remove acknowledgement' : 'Acknowledge announcement'; ?>" onclick="toggleReaction(this, <?php echo $row['announcement_id']; ?>)">
                                    <svg viewBox="0 0 24 24" class="heart-icon" aria-hidden="true">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </button>
                                <?php if ($_SESSION['role'] === 'Officer'): ?>
                                    <button type="button" class="reaction-count-btn" onclick="openAcknowledgeList(<?php echo $row['announcement_id']; ?>)" title="View acknowledgements">
                                        <span class="reaction-count"><?php echo (int)($row['reaction_count'] ?? 0); ?></span>
                                    </button>
                                <?php else: ?>
                                    <span class="reaction-count-static">
                                        <span class="reaction-count"><?php echo (int)($row['reaction_count'] ?? 0); ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
