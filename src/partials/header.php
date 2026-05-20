<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$user = null;
if (!empty($_SESSION['user_id'])){
  $user = current_user();
}

$exercisesHref = '/login.php';
if ($user) {
  if ($user['role'] === 'teacher' || $user['role'] === 'admin') {
    $exercisesHref = '/teacher/index.php';
  } else {
    $exercisesHref = '/student/index.php';
  }
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/assets/css/redesign.css">
<script src="https://unpkg.com/lottie-web@5.9.6/build/player/lottie.min.js"></script>
<?php
if (!empty($_SESSION['level_up'])){
  $lu = $_SESSION['level_up'];
  $lvl = (int)$lu['level'];
  $reward = isset($lu['new_xp']) ? (int)$lu['new_xp'] . ' XP' : 'Nouveau niveau';
  echo "<script>document.addEventListener('DOMContentLoaded',function(){ if (typeof showLevelUp==='function') showLevelUp({level:$lvl, rewardText:'$reward'}); });</script>";
  unset($_SESSION['level_up']);
}
?>
<header class="site-header" role="banner">
  <div class="rd-nav-inner">

    <a class="site-brand" href="/">
      <div class="brand-logo">A</div>
      <div>
        <div class="brand-title">Apprends les Articles</div>
        <div class="brand-subtitle">Amusant · Simple · Rapide</div>
      </div>
    </a>

    <nav class="rd-nav-links" aria-label="Navigation principale">
      <a class="rd-nav-link" href="/lesson.php">📚 Leçons</a>
      <a class="rd-nav-link" href="<?= htmlspecialchars($exercisesHref) ?>">✏️ Exercices</a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a class="rd-nav-link" href="/student/progress.php">📈 Mon Progrès</a>
      <?php endif; ?>
      <a class="rd-nav-link" href="/leaderboard.php">🏆 Classement</a>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <?php
          $xp = 0;
          try {
            $stmtxp = $pdo->prepare('SELECT COALESCE(SUM(score),0) AS xp FROM attempts WHERE user_id = ?');
            $stmtxp->execute([$user['id']]); $rowxp = $stmtxp->fetch(); $xp = (int)$rowxp['xp'];
          } catch(Exception $e){ $xp = 0; }
          $level = max(1, floor(pow(max(0,$xp)/100, 0.8)));
          $xp_for_level = 100 * pow($level, 1.25);
          $xp_for_next  = 100 * pow($level+1, 1.25);
          $pct = $xp_for_next > 0 ? max(0, min(100, round((($xp - $xp_for_level) / ($xp_for_next - $xp_for_level)) * 100))) : 0;
        ?>
        <!-- hidden xp-bar kept for JS compatibility -->
        <div class="xp-bar" id="xpBar" data-xp="<?= $xp ?>" data-level="<?= $level ?>" style="display:none">
          <div class="xp-bar__fill" style="width:<?= $pct ?>%"></div>
        </div>
        <span style="font-size:12px;font-weight:700;color:var(--rd-primary);background:var(--rd-primary-light);padding:5px 12px;border-radius:999px;white-space:nowrap">
          ⭐ Niv.<?= $level ?>&nbsp;·&nbsp;<?= $xp ?> XP
        </span>
        <span class="muted" style="font-size:13px;font-weight:600;white-space:nowrap">
          <?= htmlspecialchars($user['display_name'] ?? $user['username']) ?>
        </span>
        <?php if ($user['role'] === 'teacher'): ?>
          <a class="btn secondary" href="/teacher/index.php" style="padding:7px 14px!important;font-size:13px!important">📊 Prof</a>
        <?php elseif ($user['role'] === 'admin'): ?>
          <a class="btn secondary" href="/admin/index.php" style="padding:7px 14px!important;font-size:13px!important">⚙️ Admin</a>
        <?php endif; ?>
        <a class="btn ghost" href="/logout.php" style="font-size:13px!important">Déconnexion</a>
      <?php else: ?>
        <a class="btn secondary" href="/login.php">Se connecter</a>
        <a class="btn" href="/register.php">S'inscrire</a>
      <?php endif; ?>
      <button id="toggleTheme" class="btn ghost" title="Basculer thème" style="padding:8px!important;font-size:16px!important;box-shadow:none!important">🌗</button>
    </div>

  </div>
</header>
