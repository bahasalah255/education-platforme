<?php
require_once __DIR__.'/../src/auth.php';
require_login();

$stmt = $pdo->query('SELECT id,username,display_name,points FROM users WHERE role="student" ORDER BY points DESC LIMIT 100');
$rows = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Classement</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Classement</h1>
  <div class="card">
    <table class="table">
      <thead><tr><th>Rang</th><th>Pseudo</th><th>Nom</th><th>Points</th></tr></thead>
      <tbody>
      <?php $rank=1; foreach($rows as $r): ?>
        <tr><td><?= $rank++ ?></td><td><?=htmlspecialchars($r['username'])?></td><td><?=htmlspecialchars($r['display_name'])?></td><td><?=htmlspecialchars($r['points'])?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
