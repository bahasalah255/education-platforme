<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/csrf.php';
require_login();
verify_csrf();

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
if ($module_id <= 0) { echo 'Module not specified'; exit; }

// gather unit ids for module
$stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order`'); $stmt->execute([$module_id]);
$unit_ids = array_column($stmt->fetchAll(), 'id');
if (empty($unit_ids)) { echo 'No units for this module'; exit; }

// define posttest questions
$questions = [
  ['id'=>'q1','type'=>'mcq','prompt'=>'Choisissez l’article défini correct pour compléter cette phrase : « …..école de mon quartier est très ancienne. »','choices'=>['La','Les','L’','Le'],'correct'=>'L’'],
  ['id'=>'q2','type'=>'mcq','prompt'=>'Lequel de ces articles complète correctement la phrase : « j’aime beaucoup…..haricots verts. »','choices'=>['L’','Les','Des','Le'],'correct'=>'Les'],
  ['id'=>'q3','type'=>'mcq','prompt'=>'Dans quel cas utilise-t-on l’article défini ?','choices'=>['Pour parler d’une chose non identifiée.','Pour désigner un élément unique ou déjà connu.','Devant un verbe à l’infinitif.','Uniquement pour les noms propres.'],'correct'=>'Pour désigner un élément unique ou déjà connu.'],
  ['id'=>'q4','type'=>'mcq','prompt'=>'Compléter : «……hiver est ma saison préférée.»','choices'=>['Le','La','L’','Les'],'correct'=>'L’'],
  ['id'=>'q5','type'=>'mcq','prompt'=>'Quelle est la forme plurielle de « le » et « la » ?','choices'=>['Leurs','Ils','Des','Les'],'correct'=>'Les'],
  ['id'=>'q6','type'=>'mcq','prompt'=>'Choisissez la phrase correcte :','choices'=>['Le ordinateur est cassé.','L’ordinateur est cassé.','Les ordinateur est cassé.','La ordinateur est cassé'],'correct'=>'L’ordinateur est cassé.'],
  ['id'=>'q7','type'=>'mcq','prompt'=>'Quel article défini convient pour «……cahiers de l’élève » ?','choices'=>['Les','La','Le','L’'],'correct'=>'Les'],
  ['id'=>'q8','type'=>'mcq','prompt'=>'Compléter la phrase : « j’ai perdu…….adresse de mon mari. »','choices'=>["L’","La","Le","Une"],'correct'=>"L’"],
  // Q9 blanks
  ['id'=>'q9','type'=>'blanks','prompt'=>'Compléter les espaces vides par l’article défini qui convient.','','blanks'=>[
    'Chaque matin, ____ soleil entre par ____ fenêtre de la chambre.',
    'Jawad ouvre ____ rideaux et regarde ____ oiseaux dans ____ jardin.',
    'Elle adore ____ air frais du matin.'
  ],'answers'=>['le','la','les','les','le','l\'']],
  // Q10 paragraph correction — expect key corrected tokens
  ['id'=>'q10','type'=>'paragraph','prompt'=>'Identifier et corriger les erreurs d’articles dans le paragraphe suivant :','text'=>"Hier, le amie de Mohamed est allée à le épicerie pou  acheter le fruits. Il a pris la oignon, le salade et les ail. sur le chemin du retour, le humidité de la air a mouillé ses vêtements. Il a dû ouvrir le parapluie pour protéger la ordinateur qu’il portait dans son sac."]
];

$result = null; $message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $score = 0; $max = 0;
  // grade MCQs
  foreach ($questions as $q) {
    if ($q['type'] === 'mcq') { $max += 1; $ans = $_POST[$q['id']] ?? null; if ($ans !== null && trim($ans) === $q['correct']) $score += 1; }
    if ($q['type'] === 'blanks') { $max += count($q['answers']); // each blank is one point
      // collect blanks answers from POST as q9_1..q9_n
      $blCorrect = 0; foreach ($q['answers'] as $i=>$exp){ $idx = 'q9_'.($i+1); $given = trim((string)($_POST[$idx] ?? '')); if ($given!=='') { $g = strtolower($given); $e = strtolower($exp); $g = str_replace([" ","\""],['',''],$g); $e = str_replace([" ","\""],['',''],$e); if ($g === $e) $blCorrect++; } }
      $score += $blCorrect;
    }
    if ($q['type'] === 'paragraph') { $max += 10; // up to 10 corrections
      $given = trim((string)($_POST['q10_text'] ?? ''));
      // expected tokens to check
      $expected_tokens = ["l'amie","l\'epicerie","pour","les fruits","l\'oignon","la salade","l\'ail","l\'humidite","l\'air","l\'ordinateur"];
      $found = 0; $low = strtolower($given);
      foreach ($expected_tokens as $tok) { $tok_normal = strtolower(str_replace([" ","\"","\n"], ['','',''], $tok)); $low_normal = strtolower(str_replace([" ","\"","\n"], ['','',''], $low)); if (strpos($low_normal, $tok_normal) !== false) $found++; }
      $score += $found;
    }
  }

  // save an exercise record for posttest and an attempt
  try {
    $firstUnit = $unit_ids[0] ?? null;
    $exercise_id = null;
    if ($firstUnit) {
      $insEx = $pdo->prepare('INSERT INTO exercises (unit_id,type,data,points) VALUES (?,?,?,?)');
      $insEx->execute([$firstUnit,'posttest', json_encode(['questions'=>$questions]), $max]);
      $exercise_id = $pdo->lastInsertId();
    }
    if ($exercise_id) {
      $insAt = $pdo->prepare('INSERT INTO attempts (user_id,exercise_id,score,max_score,data) VALUES (?,?,?,?,?)');
      $insAt->execute([$_SESSION['user_id'],$exercise_id,$score,$max,json_encode($_POST)]);
    }
  } catch (Exception $e) { error_log('Posttest save error: '.$e->getMessage()); }

  $percent = $max>0 ? round(100*$score/$max) : 0;
  if ($percent >= 80) {
    // mark module units complete and award points
    foreach ($unit_ids as $uid) {
      $up = $pdo->prepare('SELECT id FROM progress WHERE user_id=? AND unit_id=?'); $up->execute([$_SESSION['user_id'],$uid]); $exst = $up->fetch();
      if ($exst) { $pdo->prepare('UPDATE progress SET score=?, max_score=?, needs_remediation=0 WHERE id=?')->execute([$max,$max,$exst['id']]); }
      else { $pdo->prepare('INSERT INTO progress (user_id,unit_id,score,max_score,needs_remediation) VALUES (?,?,?,?,0)')->execute([$_SESSION['user_id'],$uid,$max,$max]); }
    }
    $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?')->execute([($score*5), $_SESSION['user_id']]);
    $message = "Posttest réussi ({$percent}%). Module validé."; $result = 'pass';
    // proceed to next module if exists
    $next = $pdo->prepare('SELECT id FROM modules WHERE `order` > (SELECT `order` FROM modules WHERE id = ?) ORDER BY `order` LIMIT 1'); $next->execute([$module_id]); $nextId = $next->fetchColumn();
    if ($nextId) { header('Location: /module.php?id='.$nextId); exit; }
  } elseif ($percent >= 50) {
    // partial: send to first non-mastered unit
    $first = null;
    $stmt2 = $pdo->prepare('SELECT id FROM units WHERE module_id = ? ORDER BY `order`'); $stmt2->execute([$module_id]); $units = $stmt2->fetchAll();
    foreach ($units as $u) { $st = $pdo->prepare('SELECT score,max_score FROM progress WHERE user_id=? AND unit_id=?'); $st->execute([$_SESSION['user_id'],$u['id']]); $p = $st->fetch(); if (!$p || ($p['max_score']>0 && $p['score'] < $p['max_score'])) { $first = $u['id']; break; } }
    $message = "Posttest partiel ({$percent}%). Exercices ciblés recommandés."; $result = 'partial';
    if ($first) { header('Location: /lesson.php?unit_id='.$first); exit; }
  } else {
    // fail: generate remediation for first unit
    $first = $unit_ids[0];
    try {
      require_once __DIR__.'/../src/remediation.php';
      generate_remediation($pdo,$first);
    } catch (Throwable $e) {
      error_log('Posttest remediation error: '.$e->getMessage());
    }
    $message = "Échec ({$percent}%). Remédiation requise."; $result = 'fail';
    header('Location: /lesson.php?unit_id='.$first.'&remediation=1'); exit;
  }

}

?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post-test · Module <?=htmlspecialchars($module_id)?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/ui.js" defer></script>
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>
<main class="app-container">

  <!-- REDESIGN: Posttest hero -->
  <div class="rd-lesson-hero rd-anim" style="background:linear-gradient(135deg,#F59E0B,#ef4444)">
    <div class="rd-unit-tag">🏁 Évaluation finale</div>
    <h1>Post-test — Vérification des acquis</h1>
    <p>Complétez ce test pour valider votre progression dans le module.</p>
  </div>

  <?php if ($message): ?>
  <div class="rd-quiz-wrap" style="margin-top:24px">
    <div class="rd-result-card rd-anim">
      <?php $pct_disp = isset($percent) ? $percent : 0; $stroke = round(3.14159*2*54*$pct_disp/100,1); ?>
      <div class="rd-score-wrap">
        <svg width="140" height="140" viewBox="0 0 140 140">
          <circle cx="70" cy="70" r="54" fill="none" stroke="var(--rd-border)" stroke-width="10"/>
          <circle cx="70" cy="70" r="54" fill="none"
            stroke="<?= $result==='pass' ? 'var(--rd-success)' : ($result==='partial' ? 'var(--rd-warning)' : 'var(--rd-danger)') ?>"
            stroke-width="10" stroke-linecap="round"
            stroke-dasharray="<?= $stroke ?> 339.3"
            style="transition:stroke-dasharray 1s ease"/>
        </svg>
        <div class="rd-score-inner">
          <div class="rd-score-pct"><?= $pct_disp ?>%</div>
          <div class="rd-score-sub">Score</div>
        </div>
      </div>
      <h2 style="margin-bottom:8px">
        <?= $result==='pass' ? '🏆 Module validé !' : ($result==='partial' ? '⚡ En progression' : '💪 À retravailler') ?>
      </h2>
      <p class="muted" style="margin-bottom:24px"><?=htmlspecialchars($message)?></p>
      <div style="display:flex;gap:12px;justify-content:center">
        <a class="btn secondary" href="/">Retour à l'accueil</a>
        <?php if ($result !== 'pass'): ?>
          <a class="btn" href="/lesson.php?unit_id=<?= $unit_ids[0] ?? 1 ?>">Revoir les leçons</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php else: ?>

  <!-- REDESIGN: Posttest questions -->
  <div class="rd-quiz-wrap" style="margin-top:24px">
    <form method="post" id="posttestForm">
      <?= csrf_field() ?>
      <?php $qNum = 0; foreach ($questions as $q): $qNum++; ?>
      <div class="rd-quiz-card rd-anim" style="margin-bottom:20px">
        <div class="rd-ex-badge">
          <?= $q['type']==='mcq' ? '📝' : ($q['type']==='blanks' ? '✏️' : '🔍') ?>
          Question <?= $qNum ?> / <?= count($questions) ?>
        </div>
        <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;line-height:1.5"><?=htmlspecialchars($q['prompt'])?></h3>

        <?php if ($q['type']==='mcq'): ?>
          <div class="choices">
            <?php foreach ($q['choices'] as $c): ?>
              <label>
                <input type="radio" name="<?=htmlspecialchars($q['id'])?>" value="<?=htmlspecialchars($c)?>">
                <?=htmlspecialchars($c)?>
              </label>
            <?php endforeach; ?>
          </div>

        <?php elseif ($q['type']==='blanks'): ?>
          <?php foreach ($q['blanks'] as $i => $blank): ?>
            <div style="margin-bottom:14px">
              <p style="font-style:italic;color:var(--rd-muted);margin-bottom:8px"><?=htmlspecialchars($blank)?></p>
              <input type="text" name="q9_<?=($i+1)?>" placeholder="Entrez l'article (le, la, les, l')…" style="max-width:240px">
            </div>
          <?php endforeach; ?>

        <?php elseif ($q['type']==='paragraph'): ?>
          <div style="background:var(--rd-warning-light);border:1px solid rgba(245,158,11,0.25);border-radius:10px;padding:16px;margin-bottom:16px;font-size:14px;line-height:1.7;color:var(--rd-text)">
            <?=htmlspecialchars($q['text'])?>
          </div>
          <label style="margin-bottom:8px">Recopiez le paragraphe en corrigeant les articles :</label>
          <textarea name="q10_text" rows="6" placeholder="Tapez ici le paragraphe corrigé…" style="resize:vertical"></textarea>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div style="display:flex;justify-content:flex-end;margin-top:8px">
        <button class="btn" type="submit" style="padding:13px 28px!important;font-size:15px!important">
          🏁 Soumettre le post-test
        </button>
      </div>
    </form>
  </div>

  <?php endif; ?>
</main>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
<script src="/assets/js/exercises.js" defer></script>
</body></html>
