<?php
require_once "../config.php";
requireLogin();
if (!allowed(['admin', 'finance'])) {
    http_response_code(403);
    die("Access denied.");
}

$message = '';
$messageClass = '';

function adminNoticeAuthorId(PDO $pdo, array $currentUser): int {
    $name = trim((string)($currentUser['name'] ?? ''));
    if ($name !== '') {
        $stmt = $pdo->prepare('SELECT admin_id FROM admin_users WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if ($id) { return (int)$id; }
    }
    $id = $pdo->query("SELECT admin_id FROM admin_users WHERE status='active' ORDER BY admin_id LIMIT 1")->fetchColumn();
    if (!$id) { throw new RuntimeException('Create an active admin account before publishing notices.'); }
    return (int)$id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'publish';
    try {
        if ($action === 'delete') {
            $noticeId = filter_input(INPUT_POST, 'notice_id', FILTER_VALIDATE_INT);
            if (!$noticeId) { throw new InvalidArgumentException('Invalid notice selected.'); }
            $stmt = $pdo->prepare('DELETE FROM notices WHERE notice_id=?');
            $stmt->execute([$noticeId]);
            $message = $stmt->rowCount() ? 'Notice deleted successfully.' : 'Notice was not found.';
            $messageClass = $stmt->rowCount() ? 'alert-success' : 'alert-danger';
        } else {
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $audience = in_array($_POST['target_audience'] ?? '', ['all', 'students', 'staff'], true) ? $_POST['target_audience'] : 'all';
            if ($title === '' || $content === '') { throw new InvalidArgumentException('Enter both a title and notice content.'); }
            $stmt = $pdo->prepare('INSERT INTO notices (title, content, target_audience, posted_by, posted_on) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)');
            $stmt->execute([$title, $content, $audience, adminNoticeAuthorId($pdo, user() ?? [])]);
            $message = 'Notice published successfully.';
            $messageClass = 'alert-success';
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageClass = 'alert-danger';
    }
}

$notices = $pdo->query("SELECT n.notice_id, n.title, n.content, n.posted_on, u.full_name FROM notices n LEFT JOIN admin_users u ON u.admin_id = n.posted_by ORDER BY n.posted_on DESC")->fetchAll();
$noticeDirectory = $pdo->query("SELECT notice_id, title, posted_on FROM notices ORDER BY posted_on DESC")->fetchAll();
$selectedNoticeId = filter_input(INPUT_GET, 'notice_id', FILTER_VALIDATE_INT);
$selectedNotice = null;
if ($selectedNoticeId) {
    foreach ($noticeDirectory as $notice) {
        if ((int)$notice['notice_id'] === (int)$selectedNoticeId) {
            $selectedNotice = $notice;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Notices | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:block; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        .notice { border:1px solid #e2e8f0; border-left:4px solid #2563eb; padding:16px; border-radius:8px; margin-top:16px; }
        .notice-form { display:grid; gap:10px; margin:20px 0 24px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; }
        .notice-form input, .notice-form textarea, .notice-form select { width:100%; box-sizing:border-box; padding:11px; border:1px solid #cbd5e1; border-radius:6px; font:inherit; }
        .notice-form textarea { min-height:110px; resize:vertical; }
        .publish-button, .delete-button { border:0; border-radius:6px; padding:9px 14px; font-weight:700; cursor:pointer; }
        .clear-button { background:#e2e8f0; color:#1e293b; border:0; border-radius:6px; padding:9px 14px; font-weight:700; cursor:pointer; }
        .publish-button { background:#1e3d73; color:#fff; }
        .delete-button { margin-top:10px; background:#fee2e2; color:#b91c1c; }
        @media(max-width:700px){.sidebar{position:static;width:100%;min-height:auto;transform:none}.main{margin-left:0;width:100%}.content{padding:20px}.topbar{padding:0 18px}}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Notices</b></div><div class="topbar-logo"><div class="brand-mark">VC</div><span>College ERP</span></div></header>
        <div class="card">
            <h1>Notice Bulletin</h1>
            <p class="text-muted">Publish and manage notices for the college community.</p>
            <?php if ($message): ?><div class="alert <?=htmlspecialchars($messageClass)?>"><?=htmlspecialchars($message)?></div><?php endif; ?>

            <div style="margin-top:18px; margin-bottom:12px;">
                <label for="noticeDirectorySelector" style="display:block; margin-bottom:8px; font-size: 14px; font-weight: 600; color: #4a5568;">Notice directory</label>
                <select id="noticeDirectorySelector" style="width:100%; max-width:420px; padding:10px 12px; border:1px solid #cbd5e0; border-radius:6px; background:#fff; font-size:14px;" onchange="window.location.href = 'notices.php?notice_id=' + this.value">
                    <option value="">Select a notice</option>
                    <?php foreach ($noticeDirectory as $notice): ?>
                        <option value="<?= (int)$notice['notice_id'] ?>" <?= $selectedNoticeId && (int)$notice['notice_id'] === (int)$selectedNoticeId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($notice['title']) ?> (<?= htmlspecialchars(date('d M Y', strtotime($notice['posted_on']))) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="noticeDirectoryDetail" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; color:#4a5568; min-height:70px; display:flex; align-items:center;">
                <?php if ($selectedNotice): ?>
                    <strong><?= htmlspecialchars($selectedNotice['title']) ?></strong><br>
                    Posted: <?= htmlspecialchars(date('d M Y', strtotime($selectedNotice['posted_on']))) ?>
                <?php else: ?>
                    Select a notice to view its posting date.
                <?php endif; ?>
            </div>

            <form method="post" class="notice-form">
                <input type="text" name="title" placeholder="Notice title" required>
                <textarea name="content" placeholder="Write notice content..." required></textarea>
                <select name="target_audience"><option value="all">Everyone</option><option value="students">Students</option><option value="staff">Staff</option></select>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="reset" class="clear-button">Clear</button>
                    <button type="submit" class="publish-button">Publish Notice</button>
                </div>
            </form>
            <?php foreach ($notices as $notice): ?>
                <div class="notice">
                    <h3><?= htmlspecialchars($notice['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
                    <small>Posted by <?= htmlspecialchars($notice['full_name'] ?? 'System') ?> on <?= htmlspecialchars(date('d M Y', strtotime($notice['posted_on']))) ?></small>
                    <form method="post" onsubmit="return confirm('Delete this notice?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="notice_id" value="<?= (int)$notice['notice_id'] ?>"><button type="submit" class="delete-button">Delete</button></form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
