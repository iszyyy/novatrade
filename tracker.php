<?php
require_once 'config.php';
$ip=$_SERVER['REMOTE_ADDR'] ?? '';
$ua=$_SERVER['HTTP_USER_AGENT'] ?? '';
$pdo->prepare("INSERT INTO visits(ip,user_agent) VALUES(?,?)")->execute([$ip,$ua]);
?>
