<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();

// XP & Level
$xp = 0;
try {
  $stxp = $pdo->prepare('SELECT COALESCE(SUM(score),0) AS xp FROM attempts WHERE user_id = ?');
  $stxp->execute([$user['id']]); $rx = $stxp->fetch(); $xp = (int)$rx['xp'];
} catch(Exception $e){ $xp = 0; }
$level = max(1, floor(pow(max(0,$xp)/100, 0.8)));
$xp_for_level = 100 * pow($level, 1.25);
$xp_for_next  = 100 * pow($level+1, 1.25);
$percent_xp = $xp_for_next > 0 ? max(0, min(100, round((($xp-$xp_for_level)/($xp_for_next-$xp_for_level))*100))) : 0;

// Enrolled modules
$stmt = $pdo->prepare('SELECT m.*, COALESCE(u.display_name,u.username) AS creator_name FROM modules m JOIN user_courses uc ON uc.module_id=m.id LEFT JOIN users u ON m.created_by=u.id WHERE uc.user_id=? ORDER BY m.`order`');
$stmt->execute([$user['id']]);
$mods = $stmt->fetchAll();

// Badges
$badgesStmt = $pdo->prepare('SELECT b.title, b.icon FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.awarded_at DESC LIMIT 4');
$badgesStmt->execute([$user['id']]);
$myBadges = $badgesStmt->fetchAll();

// Last attempt date for streak message
$lastStmt = $pdo->prepare('SELECT MAX(created_at) AS last FROM attempts WHERE user_id=?');
$lastStmt->execute([$user['id']]); $lastRow = $lastStmt->fetch();
$lastDate = $lastRow['last'] ? date('d/m/Y', strtotime($lastRow['last'])) : null;

// Count new resources
$newResStmt = $pdo->prepare('SELECT COUNT(*) FROM media m JOIN user_courses uc ON uc.module_id=m.module_id WHERE uc.user_id=? AND m.uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$newResStmt->execute([$user['id']]); $newResCount = (int)$newResStmt->fetchColumn();

$firstName = explode(' ', $user['display_name'] ?? $user['username'])[0];
$levelEmojis = ['🌱','⭐','🌟','🚀','💎','👑','🔥','🦁','🦄','🏆'];
$levelEmoji = $levelEmojis[min($level-1, count($levelEmojis)-1)];
$colors = ['#6366f1','#f97316','#16a34a','#db2777','#0891b2','#9333ea'];
$icons  = ['📖','✏️','🎯','🌟','📝','🏆'];
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mon Espace · Ma maison est une école</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap">
  <link rel="stylesheet" href="/assets/css/redesign.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    /* Kid theme overrides */
    body { font-family: 'Nunito', 'Inter', sans-serif !important; background: #f0f4ff !important; }
    body.dark { background: #0d0f2b !important; }

    .kid-container { max-width: 1100px; margin: 0 auto; padding: 0 20px 60px; }

    /* Top welcome banner */
    .kid-banner {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
      border-radius: 28px; padding: 32px 36px; color: white;
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
      margin: 24px 0 28px; position: relative; overflow: hidden;
      box-shadow: 0 8px 32px rgba(79,70,229,.35);
    }
    .kid-banner::before {
      content: ''; position: absolute; top:-60px; right:-60px;
      width:220px; height:220px; border-radius:50%;
      background: rgba(255,255,255,.08);
    }
    .kid-banner::after {
      content: ''; position: absolute; bottom:-40px; left:30%;
      width:150px; height:150px; border-radius:50%;
      background: rgba(255,255,255,.06);
    }
    .kid-banner h1 { color:white!important; font-size:clamp(1.4rem,4vw,2rem); margin:0 0 6px; font-weight:900; }
    .kid-banner p  { color:rgba(255,255,255,.88); margin:0; font-size:1rem; font-weight:600; }
    .kid-avatar {
      width:80px; height:80px; border-radius:50%;
      background: rgba(255,255,255,.25); border: 3px solid rgba(255,255,255,.5);
      display:flex; align-items:center; justify-content:center;
      font-size:2.2rem; font-weight:900; color:white; flex-shrink:0;
      box-shadow: 0 4px 16px rgba(0,0,0,.2);
    }
    .kid-xp-bar-wrap { margin-top:12px; max-width:340px; }
    .kid-xp-track { height:14px; background:rgba(255,255,255,.25); border-radius:999px; overflow:hidden; }
    .kid-xp-fill  { height:100%; background:linear-gradient(90deg,#fbbf24,#f59e0b); border-radius:999px;
                    transition:width 1s ease; box-shadow:0 0 8px rgba(251,191,36,.6); }
    .kid-xp-label { display:flex; justify-content:space-between; color:rgba(255,255,255,.8);
                    font-size:12px; font-weight:700; margin-top:5px; }

    /* Stats row */
    .kid-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px; margin-bottom:28px; }
    .kid-stat {
      border-radius:20px; padding:20px 16px; text-align:center;
      box-shadow: 0 4px 16px rgba(0,0,0,.08); transition:transform .2s;
    }
    .kid-stat:hover { transform:translateY(-4px); }
    .kid-stat-emoji { font-size:2.2rem; margin-bottom:6px; }
    .kid-stat-val   { font-size:1.8rem; font-weight:900; line-height:1; }
    .kid-stat-lbl   { font-size:13px; font-weight:700; margin-top:4px; opacity:.8; }

    /* Section headers */
    .kid-section-hd {
      display:flex; align-items:center; gap:10px; margin:32px 0 16px;
      font-size:1.25rem; font-weight:900;
    }
    .kid-section-hd span { font-size:1.8rem; }

    /* Module cards */
    .kid-modules { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
    .kid-mod-card {
      border-radius:24px; overflow:hidden; background:white;
      box-shadow:0 6px 24px rgba(0,0,0,.1); transition:transform .25s, box-shadow .25s;
      display:flex; flex-direction:column;
    }
    body.dark .kid-mod-card { background: #1e293b; }
    .kid-mod-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(0,0,0,.15); }
    .kid-mod-top {
      height:110px; display:flex; align-items:center; justify-content:center;
      font-size:3.5rem; position:relative; overflow:hidden;
    }
    .kid-mod-top::after {
      content:''; position:absolute; bottom:-20px; left:50%; transform:translateX(-50%);
      width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.12);
    }
    .kid-mod-body { padding:18px 18px 20px; flex:1; display:flex; flex-direction:column; gap:10px; }
    .kid-mod-badge {
      display:inline-flex; align-items:center; gap:5px; padding:4px 12px;
      border-radius:999px; font-size:12px; font-weight:800; width:fit-content;
    }
    .kid-mod-title { font-size:1rem; font-weight:900; line-height:1.3; }
    .kid-prog-track { height:10px; background:rgba(0,0,0,.08); border-radius:999px; overflow:hidden; }
    body.dark .kid-prog-track { background:rgba(255,255,255,.1); }
    .kid-prog-fill  { height:100%; border-radius:999px; transition:width .8s ease; }
    .kid-prog-label { font-size:12px; font-weight:700; margin-top:4px; opacity:.7; }
    .kid-mod-actions { display:flex; gap:8px; margin-top:auto; }
    .kid-btn {
      flex:1; padding:10px 12px; border-radius:14px; border:0;
      font-family:'Nunito',sans-serif; font-size:14px; font-weight:800;
      cursor:pointer; text-decoration:none; text-align:center;
      transition:transform .15s, box-shadow .15s;
      display:inline-flex; align-items:center; justify-content:center; gap:6px;
    }
    .kid-btn:hover { transform:translateY(-2px); }
    .kid-btn-primary { color:white; box-shadow:0 4px 12px rgba(0,0,0,.18); }
    .kid-btn-outline { background:white; border:2.5px solid currentColor; }
    body.dark .kid-btn-outline { background:#1e293b; }

    /* Resources quick-access */
    .kid-res-bar {
      background:linear-gradient(135deg,#0ea5e9,#6366f1);
      border-radius:20px; padding:20px 24px; color:white;
      display:flex; align-items:center; justify-content:space-between; gap:16px;
      margin-bottom:28px; flex-wrap:wrap;
    }
    .kid-res-bar h3 { color:white!important; margin:0 0 4px; font-size:1.1rem; }
    .kid-res-bar p  { margin:0; opacity:.88; font-size:14px; }
    .kid-res-btn {
      background:white; color:#4f46e5; padding:10px 22px; border-radius:12px;
      font-weight:900; text-decoration:none; font-size:14px; white-space:nowrap;
      transition:transform .2s; flex-shrink:0;
    }
    .kid-res-btn:hover { transform:scale(1.04); opacity:1; color:#4f46e5; }

    /* Badges */
    .kid-badges { display:flex; gap:12px; flex-wrap:wrap; margin-top:4px; }
    .kid-badge {
      display:flex; flex-direction:column; align-items:center; gap:6px;
      background:white; border-radius:16px; padding:14px 18px;
      box-shadow:0 3px 12px rgba(0,0,0,.08); text-align:center; min-width:90px;
      transition:transform .2s;
    }
    body.dark .kid-badge { background:#1e293b; }
    .kid-badge:hover { transform:translateY(-3px); }
    .kid-badge-icon { font-size:2rem; }
    .kid-badge-name { font-size:11px; font-weight:800; opacity:.7; line-height:1.2; }
    .kid-badge-locked { opacity:.4; filter:grayscale(1); }

    /* Missions */
    .kid-missions { display:flex; flex-direction:column; gap:10px; }
    .kid-mission {
      display:flex; align-items:center; gap:14px;
      background:white; border-radius:16px; padding:14px 18px;
      box-shadow:0 2px 10px rgba(0,0,0,.07);
    }
    body.dark .kid-mission { background:#1e293b; }
    .kid-mission-icon { font-size:1.6rem; flex-shrink:0; }
    .kid-mission-txt { flex:1; font-weight:700; font-size:14px; }
    .kid-mission-xp {
      font-size:12px; font-weight:900; padding:4px 10px;
      border-radius:999px; background:#fef3c7; color:#92400e;
    }

    @media(max-width:720px){
      .kid-banner { flex-direction:column; align-items:flex-start; }
      .kid-avatar  { width:60px; height:60px; font-size:1.6rem; }
    }
  </style>
  <script src="/assets/js/ui.js" defer></script>
</head>
<body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>

<div class="kid-container">

  <!-- Welcome banner -->
  <div class="kid-banner rd-anim">
    <div>
      <h1><?= $levelEmoji ?> Bonjour, <?= htmlspecialchars($firstName) ?> !</h1>
      <p>Tu es au niveau <?= $level ?> — continue comme ça, tu es super ! 🎉</p>
      <div class="kid-xp-bar-wrap">
        <div class="kid-xp-track">
          <div class="kid-xp-fill" style="width:<?= $percent_xp ?>%"></div>
        </div>
        <div class="kid-xp-label">
          <span>Niv. <?= $level ?></span>
          <span><?= $xp ?> XP / <?= round($xp_for_next) ?> XP</span>
          <span>Niv. <?= $level+1 ?></span>
        </div>
      </div>
    </div>
    <div class="kid-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($firstName,0,1))) ?></div>
  </div>

  <!-- Stats -->
  <div class="kid-stats rd-anim rd-d1">
    <div class="kid-stat" style="background:#fef3c7;color:#92400e">
      <div class="kid-stat-emoji">⭐</div>
      <div class="kid-stat-val"><?= $xp ?></div>
      <div class="kid-stat-lbl">Points XP</div>
    </div>
    <div class="kid-stat" style="background:#ede9fe;color:#5b21b6">
      <div class="kid-stat-emoji">🏆</div>
      <div class="kid-stat-val"><?= $level ?></div>
      <div class="kid-stat-lbl">Mon Niveau</div>
    </div>
    <div class="kid-stat" style="background:#dcfce7;color:#166534">
      <div class="kid-stat-emoji">📚</div>
      <div class="kid-stat-val"><?= count($mods) ?></div>
      <div class="kid-stat-lbl">Mes Cours</div>
    </div>
    <div class="kid-stat" style="background:#fee2e2;color:#991b1b">
      <div class="kid-stat-emoji">🎖️</div>
      <div class="kid-stat-val"><?= count($myBadges) ?></div>
      <div class="kid-stat-lbl">Badges</div>
    </div>
  </div>

  <!-- Resources shortcut -->
  <div class="kid-res-bar rd-anim rd-d1">
    <div>
      <h3>🎬 Vidéos & PDF disponibles !</h3>
      <p>Regarde les leçons vidéo et lis les documents PDF de ton professeur.
        <?= $newResCount > 0 ? "<strong>$newResCount nouvelle(s)</strong> cette semaine !" : '' ?>
      </p>
    </div>
    <a href="/student/resources.php" class="kid-res-btn">🚀 Voir mes cours</a>
  </div>

  <!-- Modules -->
  <div class="kid-section-hd"><span>📚</span> Mes modules</div>

  <?php if (empty($mods)): ?>
    <div style="text-align:center;padding:40px;background:white;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,.08)">
      <div style="font-size:4rem;margin-bottom:12px">😊</div>
      <h3>Ton professeur ne t'a pas encore assigné de cours.</h3>
      <p class="muted">Reviens bientôt — tes leçons arrivent !</p>
    </div>
  <?php else: ?>
  <div class="kid-modules">
    <?php foreach ($mods as $i => $m):
      // progress per module
      $stmtu = $pdo->prepare('SELECT id FROM units WHERE module_id=?'); $stmtu->execute([$m['id']]); $units = $stmtu->fetchAll();
      $unitCount = count($units); $total=0; $maxS=0; $completedUnits=0;
      foreach ($units as $u) {
        $stp = $pdo->prepare('SELECT score,max_score FROM progress WHERE user_id=? AND unit_id=?');
        $stp->execute([$user['id'],$u['id']]); $pr = $stp->fetch();
        if ($pr){ $total+=(int)$pr['score']; $maxS+=(int)$pr['max_score'];
          if ((int)$pr['max_score']>0 && (int)$pr['score']>=(int)$pr['max_score']*0.8) $completedUnits++; }
      }
      $pct = ($maxS>0) ? round(($total/$maxS)*100) : ($completedUnits>0 ? round(($completedUnits/max(1,$unitCount))*100) : 0);
      $locked = ($unitCount>0 && $completedUnits<1 && $pct===0);
      $bg = $colors[$i % count($colors)];
      $ic = $icons[$i % count($icons)];
      $statusEmoji = $pct>=100?'✅':($pct>0?'🔄':'🔒');
    ?>
    <div class="kid-mod-card rd-anim" style="animation-delay:<?= $i*60 ?>ms">
      <?php if (!empty($m['cover_image'])): ?>
        <div class="kid-mod-top" style="background:<?= $bg ?>">
          <img src="<?= htmlspecialchars($m['cover_image']) ?>" alt="" style="width:100%;height:110px;object-fit:cover;position:absolute;inset:0">
        </div>
      <?php else: ?>
        <div class="kid-mod-top" style="background:linear-gradient(135deg,<?= $bg ?>,<?= $colors[($i+1)%count($colors)] ?>)">
          <?= $ic ?>
        </div>
      <?php endif; ?>
      <div class="kid-mod-body">
        <div class="kid-mod-badge" style="background:<?= $bg ?>22;color:<?= $bg ?>">
          <?= $statusEmoji ?> Module <?= $i+1 ?>
        </div>
        <div class="kid-mod-title"><?= htmlspecialchars($m['title']) ?></div>
        <?php if ($m['description']): ?>
          <div style="font-size:13px;opacity:.7;line-height:1.4"><?= htmlspecialchars(mb_substr($m['description'],0,80)).(mb_strlen($m['description'])>80?'…':'') ?></div>
        <?php endif; ?>
        <div>
          <div class="kid-prog-track">
            <div class="kid-prog-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,<?= $bg ?>,<?= $colors[($i+1)%count($colors)] ?>)"></div>
          </div>
          <div class="kid-prog-label"><?= $pct ?>% complété — <?= $completedUnits ?>/<?= $unitCount ?> unités</div>
        </div>
        <div class="kid-mod-actions">
          <?php if ($locked): ?>
            <a href="/entrance.php?module_id=<?= $m['id'] ?>" class="kid-btn kid-btn-primary" style="background:<?= $bg ?>">🧪 Test d'entrée</a>
          <?php else: ?>
            <a href="/lesson.php?module_id=<?= $m['id'] ?>" class="kid-btn kid-btn-primary" style="background:<?= $bg ?>">▶️ Continuer</a>
          <?php endif; ?>
          <a href="/student/resources.php?module_id=<?= $m['id'] ?>" class="kid-btn kid-btn-outline" style="color:<?= $bg ?>">🎬 Vidéos</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Bottom 2-col: Missions + Badges -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:36px" class="rd-anim rd-d2">

    <!-- Missions -->
    <div>
      <div class="kid-section-hd"><span>🎯</span> Missions du jour</div>
      <div class="kid-missions">
        <div class="kid-mission">
          <div class="kid-mission-icon">⏱️</div>
          <div class="kid-mission-txt">Faire 10 minutes de pratique</div>
          <div class="kid-mission-xp">+20 XP</div>
        </div>
        <div class="kid-mission">
          <div class="kid-mission-icon">✅</div>
          <div class="kid-mission-txt">Réussir 5 exercices</div>
          <div class="kid-mission-xp">+30 XP</div>
        </div>
        <div class="kid-mission">
          <div class="kid-mission-icon">🌟</div>
          <div class="kid-mission-txt">Obtenir 80% à un test</div>
          <div class="kid-mission-xp">+40 XP</div>
        </div>
        <div class="kid-mission">
          <div class="kid-mission-icon">🎬</div>
          <div class="kid-mission-txt">Regarder une vidéo de cours</div>
          <div class="kid-mission-xp">+15 XP</div>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
        <a href="/lesson.php" class="kid-btn kid-btn-primary" style="background:#4f46e5">📖 Aller aux leçons</a>
        <a href="/student/resources.php" class="kid-btn kid-btn-primary" style="background:#0891b2">🎬 Voir les vidéos</a>
      </div>
    </div>

    <!-- Badges -->
    <div>
      <div class="kid-section-hd"><span>🏅</span> Mes badges</div>
      <?php if (empty($myBadges)): ?>
        <div style="text-align:center;padding:28px;background:white;border-radius:20px;box-shadow:0 3px 12px rgba(0,0,0,.07)">
          <div style="font-size:2.5rem;margin-bottom:8px">🔒</div>
          <p style="font-weight:700;opacity:.6">Complète des activités pour gagner tes premiers badges !</p>
        </div>
      <?php else: ?>
      <div class="kid-badges">
        <?php foreach ($myBadges as $b): ?>
          <div class="kid-badge">
            <div class="kid-badge-icon"><?= $b['icon'] ? '<img src="'.htmlspecialchars($b['icon']).'" style="width:36px;height:36px">' : '🏅' ?></div>
            <div class="kid-badge-name"><?= htmlspecialchars($b['title']) ?></div>
          </div>
        <?php endforeach; ?>
        <div class="kid-badge kid-badge-locked">
          <div class="kid-badge-icon">🔒</div>
          <div class="kid-badge-name">Badge secret</div>
        </div>
      </div>
      <?php endif; ?>
      <div style="margin-top:14px">
        <a href="/student/progress.php" class="kid-btn kid-btn-primary" style="background:#7c3aed">📈 Voir mes progrès</a>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body>
</html>
