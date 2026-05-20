<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();
$stmt = $pdo->prepare('SELECT m.* FROM modules m JOIN user_courses uc ON uc.module_id=m.id WHERE uc.user_id = ? ORDER BY m.`order`');
$stmt->execute([$user['id']]);
$mods = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>My Courses</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Mes cours</h1>
  <?php if (empty($mods)): ?><p>You have no assigned courses yet.</p><?php else: ?>
    <div class="grid">
    <?php foreach($mods as $m): ?>
      <div class="lesson-card card">
        <h3><?=htmlspecialchars($m['title'])?></h3>
        <p class="muted"><?=htmlspecialchars($m['description'])?></p>
        <div style="margin-top:10px">
          <a class="btn" href="/pretest.php?module_id=<?= $m['id'] ?>">Prétest</a>
          <a class="btn secondary" href="/posttest.php?module_id=<?= $m['id'] ?>">Posttest</a>
          <a class="btn ghost" href="/lesson.php?unit_id=<?= $m['id'] ?>">Leçons</a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</main>
</body></html>
