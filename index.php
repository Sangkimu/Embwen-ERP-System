<?php
require_once "config.php";
if(!empty($_SESSION['user'])){
    $isLoggedIn=true;
    $portalLink=$_SESSION['user']['home'];
    $portalLabel='Open my workspace';
} else {
    $isLoggedIn=false;
    $portalLink='login.php';
    $portalLabel='Sign in';
}
$events=[
    ['12','SEP','Open Day','Tour the workshops, meet our tutors, and see student projects in action.','09:00 - 15:00'],
    ['21','SEP','Innovation Showcase','An afternoon of prototypes, practical ideas, and bold student thinking.','14:00 - 17:00'],
    ['04','OCT','Sports & Culture Day','A full day of teams, music, food, and the people who make campus feel like home.','08:30 - 18:00']
];
$gallery=[
    ['https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1200&q=85','Campus life','A place to learn, build, and belong.','tall'],
    ['https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1000&q=85','Make it real','Practical learning with room for curiosity.','wide'],
    ['https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1000&q=85','Work together','Ideas get better when people share them.','square'],
    ['https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1000&q=85','Find your people','Clubs, teams, studios, and new friendships.','square']
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Embwen College | Learn by doing</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
.nav-signup{padding:10px 14px;border:1px solid var(--line);border-radius:100px}.nav-signup:hover{background:#fff}.auth-actions{display:flex;gap:12px;margin-top:33px}.auth-actions .button{flex:1;justify-content:center}
.nav-links .nav-signup{display:inline-flex}
</style>
</head>
<body>
<div class="landing">
<nav class="site-nav">
<a class="logo" href="index.php"><span class="logo-mark">EC</span><span>Embwen College</span></a>
<div class="nav-links"><a href="#gallery">Campus life</a><a href="#events">Events</a><?php if($isLoggedIn):?><a class="nav-cta" href="<?=htmlspecialchars($portalLink)?>"><?=htmlspecialchars($portalLabel)?> <span aria-hidden="true">↗</span></a><?php else:?><a class="nav-signup" href="login.php?mode=register">Sign up</a><a class="nav-cta" href="login.php">Sign in <span aria-hidden="true">↗</span></a><?php endif;?></div>
</nav>
<main>
<section class="hero">
<div class="reveal"><p class="kicker">Vocational college · Est. 1998</p><h1>Learn with your hands. <em>Lead with purpose.</em></h1><p class="hero-copy">A practical, welcoming college for people who want to make useful things, solve real problems, and shape what comes next.</p><?php if($isLoggedIn):?><div class="hero-actions"><a class="button button-dark" href="<?=htmlspecialchars($portalLink)?>">Open my workspace <span>↗</span></a><a class="button button-light" href="#events">See events</a></div><?php else:?><div class="auth-actions"><a class="button button-dark" href="login.php?mode=register">Sign up <span>↗</span></a><a class="button button-light" href="login.php">Sign in</a></div><?php endif;?></div>
<div class="hero-art reveal delay"><img class="hero-photo" src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1100&q=85" alt="Students walking together across campus"><div class="hero-label">A place to grow</div><div class="hero-note">Come curious.<span>Leave capable.</span></div></div>
</section>
<div class="strip"><div class="strip-inner"><span>Hands-on learning</span><span>Community first</span><span>Skills for tomorrow</span><span>Everyone belongs here</span></div></div>
<section class="section" id="gallery"><div class="section-head"><h2>Life at Embwen</h2><p>The moments between classes matter too: the work, the laughter, the shared wins.</p></div><div class="gallery-grid"><?php foreach($gallery as $image):?><figure class="gallery-item"><img src="<?=htmlspecialchars($image[0])?>" alt="<?=htmlspecialchars($image[1])?>"><figcaption class="gallery-caption"><?=htmlspecialchars($image[2])?></figcaption></figure><?php endforeach;?></div></section>
<section class="events-section" id="events"><div class="section"><div class="section-head"><h2>On the calendar</h2><p>Open days, showcases, and campus traditions worth putting in your diary.</p></div><div class="event-list"><?php foreach($events as $event):?><article class="event"><div class="event-date"><strong><?=htmlspecialchars($event[0])?></strong><?=htmlspecialchars($event[1])?></div><div><h3><?=htmlspecialchars($event[2])?></h3><p><?=htmlspecialchars($event[3])?></p></div><div class="event-time"><?=htmlspecialchars($event[4])?></div></article><?php endforeach;?></div></div></section>
</main>
<footer class="footer"><strong>Embwen College</strong><span>Learn by doing, together.</span><a href="<?=htmlspecialchars($portalLink)?>"><?=htmlspecialchars($portalLabel)?> ↗</a></footer>
</div>
</body>
</html>
