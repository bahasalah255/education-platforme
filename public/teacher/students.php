<?php
require_once __DIR__.'/../../src/auth.php';
require_login(); require_role('teacher');
$stmt = $pdo->query("SELECT id,username,display_name,points,created_at FROM users WHERE role='student' ORDER BY points DESC");
$students = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Étudiants</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/teacher/index.php">Retour</a>
  <h1>Étudiants</h1>
  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>Pseudo</th><th>Nom</th><th>Points</th><th>Inscrit</th><th>Progression</th><th>Remédiation</th></tr></thead>
      <tbody>
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
          <td><?=htmlspecialchars($stats['cnt'].' tentatives, score '.$stats['total_score'])?></td>
          <td><a class="btn secondary" href="/teacher/student_progress.php?user_id=<?=urlencode($s['id'])?>">Voir</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
