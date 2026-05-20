<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
require_role('teacher');

$stmt = $pdo->query('SELECT * FROM modules ORDER BY `order`');
$modules = $stmt->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Teacher Dashboard</title><link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <h1>Teacher Dashboard</h1>
  <p>
    <a class="btn" href="/teacher/new_module.php">Create module</a>
    <a class="btn" href="/teacher/new_unit.php">Create unit</a>
    <a class="btn" href="/teacher/new_exercise.php">Create exercise</a>
    <a class="btn" href="/teacher/upload_pdf.php">Upload PDF</a>
    <a class="btn" href="/teacher/upload_media.php">Upload Media</a>
    <a class="btn secondary" href="/teacher/export_attempts_csv.php">Export CSV</a>
    <a class="btn ghost" href="/teacher/students.php">Students</a>
  </p>
  <p class="muted"><strong>Accessibility:</strong> keyboard focus styles enabled; forms include ARIA attributes.</p>
  <h2>Modules</h2>
  <div class="grid">
  <?php foreach ($modules as $m): ?>
    <div class="lesson-card card">
      <h3><?=htmlspecialchars($m['title'])?></h3>
      <p class="muted"><?=htmlspecialchars($m['description'])?></p>
      <?php
        $stmt = $pdo->prepare('SELECT * FROM media WHERE module_id = ?'); $stmt->execute([$m['id']]); $files = $stmt->fetchAll();
        if ($files) {
          echo '<ul>'; foreach($files as $f) { echo '<li><a href="'.htmlspecialchars($f['path']).'">'.htmlspecialchars($f['filename']).'</a> uploaded at '.htmlspecialchars($f['uploaded_at']).'</li>'; } echo '</ul>';
        }
      ?>
    </div>
  <?php endforeach; ?>
  </div>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</main>
</body></html>
