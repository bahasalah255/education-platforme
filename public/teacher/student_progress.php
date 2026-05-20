<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) { echo 'User not specified'; exit; }

$ustmt = $pdo->prepare('SELECT id,username,display_name FROM users WHERE id = ? AND role = "student"');
$ustmt->execute([$user_id]); $user = $ustmt->fetch();
if (!$user) { echo 'Student not found'; exit; }

$stmt = $pdo->prepare('SELECT p.*, u.title AS unit_title, m.title AS module_title FROM progress p JOIN units u ON u.id=p.unit_id JOIN modules m ON m.id=u.module_id WHERE p.user_id = ? ORDER BY m.`order`, u.`order`');
$stmt->execute([$user_id]); $rows = $stmt->fetchAll();

$totalUnits = count($rows);
$remedUnits = 0;
$bestScore = 0;
$avgScore = 0;

foreach ($rows as $row) {
  $score = (int)($row['score'] ?? 0);
  $max = max(1, (int)($row['max_score'] ?? 0));
  $pct = round(($score / $max) * 100);
  if (!empty($row['needs_remediation'])) $remedUnits++;
  if ($pct > $bestScore) $bestScore = $pct;
  $avgScore += $pct;
}
$avgScore = $totalUnits > 0 ? round($avgScore / $totalUnits) : 0;
?><!doctype html>
<html><head><meta charset="utf-8"><title>Student Progress</title><link rel="stylesheet" href="/assets/css/redesign.css"><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap">
    <div>
      <a class="btn ghost" href="/teacher/students.php" style="padding-left:0!important">← Back</a>
      <h1 style="margin-top:6px">Progress for <?=htmlspecialchars($user['display_name'])?></h1>
      <p class="muted" style="margin-top:-4px">Student: <?=htmlspecialchars($user['username'])?></p>
    </div>
    <div class="badge">🧑‍🎓 Student report</div>
  </div>

  <div class="grid" style="margin-top:18px">
    <div class="card">
      <h3 style="margin-bottom:6px">Total units</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-primary)"><?= $totalUnits ?></div>
      <p class="muted">Tracked units for this student</p>
    </div>
    <div class="card">
      <h3 style="margin-bottom:6px">Need review</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-warning)"><?= $remedUnits ?></div>
      <p class="muted">Units marked for remediation</p>
    </div>
    <div class="card">
      <h3 style="margin-bottom:6px">Best score</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-success)"><?= $bestScore ?>%</div>
      <p class="muted">Highest unit result</p>
    </div>
    <div class="card">
      <h3 style="margin-bottom:6px">Average</h3>
      <div style="font-size:32px;font-weight:800;color:var(--rd-primary-dark)"><?= $avgScore ?>%</div>
      <p class="muted">Average score across units</p>
    </div>
  </div>

  <div class="card" style="margin-top:18px">
    <h3>Units needing remediation</h3>
    <?php if (empty($rows)): ?>
      <p class="muted">No progress data yet.</p>
    <?php else: ?>
      <div style="display:grid;gap:10px;margin-top:12px">
        <?php $hasRemed = false; foreach($rows as $r): if (!empty($r['needs_remediation'])): $hasRemed = true; ?>
          <?php $scorePct = max(0, min(100, round(((int)$r['score'] / max(1,(int)$r['max_score'])) * 100))); ?>
          <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px;border:1px solid var(--rd-border);border-radius:14px;background:linear-gradient(180deg,#fff,#fbfdff)">
            <div>
              <strong><?=htmlspecialchars($r['module_title'].' — '.$r['unit_title'])?></strong>
              <div class="muted" style="font-size:13px">Score <?=htmlspecialchars($r['score'].'/'.$r['max_score'])?></div>
            </div>
            <a class="btn" href="/lesson.php?unit_id=<?=urlencode($r['unit_id'])?>&remediation=1">Start remediation</a>
          </div>
        <?php endif; endforeach; if (!$hasRemed): ?>
          <p class="muted">No unit currently requires remediation.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-top:18px">
    <h3>All units</h3>
    <?php if (empty($rows)): ?>
      <p class="muted">No units recorded.</p>
    <?php else: ?>
      <div style="display:grid;gap:10px;margin-top:12px">
      <?php foreach($rows as $r): ?>
        <?php $scorePct = max(0, min(100, round(((int)$r['score'] / max(1,(int)$r['max_score'])) * 100))); ?>
        <div style="padding:14px;border:1px solid var(--rd-border);border-radius:14px;background:linear-gradient(180deg,#fff,#fbfdff)">
          <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
            <div>
              <strong><?=htmlspecialchars($r['module_title'].' — '.$r['unit_title'])?></strong>
              <div class="muted" style="font-size:13px">Remediation: <?= $r['needs_remediation'] ? 'Yes' : 'No' ?></div>
            </div>
            <div style="font-weight:800;color:<?= $scorePct >= 80 ? 'var(--rd-success)' : ($scorePct >= 50 ? 'var(--rd-warning)' : 'var(--rd-danger)') ?>"><?= $scorePct ?>%</div>
          </div>
          <div style="margin-top:10px" class="progress-bar"><i style="width:<?= $scorePct ?>%"></i></div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>