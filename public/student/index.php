<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();

// Find modules the user is enrolled in (include cover and creator)
$stmt = $pdo->prepare('SELECT m.*, COALESCE(u.display_name,u.username) AS creator_name FROM modules m JOIN user_courses uc ON uc.module_id=m.id LEFT JOIN users u ON m.created_by = u.id WHERE uc.user_id = ? ORDER BY m.`order`');
$stmt->execute([$user['id']]);
$assigned = $stmt->fetchAll();

// Show all assigned modules but compute lock state per module (locked if no/insufficient progress)
$mods = $assigned;
// compute xp for main dashboard
$xp = 0;
try{
  $stxp = $pdo->prepare('SELECT COALESCE(SUM(score),0) AS xp FROM attempts WHERE user_id = ?');
  $stxp->execute([$user['id']]); $rx = $stxp->fetch(); $xp = (int)$rx['xp'];
}catch(Exception $e){ $xp = 0; }
$level = max(1, floor(pow(max(0,$xp)/100, 0.8)) );
$xp_for_level = 100 * pow($level, 1.25);
$next_level = $level + 1;
$xp_for_next = 100 * pow($next_level, 1.25);
$percent_main = $xp_for_next>0 ? round((($xp - $xp_for_level) / ($xp_for_next - $xp_for_level)) * 100) : 0;
if ($percent_main<0) $percent_main=0; if ($percent_main>100) $percent_main=100;
?><!doctype html>
<html><head><meta charset="utf-8"><title>Tableau de bord</title>
<link rel="stylesheet" href="/assets/css/redesign.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Tableau de bord</h1>
  <div class="layout-with-sidebar">
    <aside class="side-menu">
      <h4>Menu</h4>
      <a href="/">Accueil</a>
      <a href="/entrance.php">Système d'entrée</a>
      <div style="margin-top:8px">
        <strong>Leçons</strong>
        <a href="/lesson.php">Leçons</a>
      </div>
      <div style="margin-top:8px">
        <strong>Évaluation</strong>
        <a href="/pretest.php">Prétests</a>
        <a href="/posttest.php">Post-tests</a>
      </div>
    </aside>

    <section>
      <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
        <div>
          <h2 style="margin:0">Bonjour <?=htmlspecialchars($user['display_name'] ?? $user['username'])?></h2>
          <p class="muted">Objectif du jour : 10 minutes — progresse étape par étape.</p>
          <div style="margin-top:12px;max-width:420px">
            <div class="xp-bar" id="mainXpBar" data-xp="<?= $xp ?>" data-level="<?= $level ?>"><div class="xp-bar__fill" style="width:<?= $percent_main ?>%"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px"><small class="muted">Niv. <?= $level ?></small><small class="muted"><?= $xp ?> XP</small></div>
          </div>
        </div>
        <div style="text-align:center">
          <div class="avatar" style="width:64px;height:64px;font-size:20px"><?=htmlspecialchars(substr($user['display_name'] ?? $user['username'],0,1))?></div>
          <div style="margin-top:8px"><a class="btn" href="/entrance.php?module_id=<?= $mods[0]['id'] ?? 1 ?>">Passer le test d'entrée</a></div>
        </div>
      </div>

      <h3 style="margin-top:18px">Modules assignés</h3>
      <div class="grid">
      <?php if (empty($mods)): ?><p>Tu n'as pas de cours assignés pour le moment.</p>
      <?php else: ?>
          <?php foreach($mods as $m):
          // compute units and progress counts to determine lock state and percent
          $stmtu = $pdo->prepare('SELECT id FROM units WHERE module_id = ?'); $stmtu->execute([$m['id']]); $units = $stmtu->fetchAll();
          $unitCount = count($units);
          $pcountStmt = $pdo->prepare('SELECT COUNT(*) FROM progress p JOIN units u ON p.unit_id = u.id WHERE p.user_id=? AND u.module_id=?'); $pcountStmt->execute([$user['id'],$m['id']]); $progCount = (int)$pcountStmt->fetchColumn();
          $locked = ($unitCount > 0 && $progCount < $unitCount);
          $total = 0; $max = 0; $completedUnits = 0;
          foreach($units as $u){
            $st = $pdo->prepare('SELECT score,max_score FROM progress WHERE user_id=? AND unit_id=?'); $st->execute([$user['id'],$u['id']]); $pr = $st->fetch();
            if ($pr){ $total += (int)$pr['score']; $max += (int)$pr['max_score']; if ((int)$pr['max_score']>0 && ((int)$pr['score'] >= (int)$pr['max_score']*0.8)) $completedUnits++; }
          }
          $percent = ($max>0) ? round(($total/$max)*100) : ($completedUnits>0 ? round(($completedUnits/count($units))*100) : 0);
          // find next unit (first unit without progress or lowest score)
          $nextUnit = null;
          foreach($units as $u){
            $st2 = $pdo->prepare('SELECT id,score,max_score FROM progress WHERE user_id=? AND unit_id=?'); $st2->execute([$user['id'],$u['id']]); $p2 = $st2->fetch();
            if (!$p2 || ($p2['max_score']>0 && $p2['score'] < $p2['max_score'])){ $nextUnit = $u['id']; break; }
          }
        ?>
          <div class="lesson-card card" style="padding:0;overflow:hidden">
            <?php if (!empty($m['cover_image'])): ?><div style="height:160px;overflow:hidden"><img src="<?=htmlspecialchars($m['cover_image'])?>" alt="cover" style="width:100%;height:160px;object-fit:cover"></div><?php endif; ?>
            <div style="padding:14px">
              <h3 style="margin:0 0 6px"><?=htmlspecialchars($m['title'])?></h3>
              <div class="muted" style="font-size:13px;margin-bottom:6px">Par : <?=htmlspecialchars($m['creator_name'] ?? '—')?></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                <div style="flex:1">
                  <div class="progress-bar"><i style="width:<?= $percent ?>%"></i></div>
                  <small class="muted"><?= $percent ?>% complété</small>
                </div>
                <div style="margin-left:12px;display:flex;flex-direction:column;gap:8px">
                  <?php if ($locked): ?>
                    <a class="btn" href="/entrance.php?module_id=<?= $m['id'] ?>">Passer test d'entrée</a>
                  <?php else: ?>
                    <a class="btn" href="/lesson.php?module_id=<?= $m['id'] ?>">Commencer</a>
                  <?php endif; ?>
                  <a class="btn secondary" href="/pretest.php?module_id=<?= $m['id'] ?>">Voir prétest</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      </div>
    </section>

    <aside>
      <div class="card">
        <h3>Missions du jour</h3>
        <ul style="padding-left:16px;margin:8px 0">
          <li>Compléter 10 minutes de pratique <span class="muted">(+20 XP)</span></li>
          <li>Réussir 5 QCM sans indices <span class="muted">(+30 XP)</span></li>
          <li>Atteindre 80% sur un mini-test <span class="muted">(+40 XP)</span></li>
        </ul>
        <div style="margin-top:12px;text-align:center">
          <a class="btn" href="/lesson.php">Continuer apprentissage</a>
          <button class="btn secondary" onclick="openChest()">Ouvrir coffre</button>
        </div>
      </div>

      <div class="card" style="margin-top:12px">
        <h3>Badges récents</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
          <div class="badge">Bronze Lecture</div>
          <div class="badge locked">Streak 7</div>
          <div class="badge">Précision 80%</div>
        </div>
      </div>
    </aside>
  </div>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</main>
</body></html>
