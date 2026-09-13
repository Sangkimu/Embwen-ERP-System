<?php
require_once "../config.php";
requireLogin();
if (!allowed(['dean', 'admin'])) {
    http_response_code(403);
    die("Access denied.");
}

$message = '';
$messageClass = '';

function deanNoticeAuthorId(PDO $pdo, array $user): int {
    $fullName = trim((string)($user['name'] ?? ''));
    if ($fullName !== '') {
        $stmt = $pdo->prepare('SELECT admin_id FROM admin_users WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$fullName]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }
    }

    $username = 'dean_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower((string)($user['username'] ?? 'dean')));
    $stmt = $pdo->prepare('SELECT admin_id FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int)$id;
    }

    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, full_name, role, email, phone, status) VALUES (?, ?, ?, ?, ?, NULL, "active")');
    $passwordHash = password_hash('dean-content-access', PASSWORD_DEFAULT);
    $email = trim((string)($user['email'] ?? ''));
    $stmt->execute([
        $username . '_' . (int)($user['id'] ?? 0),
        $passwordHash,
        $fullName !== '' ? $fullName : 'Dean Office',
        'staff',
        $email !== '' ? $email : null,
    ]);

    return (int)$pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $audience = in_array($_POST['target_audience'] ?? '', ['all', 'students', 'staff'], true) ? $_POST['target_audience'] : 'all';

    if ($title === '' || $content === '') {
        $message = 'Please enter both a title and content for the notice.';
        $messageClass = 'alert-danger';
    } else {
        try {
            $authorId = deanNoticeAuthorId($pdo, user() ?? []);
            $stmt = $pdo->prepare('INSERT INTO notices (title, content, target_audience, posted_by, posted_on) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$title, $content, $audience, $authorId]);
            $message = 'Notice published successfully to the campus content board.';
            $messageClass = 'alert-success';
        } catch (Throwable $e) {
            $message = 'Unable to publish notice: ' . $e->getMessage();
            $messageClass = 'alert-danger';
        }
    }
}

$notices = $pdo->query("SELECT n.notice_id, n.title, n.content, n.target_audience, n.posted_on, COALESCE(u.full_name, 'Dean Office') AS author_name FROM notices n LEFT JOIN admin_users u ON u.admin_id = n.posted_by ORDER BY n.posted_on DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>">
    <title>Dean Content & Notices</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f7fb; }
        .app { display:grid; grid-template-columns: 260px 1fr; min-height:100vh; }
        .main { padding:30px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; }
        .notice-grid { display:grid; grid-template-columns: 1.1fr 0.9fr; gap:24px; }
        .notice-list { display:grid; gap:16px; }
        .notice-item { border:1px solid #e2e8f0; border-left:4px solid #2563eb; border-radius:8px; padding:16px; background:#f8fafc; }
        .notice-item h3 { margin:0 0 8px; }
        .notice-item p { margin:0 0 10px; color:#334155; line-height:1.6; }
        .notice-meta { display:flex; justify-content:space-between; gap:12px; font-size:12px; color:#64748b; }
        .form { display:grid; gap:12px; }
        .form-control { width:100%; box-sizing:border-box; padding:12px; border:1px solid #cbd5e1; border-radius:6px; }
        textarea.form-control { min-height:150px; resize:vertical; }
        .btn { border:0; border-radius:6px; padding:11px 18px; font-weight:700; cursor:pointer; }
        .btn-primary { background:#1e3d73; color:#fff; }
        .badge { display:inline-block; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; background:#dbeafe; color:#1d4ed8; }
        @media(max-width:900px){ .notice-grid{grid-template-columns:1fr;} .app{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar"><?php include "../partials/sidebar.php"; ?></aside>
    <main class="main">
        <header class="topbar"><div class="module-tag">Workspace / <b>Content</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header>
        <section class="content">
            <div class="page-head">
                <div>
                    <p class="eyebrow">DEAN MODULE</p>
                    <h1>Campus Content & Notices</h1>
                    <p>Share academic notices, student updates, and official announcements with the wider campus community.</p>
                </div>
            </div>

            <?php if ($message): ?><div class="alert <?=htmlspecialchars($messageClass)?>"><?=htmlspecialchars($message)?></div><?php endif; ?>

            <div class="notice-grid">
                <section class="card">
                    <h2>Published Content</h2>
                    <div class="notice-list">
                        <?php if (!$notices): ?>
                            <div class="service"><small style="color:#666;">No notices have been published yet.</small></div>
                        <?php else: ?>
                            <?php foreach ($notices as $notice): ?>
                                <article class="notice-item">
                                    <div class="notice-meta">
                                        <span><b><?=htmlspecialchars($notice['author_name'])?></b></span>
                                        <span class="badge"><?=htmlspecialchars($notice['target_audience'])?></span>
                                    </div>
                                    <h3><?=htmlspecialchars($notice['title'])?></h3>
                                    <p><?=nl2br(htmlspecialchars($notice['content']))?></p>
                                    <div class="notice-meta">
                                        <span><?=htmlspecialchars(date('d M Y', strtotime($notice['posted_on'])))?></span>
                                        <span><?=htmlspecialchars(date('h:i A', strtotime($notice['posted_on'])))?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="card">
                    <h2>Publish Notice</h2>
                    <form class="form" method="post">
                        <div>
                            <label for="title">Title</label>
                            <input id="title" class="form-control" type="text" name="title" placeholder="College update / academic directive" required>
                        </div>
                        <div>
                            <label for="target_audience">Audience</label>
                            <select id="target_audience" class="form-control" name="target_audience">
                                <option value="all">All</option>
                                <option value="students">Students</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div>
                            <label for="content">Content</label>
                            <textarea id="content" class="form-control" name="content" placeholder="Write the notice content here..." required></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Publish Notice</button>
                    </form>
                </section>
            </div>
        </section>
    </main>
</div>
</body>
</html>
