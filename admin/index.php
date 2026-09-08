<?php
require_once "../config.php";
requireLogin();
if(!allowed(['admin'])){http_response_code(403);die("Access denied.");}
$stats=[
 ['Users',$pdo->query('SELECT COUNT(*) FROM module_users')->fetchColumn()],
 ['Departments',$pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn()],
 ['Courses',$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn()],
 ['Notices',$pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn()]
];
$services=moduleServices('admin',user()['role']);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Dashboard</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><div class="app"><aside class="sidebar"><?php include "../partials/sidebar.php";?></aside><main class="main"><header class="topbar"><div class="module-tag">Workspace / <b>Admin</b></div><div class="top-user"><div class="avatar"><?=strtoupper(substr(user()['name'],0,2))?></div><?=htmlspecialchars(user()['name'])?></div></header><section class="content"><div class="page-head"><div><p class="eyebrow">ADMIN MODULE</p><h1>Admin Dashboard</h1><p>Manage the services assigned to your role.</p></div></div><div class="stats"><?php foreach($stats as $s):?><div class="stat"><span><?=htmlspecialchars($s[0])?></span><strong><?=htmlspecialchars($s[1])?></strong></div><?php endforeach;?></div><div class="grid"><section class="card"><h2>Your Modules</h2><?php foreach($services as $s):?><a class="service" href="<?=htmlspecialchars($s['path'])?>"><div class="service-icon">◆</div><div><b><?=htmlspecialchars($s['label'])?></b><small><?=htmlspecialchars($s['description'])?></small></div></a><?php endforeach;?></section></div></section></main></div></body></html>
