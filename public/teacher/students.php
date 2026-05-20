<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');
$stmt = $pdo->query("SELECT id,username,display_name,points,created_at FROM users WHERE role='student' ORDER BY points DESC");
$students = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Students</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<a href="/teacher/index.php">Back</a>
<h1>Students</h1>
<table border="1" cellpadding="6">
  <tr><th>ID</th><th>Username</th><th>Name</th><th>Points</th><th>Joined</th><th>Progress</th><th>Remediation</th></tr>
  <?php foreach($students as $s):
    $pstmt = $pdo->prepare('SELECT COUNT(*) AS cnt, SUM(score) AS total_score FROM attempts WHERE user_id = ?');
    $pstmt->execute([$s['id']]); $stats = $pstmt->fetch();
  ?>
  <tr>
    <td><?=htmlspecialchars($s['id'])?></td>
    <td><?=htmlspecialchars($s['username'])?></td>
    <td><?=htmlspecialchars($s['display_name'])?></td>
    <td><?=htmlspecialchars($s['points'])?></td>
    <td><?=htmlspecialchars($s['created_at'])?></td>
    <td><?=htmlspecialchars($stats['cnt'].' attempts, score '.$stats['total_score'])?></td>
    <td><a href="/teacher/student_progress.php?user_id=<?=urlencode($s['id'])?>">View</a></td>
  </tr>
  <?php endforeach; ?>
</table>
</body></html>
