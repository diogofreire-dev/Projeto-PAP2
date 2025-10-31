<?php
require_once __DIR__ . '/auth.php';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
  <h1>Dashboard</h1>
  <p>Olá, <?=htmlspecialchars($_SESSION['username'])?> — bem-vindo!</p>
  <a href="logout.php">Sair</a>
</body>
</html>
