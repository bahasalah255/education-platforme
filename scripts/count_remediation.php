<?php
require_once __DIR__.'/../src/config.php';
$s = $pdo->query("SELECT COUNT(*) AS c FROM exercises WHERE data LIKE '%remediation_generated%'");
$r = $s->fetch();
echo intval($r['c'])."\n";
