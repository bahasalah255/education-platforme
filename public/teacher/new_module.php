<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
verify_csrf();
$message = '';
// load students for optional enrollment
$students = $pdo->query("SELECT id,username,display_name FROM users WHERE role='student' ORDER BY display_name")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $enroll_all = !empty($_POST['enroll_all']);
  $enroll_students = $_POST['enroll_students'] ?? [];
  if ($title !== '') {
    $stmt = $pdo->prepare('INSERT INTO modules (title, description) VALUES (?,?)');
    $stmt->execute([$title, $desc]);
    $module_id = $pdo->lastInsertId();
    // auto-create a Pretest unit for this module
    $u = $pdo->prepare('INSERT INTO units (module_id, title, content_html, `order`) VALUES (?,?,?,?)');
    $u->execute([$module_id, 'Pretest', '<p>Pretest for module</p>', 0]);
    $enrolled = 0;
    if ($enroll_all) {
      $allStudents = $pdo->query("SELECT id FROM users WHERE role='student'")->fetchAll();
      $ins = $pdo->prepare('INSERT INTO user_courses (user_id, module_id) VALUES (?,?)');
      foreach ($allStudents as $st) {
        // avoid duplicate enrollments
        $chk = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND module_id = ?');
        $chk->execute([$st['id'], $module_id]);
        if (!$chk->fetch()) { $ins->execute([$st['id'], $module_id]); $enrolled++; }
      }
    } else {
      $ins = $pdo->prepare('INSERT INTO user_courses (user_id, module_id) VALUES (?,?)');
      $chk = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND module_id = ?');
      foreach ($enroll_students as $sid) {
        $sid = (int)$sid;
        $chk->execute([$sid, $module_id]);
        if (!$chk->fetch()) { $ins->execute([$sid, $module_id]); $enrolled++; }
      }
    }
    $message = 'Module and Pretest unit created.' . ($enrolled ? " {$enrolled} student(s) enrolled." : '');
  } else {
    $message = 'Title is required.';
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>New Module</title></head><body>
<a href="/teacher/index.php">Back</a>
<h1>Create Module</h1>
<?php if ($message): ?><p style="color:green"><?=htmlspecialchars($message)?></p><?php endif; ?>
<form method="post" aria-label="Create module form">
  <?= csrf_field() ?>
  <label>Title <input name="title" required aria-required="true"></label><br>
  <label>Description <textarea name="description"></textarea></label><br>
  <fieldset>
    <legend>Enroll students</legend>
    <label><input type="checkbox" name="enroll_all" value="1"> Enroll all students</label><br>
    <label>Select students to enroll (hold Ctrl/Cmd to multi-select):</label><br>
    <select name="enroll_students[]" multiple size="6" style="min-width:320px;">
      <?php foreach($students as $st): ?>
        <option value="<?=htmlspecialchars($st['id'])?>"><?=htmlspecialchars($st['display_name'].' ('.$st['username'].')')?></option>
      <?php endforeach; ?>
    </select>
  </fieldset>
  <button>Create</button>
</form>
</body></html>
