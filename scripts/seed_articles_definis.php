<?php
require_once __DIR__ . '/../src/config.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function findUserId(PDO $pdo, string $username): ?int {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

function upsertModule(PDO $pdo, string $title, string $description, int $order, ?int $createdBy, ?string $coverImage): int {
    $stmt = $pdo->prepare('SELECT id FROM modules WHERE title = ? LIMIT 1');
    $stmt->execute([$title]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $update = $pdo->prepare('UPDATE modules SET description = ?, `order` = ?, created_by = ?, cover_image = ? WHERE id = ?');
        $update->execute([$description, $order, $createdBy, $coverImage, $id]);
        return (int) $id;
    }

    $insert = $pdo->prepare('INSERT INTO modules (title, description, `order`, created_by, cover_image) VALUES (?, ?, ?, ?, ?)');
    $insert->execute([$title, $description, $order, $createdBy, $coverImage]);
    return (int) $pdo->lastInsertId();
}

function upsertUnit(PDO $pdo, int $moduleId, string $title, string $contentHtml, int $order): int {
    $stmt = $pdo->prepare('SELECT id FROM units WHERE module_id = ? AND title = ? LIMIT 1');
    $stmt->execute([$moduleId, $title]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $update = $pdo->prepare('UPDATE units SET content_html = ?, `order` = ? WHERE id = ?');
        $update->execute([$contentHtml, $order, $id]);
        return (int) $id;
    }

    $insert = $pdo->prepare('INSERT INTO units (module_id, title, content_html, `order`) VALUES (?, ?, ?, ?)');
    $insert->execute([$moduleId, $title, $contentHtml, $order]);
    return (int) $pdo->lastInsertId();
}

function upsertExercise(PDO $pdo, int $unitId, string $type, int $order, array $data, int $points = 1): int {
    $stmt = $pdo->prepare('SELECT id FROM exercises WHERE unit_id = ? AND type = ? AND `order` = ? LIMIT 1');
    $stmt->execute([$unitId, $type, $order]);
    $id = $stmt->fetchColumn();
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($id) {
        $update = $pdo->prepare('UPDATE exercises SET data = ?, points = ? WHERE id = ?');
        $update->execute([$payload, $points, $id]);
        return (int) $id;
    }

    $insert = $pdo->prepare('INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES (?, ?, ?, ?, ?)');
    $insert->execute([$unitId, $type, $payload, $points, $order]);
    return (int) $pdo->lastInsertId();
}

function enrollUser(PDO $pdo, int $userId, int $moduleId): void {
    $stmt = $pdo->prepare('SELECT id FROM user_courses WHERE user_id = ? AND module_id = ? LIMIT 1');
    $stmt->execute([$userId, $moduleId]);
    if (!$stmt->fetchColumn()) {
        $ins = $pdo->prepare('INSERT INTO user_courses (user_id, module_id) VALUES (?, ?)');
        $ins->execute([$userId, $moduleId]);
    }
}

$teacherId = findUserId($pdo, 'teacher1');
$studentIds = array_filter([
    findUserId($pdo, 'student1'),
    findUserId($pdo, 'student2'),
]);

$moduleId = upsertModule(
    $pdo,
    'Articles définis',
    'Parcours complet sur les articles définis (le, la, les, l\'). Prétest, 4 unités, activités globales, posttest et remédiation.',
    1,
    $teacherId,
    '/assets/images/articles-definis-cover.svg'
);

$units = [
    [
        'title' => 'Unité 1 — Citer les articles définis',
        'order' => 1,
        'content_html' => <<<'HTML'
<section class="lesson-block">
  <h2>Objectif</h2>
  <p>Citer les articles définis <strong>le</strong>, <strong>la</strong>, <strong>les</strong> et <strong>l'</strong> sans aide avec une exactitude de 100%.</p>
</section>
<section class="lesson-block">
  <h2>Leçon</h2>
  <p>Les articles définis sont de petits mots qui accompagnent un nom déjà connu ou unique.</p>
  <ul>
    <li><strong>le</strong> devant un nom masculin singulier.</li>
    <li><strong>la</strong> devant un nom féminin singulier.</li>
    <li><strong>les</strong> devant un nom pluriel.</li>
    <li><strong>l'</strong> devant une voyelle ou un h muet.</li>
  </ul>
  <p><strong>Exemples :</strong> le lion, la vache, les oiseaux, l'école.</p>
</section>
<section class="lesson-block">
  <h2>Entraînement</h2>
  <p>Relève les articles définis dans les phrases suivantes :</p>
  <ul>
    <li>Le café est encore chaud.</li>
    <li>Nous marchons dans le jardin.</li>
    <li>Le vent souffle dans l'arbre.</li>
    <li>Les enfants jouent au ballon.</li>
  </ul>
</section>
<section class="lesson-block">
  <h2>Évaluation</h2>
  <p>À la fin de cette unité, l'apprenant clique sur les bonnes réponses pour distinguer les articles définis des autres mots.</p>
</section>
HTML,
    ],
    [
        'title' => 'Unité 2 — Justifier le choix de l’article',
        'order' => 2,
        'content_html' => <<<'HTML'
<section class="lesson-block">
  <h2>Objectif</h2>
  <p>Justifier le choix de l’article selon le genre, le nombre et l’élision.</p>
</section>
<section class="lesson-block">
  <h2>Leçon</h2>
  <p>L'article défini s'accorde en genre et en nombre avec le nom qu'il accompagne.</p>
  <ul>
    <li><strong>le</strong> devant un nom masculin singulier : le bureau.</li>
    <li><strong>la</strong> devant un nom féminin singulier : la porte.</li>
    <li><strong>les</strong> devant un nom au pluriel : les stylos.</li>
    <li><strong>l'</strong> devant une voyelle ou un h muet : l'ordinateur, l'heure.</li>
  </ul>
</section>
<section class="lesson-block">
  <h2>Entraînement</h2>
  <p>Associe l'article au nom :</p>
  <ul>
    <li>bureau → le</li>
    <li>table → la</li>
    <li>stylos → les</li>
    <li>école → l'</li>
  </ul>
</section>
<section class="lesson-block">
  <h2>Évaluation</h2>
  <p>Complète les phrases avec l'article défini correct : <em>le / la / les / l'</em>.</p>
</section>
HTML,
    ],
    [
        'title' => 'Unité 3 — Employer l’article défini correct',
        'order' => 3,
        'content_html' => <<<'HTML'
<section class="lesson-block">
  <h2>Objectif</h2>
  <p>Employer l'article défini correct devant les noms communs, avec un seuil de réussite de 80%.</p>
</section>
<section class="lesson-block">
  <h2>Leçon</h2>
  <p>L'article doit toujours s'accorder avec le nom.</p>
  <p><strong>Masculin singulier</strong> → le<br><strong>Féminin singulier</strong> → la<br><strong>Pluriel</strong> → les<br><strong>Voyelle ou h muet</strong> → l'</p>
</section>
<section class="lesson-block">
  <h2>Entraînement</h2>
  <ul>
    <li>… soleil brille.</li>
    <li>… lune est belle ce soir.</li>
    <li>… étudiants travaillent bien.</li>
    <li>… orange est un fruit.</li>
    <li>… professeurs sont stricts.</li>
  </ul>
</section>
<section class="lesson-block">
  <h2>Évaluation</h2>
  <p>Les réponses correctes doivent atteindre au moins 80% pour poursuivre le parcours normalement.</p>
</section>
HTML,
    ],
    [
        'title' => 'Unité 4 — Identifier et corriger les anomalies',
        'order' => 4,
        'content_html' => <<<'HTML'
<section class="lesson-block">
  <h2>Objectif</h2>
  <p>Identifier les anomalies et les corriger en tenant compte de la voyelle et du pluriel, avec 0 erreur.</p>
</section>
<section class="lesson-block">
  <h2>Leçon</h2>
  <ol>
    <li>Mauvais accord en genre : <strong>la chat</strong> → <strong>le chat</strong>.</li>
    <li>Mauvais accord en nombre : <strong>les chat</strong> → <strong>les chats</strong>.</li>
    <li>Oubli de l'élision : <strong>le école</strong> → <strong>l'école</strong>.</li>
    <li>Élision incorrecte : <strong>l'garçon</strong> → <strong>le garçon</strong>.</li>
    <li>Confusion avec d'autres déterminants : <strong>une chat</strong> → <strong>le chat</strong>.</li>
  </ol>
</section>
<section class="lesson-block">
  <h2>Entraînement</h2>
  <p>Corrige les phrases suivantes :</p>
  <ul>
    <li>La chat dort.</li>
    <li>Le école est fermée.</li>
    <li>Les stylo est sur la table.</li>
    <li>La oiseaux chantent.</li>
  </ul>
</section>
<section class="lesson-block">
  <h2>Évaluation</h2>
  <p>L'apprenant doit repérer et corriger chaque anomalie avant d'accéder à l'activité générale.</p>
</section>
HTML,
    ],
    [
        'title' => 'Unité 5 — Activités globales',
        'order' => 5,
        'content_html' => <<<'HTML'
<section class="lesson-block">
  <h2>Objectif</h2>
  <p>Réinvestir toutes les connaissances sur les articles définis dans des activités globales.</p>
</section>
<section class="lesson-block">
  <h2>Activités globales</h2>
  <ol>
    <li>Souligner les articles définis et relier chaque article au nom qu'il accompagne.</li>
    <li>Compléter les phrases avec <strong>le</strong>, <strong>la</strong>, <strong>l'</strong> ou <strong>les</strong>.</li>
    <li>Transformer le singulier en pluriel et inversement.</li>
    <li>Barre l'intrus dans chaque liste.</li>
    <li>Rédiger quatre phrases en utilisant au moins une fois chaque article.</li>
  </ol>
</section>
<section class="lesson-block">
  <h2>Évaluation finale</h2>
  <p>Cette unité prépare le post-test et consolide le parcours complet.</p>
</section>
HTML,
    ],
];

$unitIds = [];
foreach ($units as $unit) {
    $unitIds[$unit['title']] = upsertUnit($pdo, $moduleId, $unit['title'], $unit['content_html'], $unit['order']);
}

$exercises = [
    [
        'unit' => 'Unité 1 — Citer les articles définis',
        'type' => 'mcq',
        'order' => 1,
        'data' => [
            [
                'prompt' => 'Quel mot est un article défini ?',
                'choices' => [
                    ['text' => 'des', 'correct' => 0],
                    ['text' => 'le', 'correct' => 1],
                    ['text' => 'une', 'correct' => 0],
                    ['text' => 'qui', 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 1 — Citer les articles définis',
        'type' => 'dragdrop',
        'order' => 2,
        'data' => [
            [
                'prompt' => 'Associe chaque article à sa bonne utilisation.',
                'items' => [
                    ['id' => 'i1', 'label' => 'le'],
                    ['id' => 'i2', 'label' => 'la'],
                    ['id' => 'i3', 'label' => 'les'],
                    ['id' => 'i4', 'label' => "l'"],
                ],
                'targets' => [
                    ['id' => 't1', 'label' => 'masculin singulier', 'match' => 'i1'],
                    ['id' => 't2', 'label' => 'féminin singulier', 'match' => 'i2'],
                    ['id' => 't3', 'label' => 'pluriel', 'match' => 'i3'],
                    ['id' => 't4', 'label' => 'voyelle ou h muet', 'match' => 'i4'],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 2 — Justifier le choix de l’article',
        'type' => 'mcq',
        'order' => 1,
        'data' => [
            [
                'prompt' => 'Complète : « … maison est grande. »',
                'choices' => [
                    ['text' => 'Le', 'correct' => 0],
                    ['text' => 'La', 'correct' => 1],
                    ['text' => 'Les', 'correct' => 0],
                    ['text' => "L'", 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 2 — Justifier le choix de l’article',
        'type' => 'dragdrop',
        'order' => 2,
        'data' => [
            [
                'prompt' => 'Associe chaque nom à l’article défini correct.',
                'items' => [
                    ['id' => 'i1', 'label' => 'le'],
                    ['id' => 'i2', 'label' => 'la'],
                    ['id' => 'i3', 'label' => 'les'],
                    ['id' => 'i4', 'label' => "l'"],
                ],
                'targets' => [
                    ['id' => 't1', 'label' => 'bureau', 'match' => 'i1'],
                    ['id' => 't2', 'label' => 'porte', 'match' => 'i2'],
                    ['id' => 't3', 'label' => 'enfants', 'match' => 'i3'],
                    ['id' => 't4', 'label' => 'école', 'match' => 'i4'],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 3 — Employer l’article défini correct',
        'type' => 'mcq',
        'order' => 1,
        'data' => [
            [
                'prompt' => 'Complète : « … enfant mange du chocolat. »',
                'choices' => [
                    ['text' => 'La', 'correct' => 0],
                    ['text' => "L'", 'correct' => 1],
                    ['text' => 'le', 'correct' => 0],
                    ['text' => 'Les', 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 3 — Employer l’article défini correct',
        'type' => 'mcq',
        'order' => 2,
        'data' => [
            [
                'prompt' => 'Quel article convient pour « … professeurs sont stricts. » ?',
                'choices' => [
                    ['text' => 'Le', 'correct' => 0],
                    ['text' => 'La', 'correct' => 0],
                    ['text' => 'Les', 'correct' => 1],
                    ['text' => "L'", 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 4 — Identifier et corriger les anomalies',
        'type' => 'mcq',
        'order' => 1,
        'data' => [
            [
                'prompt' => 'Quelle phrase est correcte ?',
                'choices' => [
                    ['text' => 'La chat dort.', 'correct' => 0],
                    ['text' => 'Le chat dort.', 'correct' => 1],
                    ['text' => 'Les chat dort.', 'correct' => 0],
                    ['text' => "L'chat dort.", 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 4 — Identifier et corriger les anomalies',
        'type' => 'dragdrop',
        'order' => 2,
        'data' => [
            [
                'prompt' => 'Associe chaque anomalie à sa règle de correction.',
                'items' => [
                    ['id' => 'i1', 'label' => 'genre'],
                    ['id' => 'i2', 'label' => 'nombre'],
                    ['id' => 'i3', 'label' => 'élision'],
                    ['id' => 'i4', 'label' => 'élision incorrecte'],
                ],
                'targets' => [
                    ['id' => 't1', 'label' => 'le / la selon le nom', 'match' => 'i1'],
                    ['id' => 't2', 'label' => 'singulier / pluriel', 'match' => 'i2'],
                    ['id' => 't3', 'label' => 'devant voyelle ou h muet', 'match' => 'i3'],
                    ['id' => 't4', 'label' => 'devant consonne', 'match' => 'i4'],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 5 — Activités globales',
        'type' => 'mcq',
        'order' => 1,
        'data' => [
            [
                'prompt' => 'Complète : « … soleil brille très fort aujourd’hui. »',
                'choices' => [
                    ['text' => 'Le', 'correct' => 1],
                    ['text' => 'La', 'correct' => 0],
                    ['text' => 'Les', 'correct' => 0],
                    ['text' => "L'", 'correct' => 0],
                ],
            ],
        ],
    ],
    [
        'unit' => 'Unité 5 — Activités globales',
        'type' => 'dragdrop',
        'order' => 2,
        'data' => [
            [
                'prompt' => 'Relie chaque groupe nominal à la bonne forme.',
                'items' => [
                    ['id' => 'i1', 'label' => 'le'],
                    ['id' => 'i2', 'label' => 'l\''],
                    ['id' => 'i3', 'label' => 'les'],
                    ['id' => 'i4', 'label' => 'la'],
                ],
                'targets' => [
                    ['id' => 't1', 'label' => 'cartable', 'match' => 'i1'],
                    ['id' => 't2', 'label' => 'oiseau', 'match' => 'i2'],
                    ['id' => 't3', 'label' => 'exercices', 'match' => 'i3'],
                    ['id' => 't4', 'label' => 'porte', 'match' => 'i4'],
                ],
            ],
        ],
    ],
];

foreach ($exercises as $exercise) {
    upsertExercise(
        $pdo,
        $unitIds[$exercise['unit']],
        $exercise['type'],
        $exercise['order'],
        $exercise['data'],
        1
    );
}

foreach ($studentIds as $studentId) {
    enrollUser($pdo, $studentId, $moduleId);
}

echo "Seeded articles définis curriculum for module #{$moduleId}\n";
