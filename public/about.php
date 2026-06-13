<?php
require_once __DIR__.'/../src/auth.php';
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>À Propos — Ma maison est une école</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    /* Additional custom styling for the presentation page */
    .about-hero {
      background: linear-gradient(135deg, var(--rd-primary) 0%, #1d4ed8 100%);
      color: white;
      padding: 60px 24px;
      text-align: center;
      border-radius: var(--rd-radius-lg);
      margin-bottom: 40px;
      box-shadow: var(--rd-shadow-lg);
    }
    .about-hero h1 {
      color: white !important;
      margin-bottom: 12px;
      font-size: 2.5rem;
    }
    .about-hero p {
      font-size: 1.2rem;
      opacity: 0.9;
      max-width: 700px;
      margin: 0 auto;
    }
    .about-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }
    .about-card {
      transition: transform var(--rd-normal), box-shadow var(--rd-normal);
      border: 1px solid var(--rd-border);
    }
    .about-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--rd-shadow-lg) !important;
    }
    .section-title {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.3rem;
      border-bottom: 2px solid var(--rd-primary-light);
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    .section-title span {
      font-size: 1.8rem;
    }
    .info-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .info-list li {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    body.dark .info-list li {
      border-bottom-color: rgba(255,255,255,0.05);
    }
    .info-list li:last-child {
      border-bottom: none;
    }
    .info-label {
      font-weight: 700;
      color: var(--rd-text);
    }
    .info-value {
      color: var(--rd-muted);
      text-align: right;
      max-width: 60%;
    }
    .market-box {
      background: var(--rd-primary-light);
      border-left: 4px solid var(--rd-primary);
      padding: 16px;
      border-radius: var(--rd-radius-sm);
      margin-bottom: 16px;
    }
    body.dark .market-box {
      background: rgba(37,99,235,0.15);
    }
    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      margin-top: 20px;
    }
    .pricing-card {
      text-align: center;
      border: 2px solid var(--rd-border);
      position: relative;
      overflow: hidden;
    }
    .pricing-card.premium {
      border-color: var(--rd-primary);
    }
    .pricing-badge {
      position: absolute;
      top: 15px;
      right: -30px;
      background: var(--rd-warning);
      color: white;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 30px;
      transform: rotate(45deg);
    }
    .price {
      font-size: 2.2rem;
      font-weight: 900;
      margin: 20px 0;
      color: var(--rd-text);
    }
    .price span {
      font-size: 1rem;
      font-weight: 500;
      color: var(--rd-muted);
    }
  </style>
  <script src="/assets/js/ui.js" defer></script>
</head>
<body>
<?php require_once __DIR__.'/../src/partials/header.php'; ?>

<main class="app-container">
  
  <!-- Presentation Hero -->
  <div class="about-hero rd-anim">
    <h1>Ma maison est une école</h1>
    <p>Une plateforme de classe inversée moderne pour l'enseignement primaire (4ème, 5ème et 6ème AEP)</p>
  </div>

  <div class="about-grid">
    
    <!-- 1. Identification du Projet -->
    <div class="card about-card rd-anim rd-d1">
      <h2 class="section-title">
        <span>📋</span> 1. Identification du Projet
      </h2>
      <ul class="info-list">
        <li>
          <span class="info-label">Nom de la plateforme</span>
          <span class="info-value">Ma maison est une école</span>
        </li>
        <li>
          <span class="info-label">Type de projet</span>
          <span class="info-value">Plateforme de classe inversée</span>
        </li>
        <li>
          <span class="info-label">Domaine</span>
          <span class="info-value">Enseignement primaire</span>
        </li>
        <li>
          <span class="info-label">Niveau cible</span>
          <span class="info-value">4ème / 5ème et 6ème AEP</span>
        </li>
        <li>
          <span class="info-label">Thématique</span>
          <span class="info-value">Cours et exercices en ligne</span>
        </li>
      </ul>
    </div>

    <!-- 2. Concept Pédagogique -->
    <div class="card about-card rd-anim rd-d2">
      <h2 class="section-title">
        <span>🧠</span> 2. Concept Pédagogique
      </h2>
      <ul class="info-list">
        <li>
          <span class="info-label">Approche</span>
          <span class="info-value">Classe inversée</span>
        </li>
        <li>
          <span class="info-label">Principe</span>
          <span class="info-value">L'élève apprend la leçon à la maison, puis pratique en classe.</span>
        </li>
        <li>
          <span class="info-label">Objectif principal</span>
          <span class="info-value">Développer l'autonomie et améliorer la compréhension.</span>
        </li>
        <li>
          <span class="info-label">Compétences visées</span>
          <span class="info-value">Étudier à la maison sans intervention du professeur.</span>
        </li>
      </ul>
    </div>

  </div>

  <div class="about-grid">

    <!-- 3. Contenu Pédagogique -->
    <div class="card about-card rd-anim rd-d1">
      <h2 class="section-title">
        <span>📚</span> 3. Contenu Pédagogique
      </h2>
      <ul class="info-list">
        <li>
          <span class="info-label">Cours Vidéo/PDF/Word...</span>
          <span class="info-value">Explication simple et illustrée</span>
        </li>
        <li>
          <span class="info-label">Exercices interactifs</span>
          <span class="info-value">QCM + jeux éducatifs</span>
        </li>
        <li>
          <span class="info-label">Activités</span>
          <span class="info-value">Étudier en ligne</span>
        </li>
        <li>
          <span class="info-label">Évaluation</span>
          <span class="info-value">Pré-test / Post-test</span>
        </li>
        <li>
          <span class="info-label">Feedback</span>
          <span class="info-value">Correction automatique</span>
        </li>
      </ul>
    </div>

    <!-- 4. Fonctionnalités Techniques -->
    <div class="card about-card rd-anim rd-d2">
      <h2 class="section-title">
        <span>🛠️</span> 4. Fonctionnalités Techniques
      </h2>
      <ul class="info-list">
        <li>
          <span class="info-label">Comptes utilisateurs</span>
          <span class="info-value">Élèves / enseignants / parents</span>
        </li>
        <li>
          <span class="info-label">Tableau de bord</span>
          <span class="info-value">Suivi précis des progrès</span>
        </li>
        <li>
          <span class="info-label">Accessibilité</span>
          <span class="info-value">Mobile + ordinateur</span>
        </li>
        <li>
          <span class="info-label">Interface</span>
          <span class="info-value">Simple, intuitive et épurée</span>
        </li>
        <li>
          <span class="info-label">Plateforme</span>
          <span class="info-value">LMS Inspiré de Moodle</span>
        </li>
      </ul>
    </div>

  </div>

  <!-- 5. Analyse du Marché -->
  <div class="card rd-anim rd-d3" style="margin-bottom: 40px;">
    <h2 class="section-title">
      <span>📈</span> 5. Analyse du Marché
    </h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
      <div class="market-box">
        <strong>🎯 Public Cible</strong>
        <p class="muted" style="margin: 8px 0 0 0;">Élèves, enseignants et établissements scolaires (publics et privés).</p>
      </div>
      <div class="market-box">
        <strong>🔍 Besoin Identifié</strong>
        <p class="muted" style="margin: 8px 0 0 0;">Modernisation de l'enseignement par le biais du numérique.</p>
      </div>
      <div class="market-box" style="border-left-color: var(--rd-danger);">
        <strong>⚠️ Problème</strong>
        <p class="muted" style="margin: 8px 0 0 0;">Le manque cruel d'interactivité au sein de la classe traditionnelle.</p>
      </div>
      <div class="market-box" style="border-left-color: var(--rd-success);">
        <strong>💡 Solution Apportée</strong>
        <p class="muted" style="margin: 8px 0 0 0;">Apprentissage hybride combinant présentiel et numérique.</p>
      </div>
    </div>
  </div>

  <!-- 6. Stratégie de Commercialisation -->
  <div class="card rd-anim rd-d3" style="margin-bottom: 20px;">
    <h2 class="section-title" style="border-bottom: none; margin-bottom: 0;">
      <span>💰</span> 6. Modèle Économique & Commercialisation
    </h2>
    <p class="muted">Une offre adaptée aux écoles publiques et privées pour garantir l'accès à tous les élèves.</p>
    
    <div class="pricing-grid">
      <!-- Offre Gratuite -->
      <div class="card pricing-card">
        <h3>Offre Gratuite</h3>
        <p class="muted">Idéal pour s'initier aux cours de base</p>
        <div class="price">0 Dh<span> / mois</span></div>
        <ul class="info-list" style="margin-bottom: 20px; text-align: left;">
          <li>
            <span>📖 Accès limité aux cours</span>
            <span>✅</span>
          </li>
          <li>
            <span>✏️ Exercices interactifs basiques</span>
            <span>✅</span>
          </li>
          <li>
            <span>📈 Suivi des progrès complet</span>
            <span style="color: var(--rd-danger)">❌</span>
          </li>
        </ul>
        <a href="/register.php" class="btn secondary" style="width: 100%;">Commencer gratuitement</a>
      </div>

      <!-- Offre Premium -->
      <div class="card pricing-card premium">
        <div class="pricing-badge">Recommandé</div>
        <h3>Offre Premium</h3>
        <p class="muted">Accès illimité pour une autonomie totale</p>
        <div class="price">Sur Mesure<span> / élève</span></div>
        <ul class="info-list" style="margin-bottom: 20px; text-align: left;">
          <li>
            <span>📖 Accès complet à tous les cours</span>
            <span>✅</span>
          </li>
          <li>
            <span>✏️ Exercices & jeux interactifs</span>
            <span>✅</span>
          </li>
          <li>
            <span>📈 Suivi détaillé (élèves, profs, parents)</span>
            <span>✅</span>
          </li>
          <li>
            <span>🎯 Parcours de remédiation ciblés</span>
            <span>✅</span>
          </li>
        </ul>
        <a href="/register.php" class="btn" style="width: 100%;">Rejoindre le Premium</a>
      </div>
    </div>
  </div>

</main>

<?php require_once __DIR__.'/../src/partials/footer.php'; ?>
</body>
</html>
