<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);die("Access denied.");}

// 1. Fetch Student Details
$student=currentStudent($pdo);

requireCompleteStudentProfile($student);

// 2. Fetch Targeted Notices (Ordered by latest first)
$notices_list = [];
if ($student) {
    $notices_stmt = $pdo->prepare("SELECT * FROM notices WHERE target_audience IN ('all', 'students') ORDER BY posted_on DESC");
    $notices_stmt->execute();
    $notices_list = $notices_stmt->fetchAll();
}

$notices = count($notices_list);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/gif" href="<?=htmlspecialchars(appAssetPath('Images/logo.gif'))?>"><title>Notices & Announcements</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
    .notice-item { border-left: 3px solid #1e3a8a; padding: 15px; margin-bottom: 20px; background: rgba(0,0,0,0.01); border-radius: 0 4px 4px 0; }
    .notice-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: #64748b; margin-bottom: 8px; font-weight: 500; }
    .notice-title { margin: 0 0 8px 0; font-size: 1.1rem; color: #1e293b; }
    .notice-body { font-size: 0.9rem; line-height: 1.5; color: #334155; margin: 0; }
    .badge-pinned { background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
</style>
</head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Notices</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">COMMUNICATION HUB</p><h1>Campus Announcements</h1><p>Stay up to date with official broadcasts, administrative directives, and holiday schedules.</p></div></div>

<div class="stats">
    <div class="stat"><span>Active Announcements</span><strong><?=$notices?></strong></div>
    <div class="stat"><span>Target Audience</span><strong>Students / All</strong></div>
</div>

<div class="grid" style="grid-template-columns: 1fr;">
    <section class="card">
        <h2>Official Notice Board</h2>
        
        <?php if(!empty($notices_list)): ?>
            <?php foreach($notices_list as $item): ?>
                <div class="notice-item" style="<?= (isset($item['is_pinned']) && $item['is_pinned']) ? 'border-left-color: #ef4444; background: #fffaf0;' : '' ?>">
                    <div class="notice-meta">
                        <span>Posted on: <b><?= date('d M Y, h:i A', strtotime($item['posted_on'])) ?></b></span>
                        <?php if(isset($item['is_pinned']) && $item['is_pinned']): ?>
                            <span class="badge-pinned">High Priority</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="notice-title"><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="notice-body"><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="service">
                <div style="color:#666; font-size:0.85rem;">There are no active notices or announcements listed on your board right now.</div>
            </div>
        <?php endif; ?>
    </section>
</div>

</section></main></div></body></html>
