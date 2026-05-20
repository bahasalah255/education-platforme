<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: /teacher/index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM modules WHERE id = ?'); $stmt->execute([$id]); $module = $stmt->fetch();
if (!$module) { header('Location: /teacher/index.php'); exit; }

$error = ''; $message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $action = $_POST['action'] ?? 'save';
  if ($action === 'save') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $order = (int)($_POST['order'] ?? 0);
    if ($title === '') { $error = 'Le titre est requis.'; }
    else {
      // handle possible new cover image
      if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $updir = __DIR__.'/../../public/uploads/modules';
        if (!is_dir($updir)) mkdir($updir,0755,true);
        $fname = time().'_'.preg_replace('/[^a-z0-9.\-_]/i','',basename($_FILES['cover_image']['name']));
        $dst = $updir.'/'.$fname;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'],$dst)) {
          $cover_path = '/uploads/modules/'.$fname;
        }
      } else { $cover_path = $module['cover_image'] ?? null; }

      $u = $pdo->prepare('UPDATE modules SET title=?, description=?, `order`=?, cover_image=? WHERE id=?');
      $u->execute([$title,$desc,$order,$cover_path,$id]);
      $message = 'Module mis à jour.';
      // refresh module
      $stmt->execute([$id]); $module = $stmt->fetch();
    }
  } elseif ($action === 'delete') {
    // delete module cascade will remove units/exercises
    $d = $pdo->prepare('DELETE FROM modules WHERE id = ?'); $d->execute([$id]);
    header('Location: /teacher/index.php'); exit;
  }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Modifier le module</title>
<link rel="stylesheet" href="/assets/css/redesign.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script>function confirmDelete(){ return confirm('Supprimer ce module et tout son contenu ?'); }</script>
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/teacher/index.php">Retour</a>
  <h1>Modifier le module</h1>

  <div style="display:flex;gap:20px;align-items:flex-start">
    <div class="card" style="flex:1;max-width:720px">
      <?php if ($error): ?><p style="color:var(--rd-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <?php if ($message): ?><p style="color:var(--rd-success)"><?=htmlspecialchars($message)?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Titre</label>
          <input class="form-control" name="title" required value="<?=htmlspecialchars($module['title'])?>">
        </div>

        <div class="form-group">
          <label>Remplacer l'image d'affiche</label>
          <input class="form-control" name="cover_image" type="file" accept="image/*">
        </div>

        <?php if (!empty($module['cover_image'])): ?>
          <div class="form-group" style="margin-top:6px">
            <label>Image actuelle</label>
            <div><img src="<?=htmlspecialchars($module['cover_image'])?>" alt="Cover" style="width:280px;border-radius:10px;object-fit:cover"></div>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label>Description</label>
          <textarea class="form-control" name="description" rows="5"><?=htmlspecialchars($module['description'])?></textarea>
        </div>

        <div class="form-group">
          <label>Ordre (facultatif)</label>
          <input class="form-control" name="order" type="number" min="0" value="<?=htmlspecialchars($module['order'])?>">
        </div>

        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn" type="submit" name="action" value="save">Enregistrer</button>
          <a class="btn secondary" href="/teacher/index.php">Annuler</a>
          <button class="btn ghost" type="submit" name="action" value="delete" onclick="return confirmDelete();">Supprimer</button>
        </div>
      </form>
    </div>

    <aside style="width:300px">
      <div class="card" style="text-align:center;padding:16px">
        <svg width="120" height="120" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
          <defs><linearGradient id="gmod" x1="0" x2="1"><stop offset="0" stop-color="#ffd166"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs>
          <rect x="20" y="36" width="120" height="68" rx="10" fill="url(#gmod)" />
          <circle cx="60" cy="70" r="8" fill="#fff" />
          <circle cx="100" cy="70" r="8" fill="#fff" />
        </svg>
        <h4 style="margin-top:10px">Conseil</h4>
        <p class="muted" style="font-size:14px">Choisissez une image claire et descriptive pour attirer les apprenants.</p>
      </div>
    </aside>
  </div>

</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
