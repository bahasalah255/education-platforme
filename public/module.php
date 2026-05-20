<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT m.*, COALESCE(u.display_name,u.username) AS creator_name FROM modules m LEFT JOIN users u ON m.created_by = u.id WHERE m.id = ?'); $stmt->execute([$id]); $module = $stmt->fetch();
if (!$module) { echo 'Module not found'; exit; }

$units = $pdo->prepare('SELECT * FROM units WHERE module_id = ? ORDER BY `order`'); $units->execute([$id]); $units = $units->fetchAll();
$media = $pdo->prepare('SELECT * FROM media WHERE module_id = ? ORDER BY uploaded_at DESC'); $media->execute([$id]); $media = $media->fetchAll();

// user-specific progress and enrollment
$user = null; $percent = 0; $completedUnits = 0; $nextUnit = null; $enrolledCount = 0;
if (!empty($_SESSION['user_id'])) {
  $user = current_user();
  // count enrolled
  $ec = $pdo->prepare('SELECT COUNT(*) FROM user_courses WHERE module_id = ?'); $ec->execute([$id]); $enrolledCount = (int)$ec->fetchColumn();
  // compute completed units
  $unitCount = count($units);
  foreach ($units as $u) {
    $st = $pdo->prepare('SELECT score,max_score FROM progress WHERE user_id=? AND unit_id=?'); $st->execute([$user['id'],$u['id']]); $pr = $st->fetch();
    if ($pr && $pr['max_score']>0 && $pr['score'] >= ($pr['max_score']*0.8)) { $completedUnits++; }
    if (!$nextUnit && (!$pr || ($pr['max_score']>0 && $pr['score'] < $pr['max_score']))) { $nextUnit = $u['id']; }
  }
  if ($unitCount>0) $percent = round(100 * $completedUnits / $unitCount);
}

$units = $pdo->prepare('SELECT * FROM units WHERE module_id = ? ORDER BY `order`'); $units->execute([$id]); $units = $units->fetchAll();
$media = $pdo->prepare('SELECT * FROM media WHERE module_id = ? ORDER BY uploaded_at DESC'); $media->execute([$id]); $media = $media->fetchAll();

?>
<!doctype html>
<html><head><meta charset="utf-8"><title><?=htmlspecialchars($module['title'])?></title><link rel="stylesheet" href="/assets/css/style.css"><script src="/assets/js/ui.js" defer></script></head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">
  <div class="hero" style="background:transparent;padding:12px 0">
    <div style="display:flex;gap:20px;align-items:flex-start">
      <div style="flex:1">
        <h1><?=htmlspecialchars($module['title'])?></h1>
        <?php if (!empty($module['cover_image'])): ?><div style="margin-top:12px"><img src="<?=htmlspecialchars($module['cover_image'])?>" alt="cover" style="width:100%;max-height:520px;object-fit:cover;border-radius:10px"></div><?php endif; ?>
        <div style="margin-top:12px;color:var(--muted)">Par : <?=htmlspecialchars($module['creator_name'] ?? '—')?></div>
        <div style="margin-top:16px">
          <div class="card">
              <h3>À propos du cours</h3>
              <?php if (!empty($module['about'])): ?>
                <div class="muted"><?= $module['about'] ?></div>
              <?php else: ?>
                <div class="muted">
                  <ol>
                    <li><strong>Système d'Entrée :</strong> Phase de Cadrage Détaillée</li>
                    <li><strong>Présentation des Objectifs</strong> (La Boussole)</li>
                    <li><strong>Objectif Général (OG) :</strong> À la fin de ce module, l'élève sera capable de mobiliser l'adjectif qualificatif de manière autonome pour enrichir une description, en assurant sa correction syntaxique (place et fonction) et sa validité orthographique (accords complexes) dans un texte de trois à cinq phrases.</li>
                    <li><strong>Objectifs Spécifiques (OS) :</strong>
                      <ul>
                        <li>OS 1 (Repérage) : Distinguer l'adjectif qualificatif au sein d'un groupe nominal et identifier avec précision le nom noyau qu'il qualifie.</li>
                        <li>OS 2 (Analyse Fonctionnelle) : Différencier l'adjectif épithète (lié au nom) de l'adjectif attribut (lié au sujet via un verbe d'état).</li>
                        <li>OS 3 (Morphologie et Accords) : Appliquer les règles de flexion en genre (féminin) et en nombre (pluriel), y compris pour les adjectifs à terminaisons particulières (-al, -eau, -s, -x).</li>
                      </ul>
                    </li>
                  </ol>
                  <p><a href="#" class="muted">Afficher moins</a></p>
                </div>
              <?php endif; ?>
            </div>
        </div>
        <?php if ($media): ?>
          <div style="margin-top:12px" class="card">
            <h3>Ressources</h3>
            <ul>
            <?php foreach($media as $m): ?>
              <li><a href="<?=htmlspecialchars($m['path'])?>" target="_blank" rel="noopener"><?=htmlspecialchars($m['filename'])?></a> <small class="muted"><?=htmlspecialchars($m['mime'])?></small></li>
            <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div style="margin-top:12px" class="card">
          <h3>Contenu du cours</h3>
          <?php if (empty($units)): ?><p class="muted">Aucune unité pour le moment.</p><?php else: ?>
            <ul>
            <?php foreach($units as $u):
              $s = $pdo->prepare('SELECT COUNT(*) FROM exercises WHERE unit_id = ?'); $s->execute([$u['id']]); $cnt = (int)$s->fetchColumn();
            ?>
              <li><a class="muted" href="/lesson.php?unit_id=<?= $u['id'] ?>"><?=htmlspecialchars($u['title'])?></a> <small class="muted">(<?= $cnt ?> exercice(s))</small></li>
            <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <aside style="width:320px">
        <div class="card">
          <h3>Progression du cours</h3>
          <div style="margin-top:12px">
            <div class="progress-bar"><i style="width:<?= $percent ?>%"></i></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px"><small class="muted"><?= $completedUnits ?>/<?= count($units) ?> unités</small><small class="muted"><?= $percent ?>% Terminer</small></div>
          </div>
          <div style="margin-top:12px;text-align:center">
            <?php if (!empty($user)): ?>
              <a class="btn" href="/lesson.php?unit_id=<?= $nextUnit ?? ($units[0]['id'] ?? 0) ?>">Continuer d'apprendre</a>
              <div style="margin-top:8px"><a class="btn secondary" href="#">Cours terminé</a></div>
            <?php else: ?>
              <a class="btn" href="/register.php">S'inscrire</a>
            <?php endif; ?>
          </div>
          <div style="margin-top:12px;border-top:1px solid rgba(0,0,0,0.04);padding-top:12px">
            <div class="muted">Vous êtes inscrit :
              <?php if (!empty($user)){
                $uc = $pdo->prepare('SELECT enrolled_at FROM user_courses WHERE user_id=? AND module_id=?'); $uc->execute([$user['id'],$id]); $en = $uc->fetchColumn();
                echo $en ? htmlspecialchars($en) : '<span class="muted">Non</span>';
              } else { echo '<a href="/register.php">Sinscrire</a>'; }?>
            </div>
            <div style="margin-top:8px"><small class="muted"><?= $enrolledCount ?> Total des inscrits</small></div>
          </div>
        </div>
      </aside>
    </div>
  </div>

</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
