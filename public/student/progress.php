<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();
$stmt = $pdo->prepare('SELECT a.*, e.unit_id, u.title AS unit_title FROM attempts a JOIN exercises e ON e.id=a.exercise_id JOIN units u ON u.id=e.unit_id WHERE a.user_id = ? ORDER BY a.created_at DESC');
$stmt->execute([$user['id']]);
$attempts = $stmt->fetchAll();
// units needing remediation
$needstmt = $pdo->prepare('SELECT p.*, un.title FROM progress p JOIN units un ON un.id=p.unit_id WHERE p.user_id = ? AND p.needs_remediation = 1');
$needstmt->execute([$user['id']]);
$needs = $needstmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>My Progress</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<a href="/">Accueil</a> | <a href="/logout.php">Logout</a>
<h1>My Progress</h1>
<table border="1" cellpadding="6">
  <tr><th>Date</th><th>Unit</th><th>Score</th><th>Details</th></tr>
  <?php foreach($attempts as $a): ?>
    <tr>
      <td><?=htmlspecialchars($a['created_at'])?></td>
      <td><?=htmlspecialchars($a['unit_title'])?></td>
      <td><?=htmlspecialchars($a['score'].' / '.$a['max_score'])?></td>
      <td><pre><?=htmlspecialchars($a['data'])?></pre></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php if (!empty($needs)): ?>
  <h2>Units needing remediation</h2>
  <ul>
  <?php foreach($needs as $n): ?>
    <li><?=htmlspecialchars($n['title'])?> — score <?=htmlspecialchars($n['score'].'/'.$n['max_score'])?> — <a href="/lesson.php?unit_id=<?= urlencode($n['unit_id']) ?>&remediation=1">Start remediation</a></li>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</body></html>
