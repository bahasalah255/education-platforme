<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('admin');
verify_csrf();

$error = '';
$modules = $pdo->query('SELECT id,title FROM modules ORDER BY `order`')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $module_id = (int)($_POST['module_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content_html'] ?? '');
  $order = (int)($_POST['order'] ?? 0);
  if ($module_id <= 0 || $title === '') { $error = 'Module et titre requis.'; }
  else {
    $stmt = $pdo->prepare('INSERT INTO units (module_id, title, content_html, `order`) VALUES (?, ?, ?, ?)');
    $stmt->execute([$module_id, $title, $content, $order]);
    header('Location: /admin/index.php'); exit;
  }
}
?><!doctype html>
<html><head><meta charset="utf-8"><title>Ajouter une unité</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/admin/index.php">Retour</a>
  <h1>Ajouter une unité</h1>
  <div class="card" style="max-width:820px">
    <?php if ($error): ?><p style="color:var(--color-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Module
        <select name="module_id" required>
          <option value="">-- choisir --</option>
          <?php foreach($modules as $m): ?>
            <option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Titre de l'unité
        <input name="title" required placeholder="Ex: Unité 1 — Les articles">
      </label>
      <label>Contenu (HTML)
        <textarea name="content_html" rows="6" placeholder="Contenu de la leçon en HTML"></textarea>
      </label>
      <label>Ordre
        <input name="order" type="number" min="0" value="0">
      </label>
      <div style="margin-top:12px;display:flex;gap:8px">
        <button class="btn" type="submit">Créer l'unité</button>
        <a class="btn secondary" href="/admin/index.php">Annuler</a>
      </div>
    </form>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
</body></html>
