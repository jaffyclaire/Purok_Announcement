<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/require_login.php';
require_once __DIR__ . '/../classes/Event.php';

$database = new Database();
$db = $database->connect();

$eventModel = new Event($db);
$upcomingEvents = $eventModel->getAllUpcoming();
$pastEvents = $eventModel->getPastEvents();

require_once 'header.php';
?>

<div class="feed-column">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Community Events</h1>
            <p class="text-muted mb-0">Upcoming gatherings, clean-ups, and assemblies.</p>
        </div>
        <?php if ($_SESSION['role'] === 'Officer'): ?>
            <a href="add_event.php" class="btn btn-success btn-add-pill">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Add Event</span>
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success" role="status">Event added successfully and notifications sent.</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success" role="status">Event updated successfully.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success" role="status">Event deleted successfully.</div>
    <?php endif; ?>

    <section class="mb-5">
        <h2 class="h4 mb-3">Upcoming Events (<?php echo count($upcomingEvents); ?>)</h2>

        <?php if (empty($upcomingEvents)): ?>
            <div class="card post-card">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <h4>No upcoming events</h4>
                    <p class="mb-0">New community events will be listed here when scheduled.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="feed-grid">
                <?php foreach ($upcomingEvents as $ev): ?>
                    <?php
                    $timestamp = strtotime($ev['event_date']);
                    $month = date('M', $timestamp);
                    $day = date('d', $timestamp);
                    $time = date('g:i A', $timestamp);
                    $fullDate = date('F d, Y \a\t g:i A', $timestamp);
                    ?>
                    <article class="card post-card event-card">
                        <div class="card-body">
                            <div class="post-header-row">
                                <div class="event-date-badge" aria-label="Event date: <?php echo $fullDate; ?>">
                                    <span class="event-date-month"><?php echo $month; ?></span>
                                    <span class="event-date-day"><?php echo $day; ?></span>
                                </div>
                                <div class="post-author-info">
                                    <h3 class="post-title mb-1"><?php echo htmlspecialchars($ev['title']); ?></h3>
                                    <div class="event-meta-line">
                                        <span class="event-meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                            <span><?php echo $time; ?></span>
                                        </span>
                                        <?php if (!empty($ev['location'])): ?>
                                            <span class="meta-sep" aria-hidden="true">&middot;</span>
                                            <span class="event-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s-8-7.5-8-12a8 8 0 1 1 16 0c0 4.5-8 12-8 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                                <span><?php echo htmlspecialchars($ev['location']); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($_SESSION['role'] === 'Officer'): ?>
                                    <div class="post-menu-wrap">
                                        <button type="button" class="post-menu-btn" aria-label="More actions" aria-haspopup="true" aria-expanded="false" onclick="togglePostMenu(this)">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                                                <circle cx="5" cy="12" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="19" cy="12" r="2"/>
                                            </svg>
                                        </button>
                                        <div class="post-menu-dropdown" role="menu">
                                            <a href="update_event.php?id=<?php echo $ev['event_id']; ?>" class="post-menu-item" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 5 5 5M4 20l4.2-.8L19 8.4a2.1 2.1 0 0 0-3-3L5.2 16.2 4 20Z"/></svg>
                                                <span>Edit</span>
                                            </a>
                                            <a href="delete_event.php?id=<?php echo $ev['event_id']; ?>" class="post-menu-item post-menu-item-delete" role="menuitem" data-delete-confirm>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 13h11l1-13M9 7V4h6v3"/></svg>
                                                <span>Delete</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($ev['image_path']) && file_exists(__DIR__ . '/../' . $ev['image_path'])): ?>
                                <div class="post-image-wrap">
                                    <img src="../<?php echo htmlspecialchars($ev['image_path']); ?>" alt="<?php echo htmlspecialchars($ev['title']); ?>" class="post-image" style="max-height: 320px; object-fit: cover;">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ev['description'])): ?>
                                <p class="post-details mb-0"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="past-events-section mt-5 pt-3 border-top">
        <h2 class="h5 text-muted mb-3">Past Events</h2>
        <?php if (empty($pastEvents)): ?>
            <p class="text-muted small">No past events recorded.</p>
        <?php else: ?>
            <div class="feed-grid">
                <?php foreach ($pastEvents as $ev): ?>
                    <?php
                    $timestamp = strtotime($ev['event_date']);
                    $month = date('M', $timestamp);
                    $day = date('d', $timestamp);
                    $time = date('g:i A', $timestamp);
                    ?>
                    <article class="card post-card event-card past-event-card">
                        <div class="card-body py-3">
                            <div class="post-header-row align-items-center mb-0">
                                <div class="event-date-badge event-date-badge-muted" aria-label="Past event date: <?php echo date('F d, Y', $timestamp); ?>">
                                    <span class="event-date-month"><?php echo $month; ?></span>
                                    <span class="event-date-day"><?php echo $day; ?></span>
                                </div>
                                <div class="post-author-info">
                                    <h3 class="h6 mb-1 text-muted"><?php echo htmlspecialchars($ev['title']); ?></h3>
                                    <div class="event-meta-line text-muted small">
                                        <span><?php echo date('M d, Y', $timestamp); ?> at <?php echo $time; ?></span>
                                        <?php if (!empty($ev['location'])): ?>
                                            <span class="meta-sep" aria-hidden="true">&middot;</span>
                                            <span><?php echo htmlspecialchars($ev['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($_SESSION['role'] === 'Officer'): ?>
                                    <div class="post-menu-wrap">
                                        <button type="button" class="post-menu-btn" aria-label="More actions" aria-haspopup="true" aria-expanded="false" onclick="togglePostMenu(this)">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                                                <circle cx="5" cy="12" r="2"/>
                                                <circle cx="12" cy="12" r="2"/>
                                                <circle cx="19" cy="12" r="2"/>
                                            </svg>
                                        </button>
                                        <div class="post-menu-dropdown" role="menu">
                                            <a href="update_event.php?id=<?php echo $ev['event_id']; ?>" class="post-menu-item" role="menuitem">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 5 5 5M4 20l4.2-.8L19 8.4a2.1 2.1 0 0 0-3-3L5.2 16.2 4 20Z"/></svg>
                                                <span>Edit</span>
                                            </a>
                                            <a href="delete_event.php?id=<?php echo $ev['event_id']; ?>" class="post-menu-item post-menu-item-delete" role="menuitem" data-delete-confirm>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6m4-6v6M5.5 7l1 13h11l1-13M9 7V4h6v3"/></svg>
                                                <span>Delete</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'footer.php'; ?>

