<?php
$u=user(); $m=$u['module'] ?? '';
?>
<div class="brand"><div class="brand-mark">VC</div><div><strong>College ERP</strong><small><?=ucfirst($m)?> Workspace</small></div></div>
<nav>
<?php if($m==='admin'): ?>
<p class="nav-label">ADMINISTRATION</p>
<a href="index.php">⌂ Dashboard</a><?php foreach(moduleServices($m, $u['role'] ?? '') as $service): ?><a href="<?=htmlspecialchars($service['path'])?>">◆ <?=htmlspecialchars($service['label'])?></a><?php endforeach; ?>
<?php elseif($m==='finance'): ?>
<p class="nav-label">FINANCE</p>
<a href="index.php">⌂ Dashboard</a><?php foreach(moduleServices($m, $u['role'] ?? '') as $service): ?><a href="<?=htmlspecialchars($service['path'])?>">◆ <?=htmlspecialchars($service['label'])?></a><?php endforeach; ?>
<?php elseif($m==='dean'): ?>
<p class="nav-label">DEAN'S OFFICE</p>
<a href="index.php">⌂ Dashboard</a><?php foreach(moduleServices($m, $u['role'] ?? '') as $service): ?><a href="<?=htmlspecialchars($service['path'])?>">◆ <?=htmlspecialchars($service['label'])?></a><?php endforeach; ?>
<?php elseif($m==='students'): ?>
<p class="nav-label">STUDENT PORTAL</p>
<a href="index.php">⌂ My Dashboard</a><?php foreach(moduleServices($m, $u['role'] ?? '') as $service): ?><a href="<?=htmlspecialchars($service['path'])?>">◆ <?=htmlspecialchars($service['label'])?></a><?php endforeach; ?>
<?php endif; ?>
</nav>
<div class="user-foot"><div class="avatar"><?=strtoupper(substr($u['name']??'U',0,2))?></div><div><b><?=htmlspecialchars($u['name']??'User')?></b><small><?=htmlspecialchars(str_replace('_',' ',ucfirst($u['role']??'')))?></small></div><a href="../logout.php">↪</a></div>