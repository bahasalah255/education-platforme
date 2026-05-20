<?php
require_once __DIR__.'/../src/config.php';
$stmt = $pdo->prepare('SELECT u.id, u.username, (SELECT COUNT(*) FROM attempts a WHERE a.user_id=u.id) as attempts FROM users u WHERE username=?');
$stmt->execute(['qa_student']);
$r = $stmt->fetchAll();
echo json_encode($r, JSON_PRETTY_PRINT);
