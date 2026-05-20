<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();

$stmt = $pdo->prepare('SELECT a.*, e.unit_id, u.title AS unit_title FROM attempts a JOIN exercises e ON e.id=a.exercise_id JOIN units u ON u.id=e.unit_id WHERE a.user_id = ? ORDER BY a.created_at DESC');
$stmt->execute([$user['id']]);
$attempts = $stmt->fetchAll();

$needstmt = $pdo->prepare('SELECT p.*, un.title FROM progress p JOIN units un ON un.id=p.unit_id WHERE p.user_id = ? AND p.needs_remediation = 1');
$needstmt->execute([$user['id']]);
$needs = $needstmt->fetchAll();

$totalAttempts = count($attempts);
$bestScore = 0;
$recentScore = null;
foreach ($attempts as $index => $attempt) {
  $maxScore = max(1, (int)($attempt['max_score'] ?? 0));
  $percent = round(((int)($attempt['score'] ?? 0) / $maxScore) * 100);
  if ($percent > $bestScore) { $bestScore = $percent; }
  if ($index === 0) { $recentScore = $percent; }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Mon progrès</title><link rel="stylesheet" href="/assets/css/redesign.css"><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Mon progrès</h1>
  <p class="muted" style="margin-top:-4px">Tout ce qui compte, en un coup d'œil.</p>

  <div class="grid" style="margin-top:18px">
    <div class="card">
      <h3 style="margin-bottom:6px">Tentatives</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-primary)"><?= $totalAttempts ?></div>
      <p class="muted">Nombre total de tentatives enregistrées</p>
    </div>
    <div class="card">
      <h3 style="margin-bottom:6px">Meilleur score</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-success)"><?= (int)$bestScore ?>%</div>
      <p class="muted">Ton meilleur résultat</p>
    </div>
    <div class="card">
      <h3 style="margin-bottom:6px">Dernier score</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-warning)"><?= $recentScore === null ? '—' : (int)$recentScore.'%' ?></div>
      <p class="muted">Ta dernière tentative</p>
    </div>
  </div>

  <?php if (!empty($needs)): ?>
    <div class="card" style="margin-top:18px">
      <h3>À revoir</h3>
      <p class="muted">Ces unités ont besoin d'un petit travail supplémentaire.</p>
      <div style="display:grid;gap:10px;margin-top:12px">
        <?php foreach($needs as $n): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px;border:1px solid var(--rd-border);border-radius:12px;background:var(--rd-surface)">
            <div>
              <strong><?=htmlspecialchars($n['title'])?></strong>
              <div class="muted" style="font-size:13px">Score <?=htmlspecialchars($n['score'].'/'.$n['max_score'])?></div>
            </div>
            <a class="btn" href="/lesson.php?unit_id=<?= urlencode($n['unit_id']) ?>&remediation=1">Reprendre</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-top:18px">
    <h3>Dernières tentatives</h3>
    <?php if (empty($attempts)): ?>
      <p class="muted">Aucune tentative pour le moment.</p>
    <?php else: ?>
      <div style="display:grid;gap:12px;margin-top:12px">
        <?php foreach($attempts as $a): ?>
          <?php $scorePct = max(0, min(100, round(((int)$a['score'] / max(1,(int)$a['max_score'])) * 100))); ?>
          <div style="padding:14px;border:1px solid var(--rd-border);border-radius:14px;background:linear-gradient(180deg,#fff,#fbfdff)">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
              <div>
                <strong><?=htmlspecialchars($a['unit_title'])?></strong>
                <div class="muted" style="font-size:13px"><?=htmlspecialchars($a['created_at'])?></div>
              </div>
              <div style="font-weight:800;color:<?= $scorePct >= 80 ? 'var(--rd-success)' : ($scorePct >= 50 ? 'var(--rd-warning)' : 'var(--rd-danger)') ?>"><?= (int)$scorePct ?>%</div>
            </div>
            <div style="margin-top:10px" class="progress-bar"><i style="width:<?= $scorePct ?>%"></i></div>
            <div style="margin-top:8px">
              <details>
                <summary style="cursor:pointer;color:var(--rd-primary);font-weight:600">Voir les détails</summary>
                <pre style="margin-top:10px;white-space:pre-wrap;word-break:break-word;background:var(--rd-surface-alt);padding:12px;border-radius:10px;overflow:auto;max-height:220px"><?=htmlspecialchars($a['data'])?></pre>
              </details>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
