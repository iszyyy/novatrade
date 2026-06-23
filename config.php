<?php
$pdo = new PDO("mysql:host=localhost;dbname=novatrad_main;charset=utf8mb4","novatrad_root","UBR7+K[30%-T");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start();
?>
