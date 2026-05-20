<?php
require_once __DIR__.'/../src/auth.php';
require_login();

$stmt = $pdo->query('SELECT id,username,display_name,points FROM users WHERE role="student" ORDER BY points DESC LIMIT 100');
$rows = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Leaderboard</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<a href="/">Accueil</a>
<h1>Leaderboard</h1>
<table border="1" cellpadding="6">
  <tr><th>Rank</th><th>Username</th><th>Name</th><th>Points</th></tr>
  <?php $rank=1; foreach($rows as $r): ?>
    <tr><td><?= $rank++ ?></td><td><?=htmlspecialchars($r['username'])?></td><td><?=htmlspecialchars($r['display_name'])?></td><td><?=htmlspecialchars($r['points'])?></td></tr>
  <?php endforeach; ?>
</table>
</body></html>
