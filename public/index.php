<?php
require_once __DIR__.'/../src/auth.php';
// List modules
$stmt = $pdo->query('SELECT id,title,description FROM modules ORDER BY `order`');
$modules = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Edu Platform</title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <h2>Modules</h2>
  <div class="grid">
  <?php foreach ($modules as $m): ?>
    <div class="lesson-card card">
      <h3><?=htmlspecialchars($m['title'])?></h3>
      <p class="muted"><?=htmlspecialchars($m['description'])?></p>
      <div style="margin-top:12px;"><a class="btn" href="/lesson.php?unit_id=1">Commencer</a></div>
    </div>
  <?php endforeach; ?>
  </div>
</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
