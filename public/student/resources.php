<?php
require_once __DIR__.'/../../src/auth.php';
require_login();
$user = current_user();

// Get module_id or unit_id filter
$filterModId  = isset($_GET['module_id']) ? (int)$_GET['module_id']  : null;
$filterUnitId = isset($_GET['unit_id'])   ? (int)$_GET['unit_id']    : null;

// Build query
$where = '1=1';
$params = [];
if ($filterModId)  { $where .= ' AND m.module_id = ?'; $params[] = $filterModId; }
if ($filterUnitId) { $where .= ' AND m.unit_id   = ?'; $params[] = $filterUnitId; }

$stmt = $pdo->prepare(
  "SELECT m.*, u.title AS unit_title, mod.title AS mod_title
   FROM media m
   LEFT JOIN units u   ON u.id = m.unit_id
   LEFT JOIN modules mod ON mod.id = m.module_id
   WHERE $where
   ORDER BY m.uploaded_at DESC"
);
$stmt->execute($params);
$resources = $stmt->fetchAll();

// Modules this student is enrolled in
$modStmt = $pdo->prepare(
  'SELECT mod.id, mod.title FROM modules mod
   JOIN user_courses uc ON uc.module_id = mod.id
   WHERE uc.user_id = ? ORDER BY mod.`order`'
);
$modStmt->execute([$user['id']]);
$myModules = $modStmt->fetchAll();

// Group resources by type
$videos = array_filter($resources, fn($r) => str_contains($r['mime'], 'video'));
$pdfs   = array_filter($resources, fn($r) => str_contains($r['mime'], 'pdf'));
$others = array_filter($resources, fn($r) => !str_contains($r['mime'],'video') && !str_contains($r['mime'],'pdf'));
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>📚 Mes Cours · Ma maison est une école</title>
  <link rel="stylesheet" href="/assets/css/redesign.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    :root {
      --kid-purple: #7c3aed;
      --kid-orange: #f97316;
      --kid-green:  #16a34a;
      --kid-blue:   #2563eb;
      --kid-pink:   #db2777;
    }
    .kid-page-hero {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 24px;
      padding: 36px 32px;
      color: white;
      margin-bottom: 28px;
      position: relative;
      overflow: hidden;
    }
    .kid-page-hero::after {
      content: '📖'; font-size: 120px; opacity: .08;
      position: absolute; right: 20px; top: -20px; line-height: 1;
    }
    .kid-page-hero h1 { color: white!important; font-size: 2rem; margin-bottom: 8px; }
    .kid-filter-bar {
      display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px;
    }
    .kid-filter-btn {
      padding: 8px 18px; border-radius: 999px; border: 2px solid var(--rd-border);
      background: var(--rd-surface); font-weight: 700; font-size: 14px;
      cursor: pointer; text-decoration: none; color: var(--rd-text);
      transition: all .2s;
    }
    .kid-filter-btn:hover, .kid-filter-btn.active {
      border-color: var(--kid-purple); background: #f5f3ff; color: var(--kid-purple); opacity: 1;
    }
    .res-section-title {
      display: flex; align-items: center; gap: 10px;
      font-size: 1.2rem; font-weight: 800; margin: 28px 0 16px;
    }
    .res-section-title span { font-size: 1.6rem; }
    /* Video cards */
    .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 20px; }
    .video-card {
      border-radius: 20px; overflow: hidden; background: var(--rd-surface);
      box-shadow: var(--rd-shadow); border: 1px solid var(--rd-border);
      transition: transform .25s, box-shadow .25s;
    }
    .video-card:hover { transform: translateY(-6px); box-shadow: var(--rd-shadow-lg); }
    .video-thumb {
      background: linear-gradient(135deg, #1e3a8a, #3b82f6);
      position: relative; padding-top: 56.25%; /* 16:9 */
      cursor: pointer;
    }
    .video-thumb video, .video-thumb iframe {
      position: absolute; inset: 0; width: 100%; height: 100%; border: 0;
    }
    .video-thumb .play-overlay {
      position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
      background: rgba(0,0,0,.25); transition: background .2s;
    }
    .play-overlay:hover { background: rgba(0,0,0,.1); }
    .play-btn {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(255,255,255,.95);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; box-shadow: 0 4px 20px rgba(0,0,0,.3);
      transition: transform .2s;
    }
    .play-btn:hover { transform: scale(1.12); }
    .video-body { padding: 16px 18px; }
    .video-body h3 { font-size: 15px; font-weight: 800; margin-bottom: 6px; }
    /* PDF cards */
    .pdf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px,1fr)); gap: 16px; }
    .pdf-card {
      border-radius: 16px; padding: 20px 18px;
      background: linear-gradient(135deg, #fff5f5, #fff);
      border: 2px solid #fecaca;
      display: flex; flex-direction: column; gap: 10px;
      transition: transform .2s, box-shadow .2s;
    }
    .pdf-card:hover { transform: translateY(-4px); box-shadow: var(--rd-shadow); }
    .pdf-top { display: flex; align-items: flex-start; gap: 12px; }
    .pdf-icon-big { font-size: 2.8rem; }
    .pdf-card h3 { font-size: 14px; font-weight: 800; margin: 0; line-height: 1.3; }
    .pdf-btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 9px 16px; border-radius: 10px;
      background: #b91c1c; color: white; text-decoration: none;
      font-weight: 700; font-size: 13px; margin-top: auto;
      transition: background .2s;
    }
    .pdf-btn:hover { background: #991b1b; opacity: 1; color: white; }
    /* Modal */
    .modal-bg {
      position: fixed; inset: 0; background: rgba(0,0,0,.8);
      z-index: 9999; display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none; transition: opacity .3s;
    }
    .modal-bg.open { opacity: 1; pointer-events: all; }
    .modal-box {
      background: #000; border-radius: 16px; overflow: hidden;
      width: 90vw; max-width: 900px; max-height: 90vh;
      position: relative;
    }
    .modal-close {
      position: absolute; top: 12px; right: 12px; z-index: 10;
      background: rgba(255,255,255,.2); border: 0; color: white;
      width: 36px; height: 36px; border-radius: 50%; font-size: 18px;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .modal-video { width: 100%; aspect-ratio: 16/9; display: block; }
    .empty-state { text-align: center; padding: 64px 24px; }
    .empty-state .big { font-size: 5rem; margin-bottom: 16px; }
  </style>
  <script src="/assets/js/ui.js" defer></script>
</head>
<body>
<?php require_once __DIR__.'/../../src/partials/header.php'; ?>

<main class="app-container">

  <!-- Hero -->
  <div class="kid-page-hero rd-anim">
    <h1>🎬 Mes Cours & Vidéos</h1>
    <p style="opacity:.9;font-size:1.05rem;margin:0">Regarde les vidéos et ouvre les PDFs pour apprendre à ton rythme !</p>
  </div>

  <!-- Filter bar -->
  <div class="kid-filter-bar">
    <a href="/student/resources.php" class="kid-filter-btn <?= !$filterModId ? 'active' : '' ?>">🌟 Tous</a>
    <?php foreach ($myModules as $mod): ?>
      <a href="/student/resources.php?module_id=<?= $mod['id'] ?>"
         class="kid-filter-btn <?= $filterModId===$mod['id'] ? 'active' : '' ?>">
        📚 <?= htmlspecialchars($mod['title']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($resources)): ?>
    <div class="card empty-state">
      <div class="big">📭</div>
      <h2>Pas encore de ressources</h2>
      <p class="muted">Ton professeur n'a pas encore publié de cours pour ce module.<br>Reviens bientôt !</p>
      <a href="/student/index.php" class="btn" style="margin-top:16px">⬅️ Retour au tableau de bord</a>
    </div>
  <?php else: ?>

    <!-- VIDEOS -->
    <?php if (!empty($videos)): ?>
      <div class="res-section-title"><span>🎬</span> Vidéos (<?= count($videos) ?>)</div>
      <div class="video-grid">
        <?php foreach ($videos as $v): ?>
        <div class="video-card rd-anim">
          <div class="video-thumb">
            <!-- Lazy video preview — click opens modal -->
            <div class="play-overlay" onclick="openVideo('<?= htmlspecialchars($v['path']) ?>', '<?= htmlspecialchars(addslashes($v['title'] ?: $v['filename'])) ?>')" style="cursor:pointer">
              <div style="text-align:center">
                <div class="play-btn">▶</div>
                <div style="color:white;font-size:12px;margin-top:8px;font-weight:700;text-shadow:0 1px 4px rgba(0,0,0,.5)">
                  <?= htmlspecialchars($v['title'] ?: $v['filename']) ?>
                </div>
              </div>
            </div>
          </div>
          <div class="video-body">
            <h3><?= htmlspecialchars($v['title'] ?: $v['filename']) ?></h3>
            <?php if ($v['description']): ?>
              <p class="muted" style="font-size:13px;margin-bottom:8px"><?= htmlspecialchars($v['description']) ?></p>
            <?php endif; ?>
            <div style="font-size:12px;color:var(--rd-muted)">
              📚 <?= htmlspecialchars($v['mod_title'] ?? '—') ?>
              <?php if ($v['unit_title']): ?> · 📄 <?= htmlspecialchars($v['unit_title']) ?><?php endif; ?>
            </div>
            <button onclick="openVideo('<?= htmlspecialchars($v['path']) ?>', '<?= htmlspecialchars(addslashes($v['title'] ?: $v['filename'])) ?>')"
                    class="btn" style="width:100%;margin-top:12px">▶️ Regarder</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- PDFS -->
    <?php if (!empty($pdfs)): ?>
      <div class="res-section-title"><span>📄</span> Documents PDF (<?= count($pdfs) ?>)</div>
      <div class="pdf-grid">
        <?php foreach ($pdfs as $p): ?>
        <div class="pdf-card rd-anim">
          <div class="pdf-top">
            <div class="pdf-icon-big">📄</div>
            <div>
              <h3><?= htmlspecialchars($p['title'] ?: $p['filename']) ?></h3>
              <?php if ($p['description']): ?>
                <p class="muted" style="font-size:12px;margin:4px 0 0"><?= htmlspecialchars($p['description']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div style="font-size:12px;color:var(--rd-muted)">
            📚 <?= htmlspecialchars($p['mod_title'] ?? '—') ?>
            <?php if ($p['unit_title']): ?> · 📄 <?= htmlspecialchars($p['unit_title']) ?><?php endif; ?>
          </div>
          <a href="<?= htmlspecialchars($p['path']) ?>" target="_blank" class="pdf-btn">
            📖 Ouvrir le PDF
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- OTHER FILES -->
    <?php if (!empty($others)): ?>
      <div class="res-section-title"><span>📎</span> Autres fichiers</div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($others as $o):
          $icon = str_contains($o['mime'],'audio') ? '🎵' : (str_contains($o['mime'],'image') ? '🖼️' : '📎');
        ?>
        <div class="card" style="display:flex;align-items:center;gap:16px;padding:14px 18px!important">
          <div style="font-size:2rem"><?= $icon ?></div>
          <div style="flex:1">
            <strong><?= htmlspecialchars($o['title'] ?: $o['filename']) ?></strong>
            <?php if ($o['description']): ?><p class="muted" style="font-size:13px;margin:2px 0 0"><?= htmlspecialchars($o['description']) ?></p><?php endif; ?>
          </div>
          <a href="<?= htmlspecialchars($o['path']) ?>" target="_blank" class="btn secondary" style="flex-shrink:0">Ouvrir</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</main>

<!-- Video Modal -->
<div class="modal-bg" id="videoModal" onclick="closeVideo(event)">
  <div class="modal-box" id="modalBox">
    <button class="modal-close" onclick="closeVideoBtn()">✕</button>
    <video id="modalVideo" class="modal-video" controls controlsList="nodownload">
      Your browser does not support the video tag.
    </video>
  </div>
</div>

<?php require_once __DIR__.'/../../src/partials/footer.php'; ?>
<script>
function openVideo(path, title) {
  const v = document.getElementById('modalVideo');
  v.src = path;
  v.play();
  document.getElementById('videoModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeVideo(e) {
  if (e.target.id === 'videoModal') closeVideoBtn();
}
function closeVideoBtn() {
  const v = document.getElementById('modalVideo');
  v.pause(); v.src = '';
  document.getElementById('videoModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVideoBtn(); });
</script>
</body>
</html>
