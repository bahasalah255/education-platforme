<?php
require_once __DIR__.'/../../src/auth.php';
require_once __DIR__.'/../../src/csrf.php';
require_login();
// Allow teacher OR admin
$user = current_user();
if (!in_array($user['role'], ['teacher','admin'])) {
  http_response_code(403); echo 'Accès refusé.'; exit;
}

// Fetch all modules with their units for the cascade select
$modsRaw = $pdo->query('SELECT id, title FROM modules ORDER BY `order`')->fetchAll();
$allUnits = $pdo->query('SELECT id, module_id, title FROM units ORDER BY module_id, `order`')->fetchAll();

// Group units by module
$unitsByMod = [];
foreach ($allUnits as $u) $unitsByMod[$u['module_id']][] = $u;

// Fetch existing resources
$resources = $pdo->query(
  'SELECT m.*, u.title AS unit_title, mod.title AS mod_title
   FROM media m
   LEFT JOIN units u ON u.id = m.unit_id
   LEFT JOIN modules mod ON mod.id = m.module_id
   ORDER BY m.uploaded_at DESC LIMIT 100'
)->fetchAll();

$message = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $action = $_POST['action'] ?? 'upload';

  if ($action === 'delete') {
    $mid = (int)($_POST['media_id'] ?? 0);
    $row = $pdo->prepare('SELECT path FROM media WHERE id = ?');
    $row->execute([$mid]); $r = $row->fetch();
    if ($r) {
      $fullPath = __DIR__.'/../..'.$r['path'];
      if (file_exists($fullPath)) @unlink($fullPath);
      $pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$mid]);
      $message = 'Ressource supprimée.';
    }
  } else {
    $module_id = (int)($_POST['module_id'] ?? 0);
    $unit_id   = (int)($_POST['unit_id'] ?? 0) ?: null;
    $title_res = trim($_POST['res_title'] ?? '');
    $desc_res  = trim($_POST['res_description'] ?? '');

    if (!isset($_FILES['resource']) || $_FILES['resource']['error'] !== UPLOAD_ERR_OK) {
      $message = '❌ Veuillez sélectionner un fichier.'; $msgType = 'error';
    } elseif (!$module_id) {
      $message = '❌ Sélectionnez un module.'; $msgType = 'error';
    } else {
      $f    = $_FILES['resource'];
      $mime = mime_content_type($f['tmp_name']);
      $allowed = [
        'application/pdf',
        'video/mp4','video/webm','video/ogg','video/avi','video/quicktime',
        'image/png','image/jpeg','image/gif','image/webp',
        'audio/mpeg','audio/ogg','audio/wav',
      ];
      if (!in_array($mime, $allowed)) {
        $message = '❌ Type de fichier non autorisé : '.$mime; $msgType = 'error';
      } else {
        $dstDir = realpath(__DIR__.'/../uploads');
        if (!$dstDir || !is_dir($dstDir)) {
          mkdir(__DIR__.'/../uploads', 0755, true);
          $dstDir = realpath(__DIR__.'/../uploads');
        }
        $ext      = pathinfo($f['name'], PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)).'.'.$ext;
        $dst      = $dstDir.'/'.$basename;
        if (move_uploaded_file($f['tmp_name'], $dst)) {
          $stmt = $pdo->prepare(
            'INSERT INTO media (module_id, unit_id, filename, title, description, path, mime, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?)'
          );
          $stmt->execute([
            $module_id, $unit_id,
            $f['name'],
            $title_res ?: $f['name'],
            $desc_res,
            '/uploads/'.$basename,
            $mime,
            $_SESSION['user_id'],
          ]);
          $message = '✅ Ressource ajoutée avec succès !';
          header('Location: /teacher/upload_resources.php?ok=1'); exit;
        } else {
          $message = '❌ Échec de l\'upload. Vérifiez les permissions du dossier uploads.'; $msgType = 'error';
        }
      }
    }
  }
}
if (isset($_GET['ok'])) { $message = '✅ Ressource ajoutée avec succès !'; $msgType = 'success'; }
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Gestion des Ressources · Formateur</title>
  <link rel="stylesheet" href="/assets/css/redesign.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    .upload-zone {
      border: 2.5px dashed var(--rd-border);
      border-radius: 20px;
      padding: 36px 24px;
      text-align: center;
      transition: border-color .2s, background .2s;
      cursor: pointer;
      background: var(--rd-bg);
      position: relative;
    }
    .upload-zone:hover, .upload-zone.drag-over {
      border-color: var(--rd-primary);
      background: var(--rd-primary-light);
    }
    .upload-zone input[type=file] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .upload-icon { font-size: 3rem; margin-bottom: 12px; }
    .res-card {
      display: flex; align-items: center; gap: 14px;
      padding: 14px 18px; border-radius: 14px;
      border: 1px solid var(--rd-border);
      background: var(--rd-surface);
      transition: box-shadow .2s, transform .2s;
    }
    .res-card:hover { box-shadow: var(--rd-shadow); transform: translateY(-2px); }
    .res-icon { font-size: 2rem; width: 52px; height: 52px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .res-icon.pdf   { background: #fef2f2; }
    .res-icon.video { background: #eff6ff; }
    .res-icon.image { background: #f0fdf4; }
    .res-icon.audio { background: #fffbeb; }
    .badge-type { font-size: 11px; font-weight: 800; padding: 3px 10px;
      border-radius: 999px; text-transform: uppercase; letter-spacing: .08em; }
    .badge-pdf   { background: #fee2e2; color: #b91c1c; }
    .badge-video { background: var(--rd-primary-light); color: var(--rd-primary); }
    .badge-image { background: var(--rd-success-light); color: var(--rd-success-dark); }
    .badge-audio { background: var(--rd-warning-light); color: var(--rd-warning-dark); }
    .alert-ok  { background: var(--rd-success-light); border: 1px solid rgba(16,185,129,.3); color: #065f46; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-weight: 600; }
    .alert-err { background: var(--rd-danger-light);  border: 1px solid rgba(239,68,68,.3);  color: #991b1b; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-weight: 600; }
    #unitSelect option[data-mod] { display: none; }
  </style>
  <script src="/assets/js/ui.js" defer></script>
</head>
<body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>
<main class="app-container">

  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px;flex-wrap:wrap" class="rd-anim">
    <div>
      <h1 style="margin-bottom:4px">📁 Ressources pédagogiques</h1>
      <p class="muted" style="margin:0">Ajoutez des vidéos et PDFs pour vos élèves — organisés par module et unité.</p>
    </div>
    <a class="btn secondary" href="/teacher/index.php">← Tableau de bord</a>
  </div>

  <?php if ($message): ?>
    <div class="<?= $msgType === 'error' ? 'alert-err' : 'alert-ok' ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start" class="rd-anim rd-d1">

    <!-- Upload Form -->
    <div class="card">
      <h2 style="margin-bottom:20px;font-size:1.1rem">➕ Ajouter une ressource</h2>
      <form method="post" enctype="multipart/form-data" id="uploadForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div>
            <label for="module_id">📚 Module</label>
            <select name="module_id" id="module_id" required onchange="filterUnits(this.value)">
              <option value="">— Choisir un module —</option>
              <?php foreach ($modsRaw as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="unit_id">📄 Unité (optionnel)</label>
            <select name="unit_id" id="unit_id">
              <option value="">— Toutes les unités —</option>
              <?php foreach ($allUnits as $u): ?>
                <option value="<?= $u['id'] ?>" data-mod="<?= $u['module_id'] ?>"><?= htmlspecialchars($u['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="margin-top:16px">
          <label for="res_title">📝 Titre de la ressource</label>
          <input type="text" name="res_title" id="res_title" placeholder="ex: Vidéo — Leçon 1 : Les articles définis" required>
        </div>

        <div style="margin-top:12px">
          <label for="res_description">💬 Description (optionnel)</label>
          <textarea name="res_description" id="res_description" rows="2" placeholder="Brève description de la ressource..."></textarea>
        </div>

        <div style="margin-top:16px">
          <label>📎 Fichier (PDF, Vidéo MP4/WEBM, Image, Audio)</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="resource" id="resourceFile" required
                   accept=".pdf,.mp4,.webm,.ogg,.avi,.mov,.png,.jpg,.jpeg,.gif,.webp,.mp3,.wav"
                   onchange="previewFile(this)">
            <div class="upload-icon" id="uploadIcon">📂</div>
            <div style="font-weight:700;font-size:15px" id="uploadLabel">Cliquez ou glissez votre fichier ici</div>
            <div class="muted" id="uploadSub" style="font-size:13px;margin-top:6px">PDF · MP4 · WEBM · MP3 · PNG · JPG</div>
          </div>
        </div>

        <button class="btn" type="submit" style="width:100%;margin-top:20px;padding:14px!important;font-size:15px!important">
          🚀 Publier la ressource
        </button>
      </form>
    </div>

    <!-- Quick Stats -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="card" style="background:linear-gradient(135deg,var(--rd-primary-light),#f0fdf4)!important">
        <div style="font-size:2.5rem;margin-bottom:8px">💡</div>
        <h3 style="margin-bottom:8px">Conseils formateur</h3>
        <ul style="padding-left:18px;margin:0;color:var(--rd-muted);font-size:14px;line-height:1.8">
          <li>Préférez les vidéos courtes <strong>(2–5 min)</strong></li>
          <li>Organisez par unité pour que l'élève retrouve facilement</li>
          <li>Ajoutez un titre clair — ex: <em>"Leçon 3 : les articles"</em></li>
          <li>Les PDFs s'ouvrent directement dans le navigateur</li>
        </ul>
      </div>

      <div class="card">
        <h3 style="margin-bottom:12px">📊 Statistiques</h3>
        <?php
          $stats = $pdo->query('SELECT mime, COUNT(*) as cnt FROM media GROUP BY mime')->fetchAll();
          $counts = ['pdf'=>0,'video'=>0,'audio'=>0,'image'=>0,'other'=>0];
          foreach ($stats as $s) {
            if (str_contains($s['mime'],'pdf')) $counts['pdf'] += $s['cnt'];
            elseif (str_contains($s['mime'],'video')) $counts['video'] += $s['cnt'];
            elseif (str_contains($s['mime'],'audio')) $counts['audio'] += $s['cnt'];
            elseif (str_contains($s['mime'],'image')) $counts['image'] += $s['cnt'];
            else $counts['other'] += $s['cnt'];
          }
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div style="text-align:center;padding:12px;background:var(--rd-bg);border-radius:12px">
            <div style="font-size:1.8rem;font-weight:900;color:#b91c1c"><?= $counts['pdf'] ?></div>
            <div class="muted" style="font-size:12px">PDFs</div>
          </div>
          <div style="text-align:center;padding:12px;background:var(--rd-bg);border-radius:12px">
            <div style="font-size:1.8rem;font-weight:900;color:var(--rd-primary)"><?= $counts['video'] ?></div>
            <div class="muted" style="font-size:12px">Vidéos</div>
          </div>
          <div style="text-align:center;padding:12px;background:var(--rd-bg);border-radius:12px">
            <div style="font-size:1.8rem;font-weight:900;color:var(--rd-success)"><?= $counts['image'] ?></div>
            <div class="muted" style="font-size:12px">Images</div>
          </div>
          <div style="text-align:center;padding:12px;background:var(--rd-bg);border-radius:12px">
            <div style="font-size:1.8rem;font-weight:900;color:var(--rd-warning)"><?= $counts['audio'] ?></div>
            <div class="muted" style="font-size:12px">Audios</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Resources List -->
  <div class="card rd-anim rd-d2" style="margin-top:28px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2 style="margin:0;font-size:1.1rem">📋 Ressources publiées (<?= count($resources) ?>)</h2>
      <input type="text" id="searchRes" placeholder="🔍 Rechercher..." style="max-width:220px;padding:8px 12px!important">
    </div>

    <?php if (empty($resources)): ?>
      <div style="text-align:center;padding:48px">
        <div style="font-size:4rem;margin-bottom:12px">📭</div>
        <p class="muted">Aucune ressource publiée pour le moment. Ajoutez votre première vidéo ou PDF ci-dessus !</p>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px" id="resList">
        <?php foreach ($resources as $r):
          if (str_contains($r['mime'],'pdf')) { $typeLabel='PDF'; $typeClass='pdf'; $icon='📄'; }
          elseif (str_contains($r['mime'],'video')) { $typeLabel='Vidéo'; $typeClass='video'; $icon='🎬'; }
          elseif (str_contains($r['mime'],'audio')) { $typeLabel='Audio'; $typeClass='audio'; $icon='🎵'; }
          elseif (str_contains($r['mime'],'image')) { $typeLabel='Image'; $typeClass='image'; $icon='🖼️'; }
          else { $typeLabel='Fichier'; $typeClass='image'; $icon='📎'; }
        ?>
        <div class="res-card" data-search="<?= htmlspecialchars(strtolower($r['title'].' '.$r['mod_title'].' '.($r['unit_title']??''))) ?>">
          <div class="res-icon <?= $typeClass ?>"><?= $icon ?></div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
              <strong style="font-size:15px"><?= htmlspecialchars($r['title'] ?: $r['filename']) ?></strong>
              <span class="badge-type badge-<?= $typeClass ?>"><?= $typeLabel ?></span>
            </div>
            <?php if ($r['description']): ?>
              <div class="muted" style="font-size:13px;margin-bottom:4px"><?= htmlspecialchars($r['description']) ?></div>
            <?php endif; ?>
            <div style="font-size:12px;color:var(--rd-muted)">
              📚 <?= htmlspecialchars($r['mod_title'] ?? '—') ?>
              <?php if ($r['unit_title']): ?> · 📄 <?= htmlspecialchars($r['unit_title']) ?><?php endif; ?>
              · 🕐 <?= date('d/m/Y H:i', strtotime($r['uploaded_at'])) ?>
            </div>
          </div>
          <div style="display:flex;gap:8px;flex-shrink:0">
            <a href="<?= htmlspecialchars($r['path']) ?>" target="_blank"
               class="btn secondary" style="padding:7px 14px!important;font-size:13px!important">
              <?= $typeClass === 'video' ? '▶️ Voir' : '👁 Ouvrir' ?>
            </a>
            <form method="post" style="margin:0" onsubmit="return confirm('Supprimer cette ressource ?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="media_id" value="<?= $r['id'] ?>">
              <button class="btn ghost" style="padding:7px 10px!important;color:var(--rd-danger)!important;border:1px solid rgba(239,68,68,.2)!important">🗑️</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>
<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>

<script>
// Filter units based on module selection
const allUnitOptions = [...document.querySelectorAll('#unit_id option[data-mod]')];
function filterUnits(modId) {
  allUnitOptions.forEach(o => {
    o.style.display = (!modId || o.dataset.mod === modId) ? '' : 'none';
  });
  document.getElementById('unit_id').value = '';
}

// File preview
function previewFile(input) {
  if (!input.files.length) return;
  const f = input.files[0];
  const icons = { 'pdf':'📄', 'video':'🎬', 'audio':'🎵', 'image':'🖼️' };
  let icon = '📎';
  for (const [k,v] of Object.entries(icons)) if (f.type.includes(k)) { icon = v; break; }
  document.getElementById('uploadIcon').textContent = icon;
  document.getElementById('uploadLabel').textContent = f.name;
  document.getElementById('uploadSub').textContent = (f.size/1024/1024).toFixed(1)+' MB';
  document.getElementById('uploadZone').style.borderColor = 'var(--rd-primary)';
}

// Search filter
document.getElementById('searchRes')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#resList .res-card').forEach(card => {
    card.style.display = card.dataset.search.includes(q) ? '' : 'none';
  });
});

// Drag & drop
const zone = document.getElementById('uploadZone');
zone?.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone?.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('drag-over');
  const dt = e.dataTransfer;
  if (dt.files.length) {
    document.getElementById('resourceFile').files = dt.files;
    previewFile(document.getElementById('resourceFile'));
  }
});
</script>
</body>
</html>
