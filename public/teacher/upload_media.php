<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
verify_csrf();

$mods = $pdo->query('SELECT id,title FROM modules ORDER BY `order`')->fetchAll();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $module_id = (int)($_POST['module_id'] ?? 0);
  if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
    $message = 'Please select a file.';
  } else {
    $f = $_FILES['media'];
    $mime = mime_content_type($f['tmp_name']);
    $allowed = ['image/png','image/jpeg','image/jpg','image/gif','audio/mpeg','audio/ogg','audio/wav'];
    if (!in_array($mime, $allowed)) {
      $message = 'Only images and audio files are allowed.';
    } else {
      $dstDir = __DIR__.'/../uploads';
      if (!is_dir($dstDir)) mkdir($dstDir, 0755, true);
      $basename = bin2hex(random_bytes(8)).'-'.basename($f['name']);
      $dst = $dstDir.'/'.$basename;
      if (move_uploaded_file($f['tmp_name'], $dst)) {
        $stmt = $pdo->prepare('INSERT INTO media (module_id,filename,path,mime,uploaded_by) VALUES (?,?,?,?,?)');
        $stmt->execute([$module_id, $f['name'], '/uploads/'.$basename, $mime, $_SESSION['user_id']]);
        $message = 'File uploaded.';
      } else { $message = 'Upload failed.'; }
    }
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Upload Media</title></head><body>
<a href="/teacher/index.php">Back</a>
<h1>Upload Image / Audio</h1>
<?php if ($message): ?><p><?=htmlspecialchars($message)?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label>Module <select name="module_id"><?php foreach($mods as $m): ?><option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option><?php endforeach; ?></select></label><br>
  <label>File <input type="file" name="media" accept="image/*,audio/*"></label><br>
  <button>Upload</button>
</form>
</body></html>
