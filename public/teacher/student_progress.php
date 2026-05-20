<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$user_id) { echo 'User not specified'; exit; }

$ustmt = $pdo->prepare('SELECT id,username,display_name FROM users WHERE id = ? AND role = "student"');
$ustmt->execute([$user_id]); $user = $ustmt->fetch();
if (!$user) { echo 'Student not found'; exit; }

$stmt = $pdo->prepare('SELECT p.*, u.title AS unit_title, m.title AS module_title FROM progress p JOIN units u ON u.id=p.unit_id JOIN modules m ON m.id=u.module_id WHERE p.user_id = ?');
$stmt->execute([$user_id]); $rows = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Student Progress</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<a href="/teacher/students.php">Back</a>
<h1>Progress for <?=htmlspecialchars($user['display_name'])?> (<?=htmlspecialchars($user['username'])?>)</h1>
<h2>Units needing remediation</h2>
<ul>
<?php foreach($rows as $r): if ($r['needs_remediation']): ?>
  <li><?=htmlspecialchars($r['module_title'].' — '.$r['unit_title'])?> — score <?=htmlspecialchars($r['score'].'/'.$r['max_score'])?> — <a href="/lesson.php?unit_id=<?=urlencode($r['unit_id'])?>&remediation=1">Start remediation</a></li>
<?php endif; endforeach; ?>
</ul>
<h2>All units</h2>
<ul>
<?php foreach($rows as $r): ?>
  <li><?=htmlspecialchars($r['module_title'].' — '.$r['unit_title'])?> — score <?=htmlspecialchars($r['score'].'/'.$r['max_score'])?> — remediation: <?= $r['needs_remediation'] ? 'yes' : 'no'?></li>
<?php endforeach; ?>
</ul>
</body></html>
