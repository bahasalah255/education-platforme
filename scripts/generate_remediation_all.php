<?php
// CLI/web helper: generate remediation exercises for all units
require_once __DIR__.'/../src/remediation.php';
require_once __DIR__.'/../src/auth.php';

// allow running from CLI
$units = $pdo->query('SELECT id,title FROM units')->fetchAll();
$count = 0;
foreach ($units as $u){
  try{
    $ok = generate_remediation($pdo, $u['id']);
    if ($ok) $count++;
  }catch(Exception $e){ }
}

echo "Generated remediation for $count units\n";
