<?php
session_start();
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PAP - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand" href="#">PAP</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="#"><?=htmlspecialchars($_SESSION['username'])?></a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="register.php">Registar</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <h1>Bem-vindo ao teu Projeto PAP</h1>
  <p>Regista uma conta ou entra para continuar.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
