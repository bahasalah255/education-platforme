<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
verify_csrf();
$message = '';
$units = $pdo->query('SELECT id,title FROM units ORDER BY `order`')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $unit_id = (int)($_POST['unit_id'] ?? 0);
  $type = $_POST['type'] ?? 'mcq';
  $data = $_POST['data_json'] ?? '[]';
  // validate JSON
  $json = json_decode($data, true);
  if ($json === null && $data !== 'null') {
    $message = 'Invalid JSON';
  } elseif ($unit_id <= 0) {
    $message = 'Please select unit.';
  } else {
    $stmt = $pdo->prepare('INSERT INTO exercises (unit_id, type, data) VALUES (?,?,?)');
    $stmt->execute([$unit_id, $type, json_encode($json)]);
    $message = 'Exercise created.';
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>New Exercise</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script></head><body>
<a href="/teacher/index.php">Back</a>
<h1>Create Exercise</h1>
<?php if ($message): ?><p style="color:green"><?=htmlspecialchars($message)?></p><?php endif; ?>
<form method="post" aria-label="Create exercise form">
  <?= csrf_field() ?>
  <label>Unit <select name="unit_id" aria-required="true"><?php foreach($units as $u): ?><option value="<?= $u['id'] ?>"><?=htmlspecialchars($u['title'])?></option><?php endforeach; ?></select></label><br>
  <label>Type <select name="type"><option value="mcq">mcq</option><option value="dragdrop">dragdrop</option><option value="pretest">pretest</option><option value="posttest">posttest</option></select></label><br>
  <label>Data (JSON format)<br><textarea name="data_json" rows="12">[
  {"prompt":"Question?","choices":[{"text":"A","correct":1},{"text":"B","correct":0}]}
]</textarea></label><br>
  <p>For dragdrop, use format: [{"prompt":"Associer","items":[{"id":"i1","label":"chien"},...],"targets":[{"id":"t1","label":"animal","match":"i1"},...]}]</p>
  <h3>Available media</h3>
  <p>Upload images or audio via the <a href="/teacher/upload_media.php">Upload Image/Audio</a> page. You can reference uploaded media in your exercise JSON using the media path, e.g. {"media":"/uploads/abcd-file.jpg"} inside a question object.</p>
  <?php
    // list media grouped by module
    $media = $pdo->query('SELECT m.*, mo.title AS module_title FROM media m JOIN modules mo ON mo.id=m.module_id ORDER BY mo.`order`, m.uploaded_at DESC')->fetchAll();
    if ($media):
      echo '<ul>';
      foreach($media as $mm) {
        echo '<li>'.htmlspecialchars($mm['module_title']).': <a href="'.htmlspecialchars($mm['path']).'">'.htmlspecialchars($mm['filename']).'</a> ('.htmlspecialchars($mm['mime']).')</li>';
      }
      echo '</ul>';
    endif;
  ?>
  <button>Create</button>
</form>
</body></html>
