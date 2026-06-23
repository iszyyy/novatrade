<?php
require_once '../config.php';

if(isset($_POST['login'])){
 $u=$_POST['username']; $p=$_POST['password'];
 $s=$pdo->prepare("SELECT * FROM admins WHERE username=?");
 $s->execute([$u]); $a=$s->fetch();
 if($a && password_verify($p,$a['password'])){
   $_SESSION['admin']=1; header('Location: index.php'); exit;
 }
}

if(isset($_GET['logout'])){session_destroy(); header('Location:index.php'); exit;}

if(!isset($_SESSION['admin'])){
?>
<form method="post" style="max-width:350px;margin:100px auto;font-family:sans-serif">
<h2>AdminCP Login</h2>
<input name="username" placeholder="Username"><br><br>
<input type="password" name="password" placeholder="Password"><br><br>
<button name="login">Login</button>
</form>
<?php exit; }

if(isset($_POST['upload']) && isset($_FILES['installer'])){
 move_uploaded_file($_FILES['installer']['tmp_name'],'../uploads/installer.exe');
}

$visits=$pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
$uvisits=$pdo->query("SELECT COUNT(DISTINCT ip) FROM visits")->fetchColumn();
$downloads=$pdo->query("SELECT COUNT(*) FROM downloads")->fetchColumn();
$udownloads=$pdo->query("SELECT COUNT(DISTINCT ip) FROM downloads")->fetchColumn();
?>
<!doctype html><html><body style="font-family:sans-serif;max-width:1000px;margin:auto">
<h1>NovaTrade AdminCP</h1>
<a href="?logout=1">Logout</a>
<h3>Stats</h3>
<ul>
<li>Total Visits: <?= $visits ?></li>
<li>Unique Visitors: <?= $uvisits ?></li>
<li>Total Downloads: <?= $downloads ?></li>
<li>Unique Downloaders: <?= $udownloads ?></li>
</ul>

<h3>Upload Installer</h3>
<form method="post" enctype="multipart/form-data">
<input type="file" name="installer">
<button name="upload">Replace Installer</button>
</form>

<h3>Recent Downloads</h3>
<table border="1" cellpadding="5">
<tr><th>IP</th><th>Date</th></tr>
<?php foreach($pdo->query("SELECT * FROM downloads ORDER BY id DESC LIMIT 100") as $r){ ?>
<tr><td><?=htmlspecialchars($r['ip'])?></td><td><?=$r['created_at']?></td></tr>
<?php } ?>
</table>
</body></html>
