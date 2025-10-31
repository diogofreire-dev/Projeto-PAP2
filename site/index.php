<?php
session_start();

// Se já estiver autenticado, redireciona para dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PAP Finanças - Gestão de Cartões e Transações</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .hero {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 80px 0;
    }
    .feature-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    .feature-card {
      transition: transform 0.3s;
      height: 100%;
    }
    .feature-card:hover {
      transform: translateY(-10px);
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💳 PAP Finanças</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
        <li class="nav-item"><a class="btn btn-primary btn-sm" href="register.php">Registar</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="container text-center">
    <h1 class="display-3 fw-bold mb-4">Controla as Tuas Finanças</h1>
    <p class="lead mb-5">Gere os teus cartões e transações de forma simples e organizada. Tudo num só lugar.</p>
    <div class="d-flex gap-3 justify-content-center">
      <a href="register.php" class="btn btn-light btn-lg px-5">Começar Agora →</a>
      <a href="login.php" class="btn btn-outline-light btn-lg px-5">Já tenho conta</a>
    </div>
  </div>
</section>

<!-- Features -->
<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-5">O que podes fazer?</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card feature-card border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <div class="feature-icon">💳</div>
            <h4>Gerir Cartões</h4>
            <p class="text-muted">Adiciona e organiza todos os teus cartões de crédito. Acompanha limites e saldos em tempo real.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <div class="feature-icon">🧾</div>
            <h4>Registar Transações</h4>
            <p class="text-muted">Regista todas as tuas despesas com descrições, categorias e associação a cartões.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <div class="feature-icon">📊</div>
            <h4>Acompanhar Gastos</h4>
            <p class="text-muted">Visualiza resumos mensais, alertas de limite e histórico completo das tuas finanças.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row text-center">
      <div class="col-md-3">
        <div class="p-4">
          <h2 class="display-4 fw-bold text-primary">100%</h2>
          <p class="text-muted">Gratuito</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <h2 class="display-4 fw-bold text-primary">🔒</h2>
          <p class="text-muted">Seguro</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <h2 class="display-4 fw-bold text-primary">⚡</h2>
          <p class="text-muted">Rápido</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <h2 class="display-4 fw-bold text-primary">📱</h2>
          <p class="text-muted">Responsive</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="py-5">
  <div class="container text-center">
    <h2 class="mb-4">Pronto para começar?</h2>
    <p class="lead text-muted mb-4">Cria a tua conta gratuitamente e começa a gerir as tuas finanças hoje mesmo.</p>
    <a href="register.php" class="btn btn-primary btn-lg px-5">Criar Conta Gratuita →</a>
  </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
  <div class="container text-center">
    <p class="mb-0">&copy; <?=date('Y')?> PAP Finanças - Projeto de Aptidão Profissional</p>
    <small class="text-muted">Desenvolvido com ❤️ por Diogo Freire</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>