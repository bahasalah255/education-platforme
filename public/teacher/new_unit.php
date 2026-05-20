<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
verify_csrf();
$message = '';
$mods = $pdo->query('SELECT id,title FROM modules ORDER BY `order`')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $module_id = (int)($_POST['module_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $content = $_POST['content_html'] ?? '';
  if ($module_id > 0 && $title !== '') {
    $stmt = $pdo->prepare('INSERT INTO units (module_id, title, content_html) VALUES (?,?,?)');
    $stmt->execute([$module_id, $title, $content]);
    $message = 'Unit created.';
  } else {
    $message = 'Please select module and provide title.';
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>New Unit</title></head><body>
<a href="/teacher/index.php">Back</a>
<h1>Create Unit</h1>
<?php if ($message): ?><p style="color:green"><?=htmlspecialchars($message)?></p><?php endif; ?>
<form method="post" onsubmit="syncEditor()" aria-label="Create unit form">
  <?= csrf_field() ?>
  <label>Module <select name="module_id" aria-required="true"><?php foreach($mods as $m): ?><option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option><?php endforeach; ?></select></label><br>
  <label>Title <input name="title" required></label><br>
  <label>Content HTML</label><br>
  <div id="editor" contenteditable="true" style="border:1px solid #ccc;padding:8px;min-height:120px;">Write lesson content here...</div>
  <textarea name="content_html" id="content_html" rows="6" style="display:none"></textarea><br>
  <button>Create</button>
</form>
<script>
function syncEditor(){
  var e = document.getElementById('editor');
  document.getElementById('content_html').value = e.innerHTML;
}
</script>
</body></html>
