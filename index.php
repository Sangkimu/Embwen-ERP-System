<?php
require_once "config.php";
if(!empty($_SESSION['user'])){
    $portalLink=$_SESSION['user']['home'];
    $portalLabel='Open my workspace';
} else {
    $portalLink='login.php';
    $portalLabel='Portal sign in';
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
:root{--ink:#172b2d;--paper:#f5f0e7;--coral:#e96f4d;--mint:#cbded1;--line:rgba(23,43,45,.16);--muted:#617071}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font-family:'DM Sans',sans-serif}a{color:inherit;text-decoration:none}.landing{overflow:hidden}.site-nav{max-width:1240px;margin:auto;padding:25px 32px;display:flex;align-items:center;justify-content:space-between}.logo{display:flex;align-items:center;gap:11px;font-family:'Space Grotesk',sans-serif;font-weight:700;letter-spacing:-.04em}.logo-mark{display:grid;place-items:center;width:38px;height:38px;background:var(--coral);color:#fff;font-size:14px;border-radius:50%}.nav-links{display:flex;align-items:center;gap:28px;font-size:13px;font-weight:600}.nav-links a:not(.nav-cta){color:var(--muted)}.nav-links a:not(.nav-cta):hover{color:var(--ink)}.nav-cta{padding:11px 17px;background:var(--ink);color:#fff;border-radius:100px}.hero{max-width:1240px;margin:auto;padding:66px 32px 94px;display:grid;grid-template-columns:1.04fr .96fr;gap:70px;align-items:center}.kicker{font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--coral);margin:0 0 20px}.hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(48px,7vw,92px);line-height:.94;letter-spacing:-.075em;max-width:650px;margin:0 0 27px}.hero h1 em{font-style:normal;color:var(--coral)}.hero-copy{color:var(--muted);font-size:17px;line-height:1.65;max-width:450px;margin:0}.hero-actions{display:flex;gap:12px;align-items:center;margin-top:33px}.button{display:inline-flex;align-items:center;gap:10px;padding:14px 19px;border-radius:100px;font-size:13px;font-weight:700}.button-dark{background:var(--ink);color:#fff}.button-light{border:1px solid var(--line)}.button-light:hover{background:#fff}.hero-art{position:relative;min-height:480px}.hero-photo{width:83%;height:420px;object-fit:cover;border-radius:210px 210px 12px 12px;display:block;margin-left:auto;filter:saturate(.9)}.hero-note{position:absolute;left:0;bottom:26px;background:var(--mint);width:166px;height:166px;border-radius:50%;display:grid;place-items:center;text-align:center;padding:30px;font-family:'Space Grotesk',sans-serif;font-size:18px;line-height:1.05;transform:rotate(-8deg)}.hero-note span{display:block;font-family:'DM Sans',sans-serif;font-size:10px;line-height:1.3;text-transform:uppercase;letter-spacing:.12em;margin-top:8px}.hero-label{position:absolute;right:3%;top:25px;padding:9px 13px;background:#fff;border-radius:100px;font-size:10px;text-transform:uppercase;letter-spacing:.12em;font-weight:700}.strip{border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:16px 32px;color:var(--muted);font-size:11px;letter-spacing:.15em;text-transform:uppercase}.strip-inner{max-width:1240px;margin:auto;display:flex;justify-content:space-between;gap:20px}.section{max-width:1240px;margin:auto;padding:110px 32px}.section-head{display:flex;justify-content:space-between;align-items:end;margin-bottom:38px}.section-head h2{font-family:'Space Grotesk',sans-serif;font-size:clamp(34px,4vw,55px);letter-spacing:-.065em;line-height:1;margin:0}.section-head p{max-width:320px;color:var(--muted);font-size:14px;line-height:1.6;margin:0}.gallery-grid{display:grid;grid-template-columns:1.1fr .9fr .9fr;grid-template-rows:250px 250px;gap:14px}.gallery-item{position:relative;overflow:hidden;border-radius:9px;background:#d7d0c5}.gallery-item:first-child{grid-row:span 2}.gallery-item:nth-child(2){grid-column:span 2}.gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;display:block}.gallery-item:hover img{transform:scale(1.04)}.gallery-caption{position:absolute;left:15px;bottom:14px;color:#fff;font-size:13px;font-weight:700;text-shadow:0 1px 12px #000}.events-section{background:var(--ink);color:#fff}.events-section .section{padding-top:90px;padding-bottom:100px}.events-section .section-head p{color:#b7c3c0}.event-list{border-top:1px solid rgba(255,255,255,.23)}.event{display:grid;grid-template-columns:120px 1fr 170px;gap:28px;align-items:center;border-bottom:1px solid rgba(255,255,255,.23);padding:23px 0}.event-date{font-family:'Space Grotesk',sans-serif;color:var(--mint);font-size:13px;letter-spacing:.06em}.event-date strong{font-size:37px;line-height:1;color:#fff;display:block;letter-spacing:-.06em}.event h3{font-family:'Space Grotesk',sans-serif;font-size:25px;letter-spacing:-.04em;margin:0 0 5px}.event p{color:#b7c3c0;font-size:13px;margin:0;line-height:1.5}.event-time{text-align:right;color:var(--mint);font-size:12px}.footer{max-width:1240px;margin:auto;padding:35px 32px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--muted)}.footer strong{font-family:'Space Grotesk',sans-serif;color:var(--ink);font-size:15px}.reveal{animation:rise .7s ease both}.delay{animation-delay:.15s}@keyframes rise{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}@media(max-width:760px){.site-nav{padding:20px 20px}.nav-links a:not(.nav-cta){display:none}.hero{padding:42px 20px 70px;display:block}.hero h1{font-size:58px}.hero-art{min-height:370px;margin-top:45px}.hero-photo{height:330px;width:86%}.hero-note{width:130px;height:130px;font-size:15px;padding:23px}.strip{padding:14px 20px}.strip-inner span:nth-child(2){display:none}.section,.events-section .section{padding:70px 20px}.section-head{display:block;margin-bottom:27px}.section-head p{margin-top:16px}.gallery-grid{grid-template-columns:1fr 1fr;grid-template-rows:220px 150px 150px}.gallery-item:first-child{grid-row:span 2}.gallery-item:nth-child(2){grid-column:auto}.gallery-item:nth-child(3){grid-column:span 1}.gallery-item:nth-child(4){grid-column:span 1}.event{grid-template-columns:66px 1fr;gap:14px;padding:20px 0}.event-time{grid-column:2;text-align:left}.event h3{font-size:20px}.footer{padding:26px 20px;display:block}.footer a{display:inline-block;margin-top:10px}}
</style>
</head>
<body>
<div class="landing">
<nav class="site-nav">
<a class="logo" href="index.php"><span class="logo-mark">EC</span><span>Embwen College</span></a>
<div class="nav-links"><a href="#gallery">Campus life</a><a href="#events">Events</a><a class="nav-cta" href="<?=htmlspecialchars($portalLink)?>"><?=htmlspecialchars($portalLabel)?> <span aria-hidden="true">↗</span></a></div>
</nav>
<main>
<section class="hero">
<div class="reveal"><p class="kicker">Vocational college · Est. 1998</p><h1>Learn with your hands. <em>Lead with purpose.</em></h1><p class="hero-copy">A practical, welcoming college for people who want to make useful things, solve real problems, and shape what comes next.</p><div class="hero-actions"><a class="button button-dark" href="#gallery">Explore campus <span>↓</span></a><a class="button button-light" href="#events">See events</a></div></div>
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
