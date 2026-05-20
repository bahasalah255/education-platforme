<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');
$user = current_user();

// KPIs
$totalStudentsStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = "student"'); $totalStudentsStmt->execute(); $totalStudents = $totalStudentsStmt->fetchColumn();
$activeTodayStmt = $pdo->prepare('SELECT COUNT(DISTINCT user_id) FROM attempts WHERE DATE(created_at) = CURDATE()'); $activeTodayStmt->execute(); $activeToday = $activeTodayStmt->fetchColumn();

// Students needing remediation
$needStmt = $pdo->prepare('SELECT u.id,u.username,u.display_name, COUNT(p.id) AS needs FROM users u JOIN progress p ON p.user_id = u.id WHERE u.role="student" AND p.needs_remediation = 1 GROUP BY u.id ORDER BY needs DESC LIMIT 12');
$needStmt->execute(); $needList = $needStmt->fetchAll();

// Top performers
$topStmt = $pdo->prepare('SELECT u.id,u.username,u.display_name, COALESCE(SUM(a.score),0) AS points FROM users u LEFT JOIN attempts a ON a.user_id=u.id WHERE u.role="student" GROUP BY u.id ORDER BY points DESC LIMIT 10');
$topStmt->execute(); $topList = $topStmt->fetchAll();

// Modules
$modStmt = $pdo->query('SELECT m.*, COALESCE(u.display_name,u.username) AS creator_name FROM modules m LEFT JOIN users u ON m.created_by=u.id ORDER BY m.`order`');
$modules = $modStmt->fetchAll();
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tableau de bord · Enseignant</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">

  <!-- REDESIGN: Dashboard header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:28px;flex-wrap:wrap" class="rd-anim">
    <div>
      <h1 style="margin-bottom:4px">📊 Tableau de bord</h1>
      <p class="muted" style="margin:0">Bonjour, <?=htmlspecialchars($user['display_name'] ?? $user['username'])?> — voici l'état de votre classe</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="/teacher/new_module.php">+ Créer un module</a>
      <a class="btn secondary" href="/teacher/students.php">👥 Élèves</a>
    </div>
  </div>

  <!-- REDESIGN: KPI stats -->
  <div class="rd-stats rd-anim rd-d1">
    <div class="rd-stat">
      <div class="rd-stat-icon" style="background:var(--rd-primary-light)">👥</div>
      <div class="rd-stat-val"><?=htmlspecialchars($totalStudents)?></div>
      <div class="rd-stat-lbl">Élèves inscrits</div>
    </div>
    <div class="rd-stat">
      <div class="rd-stat-icon" style="background:var(--rd-success-light)">⚡</div>
      <div class="rd-stat-val"><?=htmlspecialchars($activeToday)?></div>
      <div class="rd-stat-lbl">Actifs aujourd'hui</div>
    </div>
    <div class="rd-stat">
      <div class="rd-stat-icon" style="background:var(--rd-warning-light)">⚠️</div>
      <div class="rd-stat-val"><?=count($needList)?></div>
      <div class="rd-stat-lbl">En difficulté</div>
    </div>
    <div class="rd-stat">
      <div class="rd-stat-icon" style="background:#faf0ff">📚</div>
      <div class="rd-stat-val"><?=count($modules)?></div>
      <div class="rd-stat-lbl">Modules créés</div>
    </div>
  </div>

  <!-- REDESIGN: Main two-column grid -->
  <div class="rd-grid-2 rd-anim rd-d2">
    <section>
      <!-- Students needing help -->
      <div class="card" style="margin-bottom:20px">
        <h3 style="margin-bottom:16px">⚠️ Élèves en difficulté</h3>
        <?php if (empty($needList)): ?>
          <div style="text-align:center;padding:20px 0">
            <div style="font-size:2rem;margin-bottom:8px">🎉</div>
            <p class="muted">Aucun élève n'a besoin d'attention immédiate.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach($needList as $s): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;background:var(--rd-warning-light);border-radius:10px;border:1px solid rgba(245,158,11,0.2)">
              <div>
                <div style="font-weight:700;font-size:14px"><?=htmlspecialchars($s['display_name'] ?: $s['username'])?></div>
                <div class="muted" style="font-size:12px;margin-top:2px"><?=htmlspecialchars($s['needs'])?> unité(s) à retravailler</div>
              </div>
              <a class="btn ghost" href="/teacher/student_progress.php?user_id=<?=urlencode($s['id'])?>" style="font-size:12px!important;padding:6px 12px!important;border:1px solid var(--rd-border)!important">Voir →</a>
            </div>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Top performers -->
      <div class="card" style="margin-bottom:20px">
        <h3 style="margin-bottom:16px">🏆 Top Performers</h3>
        <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach($topList as $i => $t): ?>
          <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--rd-bg);border-radius:10px">
            <div style="width:28px;height:28px;border-radius:50%;background:<?= $i===0?'linear-gradient(135deg,#F59E0B,#d97706)':($i===1?'linear-gradient(135deg,#94A3B8,#64748B)':'linear-gradient(135deg,#d97706,#92400e)') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px;flex-shrink:0">
              <?= $i+1 ?>
            </div>
            <div style="flex:1;font-weight:600;font-size:14px"><?=htmlspecialchars($t['display_name'] ?: $t['username'])?></div>
            <div style="font-size:13px;font-weight:700;color:var(--rd-primary)"><?=htmlspecialchars($t['points'])?> pts</div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>

      <!-- Modules -->
      <div class="card">
        <h3 style="margin-bottom:16px">📚 Modules</h3>
        <?php if (empty($modules)): ?>
          <p class="muted">Aucun module créé. <a href="/teacher/new_module.php">Créer le premier module →</a></p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach($modules as $m): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;background:var(--rd-bg);border-radius:10px">
            <div style="flex:1">
              <div style="font-weight:700;font-size:14px"><?=htmlspecialchars($m['title'])?></div>
              <div class="muted" style="font-size:12px">Par <?=htmlspecialchars($m['creator_name']??'—')?></div>
            </div>
            <div style="display:flex;gap:6px">
              <a class="btn ghost" href="/teacher/edit_module.php?id=<?=urlencode($m['id'])?>" style="font-size:12px!important;padding:6px 10px!important;border:1px solid var(--rd-border)!important">Modifier</a>
              <a class="btn ghost" href="/teacher/new_unit.php?module_id=<?=urlencode($m['id'])?>" style="font-size:12px!important;padding:6px 10px!important;border:1px solid var(--rd-border)!important">+ Unité</a>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Sidebar -->
    <aside>
      <div class="card">
        <h3 style="margin-bottom:16px">⚡ Actions rapides</h3>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a class="btn" href="/teacher/new_module.php" style="justify-content:flex-start!important">📚 Créer un module</a>
          <a class="btn secondary" href="/teacher/new_unit.php" style="justify-content:flex-start!important">📄 Ajouter une unité</a>
          <a class="btn secondary" href="/teacher/new_exercise.php" style="justify-content:flex-start!important">✏️ Créer un exercice</a>
          <a class="btn secondary" href="/teacher/students.php" style="justify-content:flex-start!important">👥 Gérer les élèves</a>
          <a class="btn ghost" href="/teacher/export_attempts_csv.php" style="justify-content:flex-start!important;border:1px solid var(--rd-border)!important">📊 Exporter CSV</a>
        </div>
      </div>

      <div class="card" style="margin-top:16px;background:linear-gradient(135deg,var(--rd-primary-light),#f0fdf4)!important">
        <div style="font-size:2rem;margin-bottom:8px">💡</div>
        <h4 style="margin-bottom:6px">Conseil pédagogique</h4>
        <p class="muted" style="font-size:13px;margin:0">Vérifiez régulièrement les élèves en difficulté et générez des exercices de remédiation pour les accompagner.</p>
      </div>
    </aside>
  </div>

  <?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</main>
</body></html>
