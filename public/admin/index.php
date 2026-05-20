<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('admin');
verify_csrf();

// handle role change or enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['change_role']) && !empty($_POST['user_id'])) {
    $uid = (int)$_POST['user_id']; $new = $_POST['role'];
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$new,$uid]);
  }
  if (!empty($_POST['enroll_user']) && !empty($_POST['user_id']) && !empty($_POST['module_id'])) {
    $uid = (int)$_POST['user_id']; $mid = (int)$_POST['module_id'];
    $exists = $pdo->prepare('SELECT id FROM user_courses WHERE user_id=? AND module_id=?'); $exists->execute([$uid,$mid]);
    if (!$exists->fetch()) $pdo->prepare('INSERT INTO user_courses (user_id,module_id) VALUES (?,?)')->execute([$uid,$mid]);
  }
}

$users = $pdo->query('SELECT id,username,display_name,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$modules = $pdo->query('SELECT id,title FROM modules ORDER BY `order`')->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Admin</title></head><body>
<a href="/">Accueil</a>
<h1>Admin Dashboard</h1>
<h2>Users</h2>
<table border="1" cellpadding="6"><tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Actions</th></tr>
<?php foreach($users as $u): ?>
  <tr>
    <td><?=htmlspecialchars($u['id'])?></td>
    <td><?=htmlspecialchars($u['username'])?></td>
    <td><?=htmlspecialchars($u['display_name'])?></td>
    <td><?=htmlspecialchars($u['role'])?></td>
    <td>
      <form method="post" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <select name="role"><option value="student">student</option><option value="teacher">teacher</option><option value="admin">admin</option></select>
        <button name="change_role" value="1">Change role</button>
      </form>
      <form method="post" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
        <select name="module_id"><?php foreach($modules as $m): ?><option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option><?php endforeach; ?></select>
        <button name="enroll_user" value="1">Enroll</button>
      </form>
    </td>
  </tr>
<?php endforeach; ?></table>
</body></html>
