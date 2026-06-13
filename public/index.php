<?php
require_once __DIR__.'/../src/auth.php';
$stmt = $pdo->query('SELECT m.*, COALESCE(u.display_name, u.username) AS creator_name FROM modules m LEFT JOIN users u ON m.created_by = u.id ORDER BY m.`order`');
$modules = $stmt->fetchAll();
$first = $modules[0] ?? null;

/* REDESIGN: module icons by index */
$icons = ['📖','✏️','🎯','🌟','📝','🏆','💡','🔤'];
$colors = [
  'linear-gradient(135deg,#2563EB,#06b6d4)',
  'linear-gradient(135deg,#10B981,#059669)',
  'linear-gradient(135deg,#F59E0B,#d97706)',
  'linear-gradient(135deg,#7c3aed,#a855f7)',
];
?><!doctype html>
<html lang="fr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ma maison est une école — Classe Inversée</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>

<!-- REDESIGN: Hero section -->
<section class="hero">
  <div class="app-container">
    <div class="rd-hero-grid">
      <div class="rd-anim">
        <div class="rd-hero-badge">🏫 Classe Inversée · Primaire (4e, 5e, 6e AEP)</div>
        <h1 class="rd-hero-title">
          Ma maison est<br>
          <span class="rd-hl">une école</span>
        </h1>
        <p class="rd-hero-sub">
          Une approche moderne d'apprentissage hybride : apprenez la leçon à la maison à votre rythme, puis pratiquez en classe avec votre professeur.
        </p>
        <div class="rd-hero-actions">
          <a class="btn" href="/entrance.php?module_id=<?= $first ? $first['id'] : 1 ?>" style="padding:13px 24px!important;font-size:15px!important">
            🚀 Démarrer le test d'entrée
          </a>
          <a class="btn secondary" href="/lesson.php?unit_id=1" style="padding:13px 24px!important;font-size:15px!important">
            📚 Parcourir les leçons
          </a>
        </div>
        <p class="muted" style="margin-top:16px;font-size:13px">
          Pour les enseignants & parents : <a href="/login.php">connectez-vous</a> pour suivre la progression et gérer les cours.
        </p>
      </div>

      <div class="rd-hero-visual rd-anim rd-d1">
        <div style="text-align:center">
          <div class="rd-float-card">
            <div style="font-size:1.15rem;font-weight:900;text-align:center;margin-bottom:4px;color:var(--rd-primary);">Ma maison est une école</div>
            <div style="font-size:11px;color:var(--rd-muted);text-align:center;margin-bottom:18px;font-weight:600;">Classe Inversée · Primaire</div>
            <div class="rd-article-grid">
              <div class="rd-article-tile t-le">Le</div>
              <div class="rd-article-tile t-la">La</div>
              <div class="rd-article-tile t-les">Les</div>
              <div class="rd-article-tile t-l">L'</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- REDESIGN: Features section -->
<section class="rd-section rd-section-white">
  <div class="rd-section-inner">
    <div class="rd-label">Pourquoi ce cours ?</div>
    <h2 class="rd-heading">Une approche progressive et engageante</h2>
    <p class="rd-desc">Des activités courtes et concrètes pour maîtriser les articles définis, avec des remédiations automatiques si nécessaire.</p>
    <div class="rd-feature-grid">
      <div class="rd-feat rd-anim">
        <div class="rd-feat-icon rd-feat-icon-blue">🗺️</div>
        <h3 class="rd-feat-title">Parcours guidés</h3>
        <p>4 unités progressives avec objectifs clairs : du repérage de base jusqu'à la correction autonome des erreurs.</p>
      </div>
      <div class="rd-feat rd-anim rd-d1">
        <div class="rd-feat-icon rd-feat-icon-green">🎮</div>
        <h3 class="rd-feat-title">Exercices interactifs</h3>
        <p>QCM animés, glisser-déposer, complétion de texte — apprendre en jouant pour une meilleure mémorisation.</p>
      </div>
      <div class="rd-feat rd-anim rd-d2">
        <div class="rd-feat-icon rd-feat-icon-amber">🎯</div>
        <h3 class="rd-feat-title">Remédiation ciblée</h3>
        <p>Exercices supplémentaires générés automatiquement pour combler précisément les lacunes détectées.</p>
      </div>
    </div>
  </div>
</section>

<!-- NEW SECTION: Project Overview from Image -->
<section class="rd-section" style="background: linear-gradient(180deg, var(--rd-surface) 0%, var(--rd-bg) 100%);">
  <div class="rd-section-inner">
    <div class="rd-label">Notre Vision</div>
    <h2 class="rd-heading">Concept de "Ma maison est une école"</h2>
    <p class="rd-desc">Découvrez les piliers de notre projet de classe inversée destiné à moderniser l'enseignement primaire marocain.</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 32px;">
      <!-- Pedagogical Concept Card -->
      <div class="card rd-anim" style="padding: 24px!important;">
        <div style="font-size: 2rem; margin-bottom: 12px;">🧠</div>
        <h3 style="margin: 0 0 10px 0;">Concept Pédagogique</h3>
        <p class="muted" style="font-size: 14px; line-height: 1.6;">
          <strong>Principe :</strong> L'élève apprend sa leçon à la maison (via des explications simples, vidéos, PDF) et vient en classe pour pratiquer avec son enseignant.<br>
          <strong>Objectif :</strong> Développer l'autonomie et améliorer la compréhension sans intervention professorale à la maison.
        </p>
      </div>

      <!-- Target/Domain Card -->
      <div class="card rd-anim rd-d1" style="padding: 24px!important;">
        <div style="font-size: 2rem; margin-bottom: 12px;">🎯</div>
        <h3 style="margin: 0 0 10px 0;">Niveau Cible & Thématique</h3>
        <p class="muted" style="font-size: 14px; line-height: 1.6;">
          <strong>Domaine :</strong> Enseignement primaire (élèves, enseignants et établissements).<br>
          <strong>Niveau :</strong> Conçu spécifiquement pour les classes de <strong>4ème, 5ème et 6ème AEP</strong>.<br>
          <strong>Thématique :</strong> Cours progressifs et exercices interactifs en ligne.
        </p>
      </div>

      <!-- Market & Commercialization Card -->
      <div class="card rd-anim rd-d2" style="padding: 24px!important;">
        <div style="font-size: 2rem; margin-bottom: 12px;">💼</div>
        <h3 style="margin: 0 0 10px 0;">Marché & Accès</h3>
        <p class="muted" style="font-size: 14px; line-height: 1.6;">
          <strong>Besoin :</strong> Moderniser l'enseignement et résoudre le manque d'interactivité en classe par l'apprentissage hybride.<br>
          <strong>Offres :</strong> Une formule gratuite avec accès limité aux cours, et une formule Premium avec suivi des progrès complet pour écoles publiques & privées.
        </p>
      </div>
    </div>
    
    <div style="text-align: center; margin-top: 32px;">
      <a class="btn secondary" href="/about.php">
        ℹ️ En savoir plus sur le projet complet
      </a>
    </div>
  </div>
</section>

<!-- REDESIGN: Modules grid -->
<section class="rd-section">
  <div class="rd-section-inner">
    <div class="rd-label">Contenu du cours</div>
    <h2 class="rd-heading">Aperçu des modules</h2>
    <p class="rd-desc">Chaque module couvre un objectif d'apprentissage spécifique avec leçon, exercices et évaluation.</p>

    <?php if (empty($modules)): ?>
      <div class="card" style="margin-top:32px;text-align:center;padding:48px!important">
        <div style="font-size:3rem;margin-bottom:12px">📭</div>
        <h3>Aucun module disponible</h3>
        <p class="muted">Les enseignants peuvent créer des modules depuis leur tableau de bord.</p>
        <a class="btn secondary" href="/login.php" style="margin-top:12px">Se connecter</a>
      </div>
    <?php else: ?>
      <div class="grid" style="margin-top:32px">
        <?php foreach ($modules as $i => $m): ?>
        <div class="lesson-card card rd-anim" style="animation-delay:<?= $i * 60 ?>ms">
          <?php if (!empty($m['cover_image'])): ?>
            <img src="<?= htmlspecialchars($m['cover_image']) ?>" alt="cover" class="module-cover">
          <?php else: ?>
            <div class="rd-mod-top" style="background:<?= $colors[$i % count($colors)] ?>">
              <span><?= $icons[$i % count($icons)] ?></span>
            </div>
          <?php endif; ?>
          <div class="rd-mod-body">
            <div class="rd-level-badge">Unité <?= $i + 1 ?></div>
            <h3><?= htmlspecialchars($m['title']) ?></h3>
            <p class="muted" style="font-size:13px;flex:1;margin-bottom:0"><?= htmlspecialchars($m['description']) ?></p>
            <div class="rd-mod-actions">
              <a class="btn" href="/module.php?id=<?= $m['id'] ?>">Ouvrir</a>
              <a class="btn secondary" href="/pretest.php?module_id=<?= $m['id'] ?>">Évaluer</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- REDESIGN: Testimonials + CTA -->
<section class="rd-section rd-section-white">
  <div class="rd-section-inner">
    <div class="rd-label">Ils témoignent</div>
    <h2 class="rd-heading">Ce qu'en disent les enseignants</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:32px">
      <div class="card rd-anim" style="background:var(--rd-primary-light)!important;border-color:rgba(37,99,235,0.15)!important">
        <div style="font-size:24px;margin-bottom:10px">💬</div>
        <p style="font-style:italic;color:var(--rd-text);margin-bottom:10px">"Les élèves progressent vite grâce aux remédiations automatiques."</p>
        <strong style="font-size:13px">— Mme Durand, CE2</strong>
      </div>
      <div class="card rd-anim rd-d1" style="background:var(--rd-success-light)!important;border-color:rgba(16,185,129,0.15)!important">
        <div style="font-size:24px;margin-bottom:10px">💬</div>
        <p style="font-style:italic;color:var(--rd-text);margin-bottom:10px">"Interface claire, activités ludiques — mes élèves adorent !"</p>
        <strong style="font-size:13px">— M. Lefebvre, CM1</strong>
      </div>
      <div class="card rd-anim rd-d2" style="background:var(--rd-warning-light)!important;border-color:rgba(245,158,11,0.15)!important">
        <div style="font-size:24px;margin-bottom:10px">💬</div>
        <p style="font-style:italic;color:var(--rd-text);margin-bottom:10px">"Le suivi individuel m'économise un temps précieux."</p>
        <strong style="font-size:13px">— Mme Khalil, 4ème primaire</strong>
      </div>
    </div>

    <div class="card rd-anim" style="margin-top:40px;text-align:center;padding:48px!important;background:linear-gradient(135deg,#EFF6FF,#F0FDF4)!important">
      <div style="font-size:3rem;margin-bottom:12px">🎓</div>
      <h3 style="font-size:1.4rem;font-weight:800;margin-bottom:8px">Prêt à commencer l'aventure ?</h3>
      <p class="muted" style="max-width:420px;margin:0 auto 24px">Testez vos prérequis ou inscrivez-vous pour accéder au parcours complet.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a class="btn" href="/register.php" style="padding:13px 28px!important;font-size:15px!important">✨ S'inscrire gratuitement</a>
        <a class="btn secondary" href="/entrance.php?module_id=<?= $first ? $first['id'] : 1 ?>" style="padding:13px 28px!important;font-size:15px!important">Passer le test d'entrée</a>
      </div>
    </div>
  </div>
</section>

<script src="/assets/js/ui.js" defer></script>
<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body></html>
