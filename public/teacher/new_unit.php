<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login(); require_role('teacher');
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
    header('Location: /teacher/index.php'); exit;
  }
  }
?>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">
  <a class="btn ghost" href="/teacher/index.php">Retour</a>
  <h1>Ajouter une unité</h1>
  <div style="display:flex;gap:20px;align-items:flex-start">
    <div class="card" style="flex:1;max-width:740px">
      <?php if ($error): ?><p style="color:var(--color-danger)"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form method="post" onsubmit="syncEditor()">
        <?= csrf_field() ?>
        <div class="form-group">
          <label>Module</label>
          <select class="form-control" name="module_id" required>
            <option value="">-- choisir --</option>
            <?php foreach($modules as $m): ?>
              <option value="<?= $m['id'] ?>"><?=htmlspecialchars($m['title'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Titre de l'unité</label>
          <input class="form-control" name="title" required placeholder="Ex: Unité 1 — Les articles">
        </div>

        <div class="form-group">
          <label>Contenu (HTML)</label>
          <div id="editor" contenteditable="true" class="form-control" style="min-height:160px;">&lt;p&gt;Contenu de la leçon...&lt;/p&gt;</div>
          <textarea name="content_html" id="content_html" rows="6" style="display:none"></textarea>
        </div>

        <div class="form-group">
          <label>Ordre</label>
          <input class="form-control" name="order" type="number" min="0" value="0">
        </div>

        <div style="margin-top:12px;display:flex;gap:8px">
          <button class="btn" type="submit">Créer l'unité</button>
          <a class="btn secondary" href="/teacher/index.php">Annuler</a>
        </div>
      </form>
    </div>

    <aside style="width:300px">
      <div class="card" style="text-align:center;padding:16px">
        <svg width="120" height="120" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
          <defs><linearGradient id="g3" x1="0" x2="1"><stop offset="0" stop-color="#ffd166"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs>
          <rect x="20" y="36" width="120" height="68" rx="10" fill="url(#g3)" />
          <circle cx="60" cy="70" r="8" fill="#fff" />
          <circle cx="100" cy="70" r="8" fill="#fff" />
        </svg>
        <h4 style="margin-top:10px">Conseil</h4>
        <p class="muted" style="font-size:14px">Utilisez l'éditeur pour coller du HTML simple (paragraphes, listes, images).</p>
      </div>
    </aside>
  </div>
</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>

<script>
function syncEditor(){
  var e = document.getElementById('editor');
  document.getElementById('content_html').value = e.innerHTML;
}
</script>
</body></html>
