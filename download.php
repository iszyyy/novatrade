<?php
require_once 'config.php';
$ip=$_SERVER['REMOTE_ADDR'] ?? '';
$ua=$_SERVER['HTTP_USER_AGENT'] ?? '';
$pdo->prepare("INSERT INTO downloads(ip,user_agent) VALUES(?,?)")->execute([$ip,$ua]);
$file='uploads/installer.exe';
if(file_exists($file)){
 header('Content-Type: application/octet-stream');
 header('Content-Disposition: attachment; filename="NovaTradeSetup.exe"');
 readfile($file);
}
