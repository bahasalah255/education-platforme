<?php
function generate_remediation($pdo, $unit_id){
  // avoid generating multiple times for same unit
  $chk = $pdo->prepare('SELECT COUNT(*) FROM exercises WHERE unit_id = ? AND CAST(data AS CHAR) LIKE ?');
  $chk->execute([$unit_id, '%"remediation_generated"%']);
  if ($chk->fetchColumn() > 0) return false;

  $examples = [
    [
      'prompt' => "Choisissez l'article défini correct pour: '____ chat est noir.'",
      'choices' => [ ['text'=>'Le','correct'=>1], ['text'=>'La','correct'=>0], ['text'=>'Les','correct'=>0], ['text'=>'L\'','correct'=>0] ]
    ],
    [
      'prompt' => "Complète: '____ amie de Houda arrive demain.'",
      'choices' => [ ['text'=>'Le','correct'=>0], ['text'=>'La','correct'=>1], ['text'=>'Les','correct'=>0], ['text'=>'L\'','correct'=>0] ]
    ],
    [
      'prompt' => "Quel article convient pour 'école'?",
      'choices' => [ ['text'=>'L\'','correct'=>1], ['text'=>'la','correct'=>0], ['text'=>'le','correct'=>0], ['text'=>'les','correct'=>0] ]
    ]
  ];

  $ins = $pdo->prepare('INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES (?, ?, ?, ?, ?)');
  $order = 1000;
  foreach ($examples as $ex){
    $payload = $ex; $payload['remediation_generated'] = true;
    $ins->execute([$unit_id, 'mcq', json_encode([$payload], JSON_UNESCAPED_UNICODE), 1, $order++]);
  }
  return true;
}
