<?php
session_start();

// Se já estiver autenticado, redireciona para dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Freecard - Gestão de Cartões e Transações</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-green: #2ecc71;
      --dark-green: #27ae60;
      --light-green: #a8e6cf;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .navbar {
      padding: 20px 0;
      background: white !important;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 24px;
    }
    
    .navbar-brand img {
      height: 40px;
      margin-right: 10px;
    }
    
    .hero {
      background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
      color: white;
      padding: 120px 0 100px;
      position: relative;
      overflow: hidden;
    }
    
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
      opacity: 0.3;
    }
    
    .hero-content {
      position: relative;
      z-index: 1;
    }
    
    .hero h1 {
      font-size: 52px;
      font-weight: 800;
      margin-bottom: 24px;
      line-height: 1.2;
    }
    
    .hero p {
      font-size: 20px;
      margin-bottom: 40px;
      opacity: 0.95;
    }
    
    .btn-light {
      background: white;
      color: var(--primary-green);
      border: none;
      padding: 14px 36px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .btn-light:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      color: var(--dark-green);
    }
    
    .btn-outline-light {
      border: 2px solid white;
      color: white;
      padding: 14px 36px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .btn-outline-light:hover {
      background: white;
      color: var(--primary-green);
      transform: translateY(-2px);
    }
    
    .feature-section {
      padding: 100px 0;
    }
    
    .section-title {
      font-size: 42px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 60px;
    }
    
    .feature-card {
      background: white;
      border-radius: 20px;
      padding: 40px;
      transition: all 0.3s;
      border: 1px solid #f0f0f0;
      height: 100%;
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 50px rgba(0,0,0,0.08);
      border-color: var(--light-green);
    }
    
    .feature-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
      color: white;
      font-size: 32px;
    }
    
    .feature-card h4 {
      font-size: 22px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 16px;
    }
    
    .feature-card p {
      color: #7f8c8d;
      font-size: 15px;
      line-height: 1.7;
      margin: 0;
    }
    
    .stats-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 80px 0;
      color: white;
    }
    
    .stat-box {
      text-align: center;
      padding: 30px;
    }
    
    .stat-box h2 {
      font-size: 56px;
      font-weight: 800;
      margin-bottom: 10px;
    }
    
    .stat-box p {
      font-size: 18px;
      opacity: 0.9;
      margin: 0;
    }
    
    .cta-section {
      padding: 100px 0;
      background: #f8f9fa;
    }
    
    .cta-card {
      background: white;
      border-radius: 24px;
      padding: 60px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.05);
      text-align: center;
    }
    
    .cta-card h2 {
      font-size: 38px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 20px;
    }
    
    .cta-card p {
      font-size: 18px;
      color: #7f8c8d;
      margin-bottom: 40px;
    }
    
    .btn-success {
      background: var(--primary-green);
      border: none;
      padding: 16px 48px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 18px;
      transition: all 0.3s;
    }
    
    .btn-success:hover {
      background: var(--dark-green);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
    }
    
    footer {
      background: #2c3e50;
      color: white;
      padding: 50px 0 30px;
    }
    
    footer a {
      color: var(--light-green);
      text-decoration: none;
    }
    
    footer a:hover {
      color: var(--primary-green);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="assets/logo.png" alt="Freecard">
      Freecard
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
          <a class="nav-link" href="login.php">Entrar</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-success" href="register.php">Começar agora</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 hero-content">
        <h1>Controla as tuas finanças com simplicidade</h1>
        <p>Gere os teus cartões e transações de forma inteligente. Acompanha os teus gastos e mantém-te sempre dentro do orçamento.</p>
        <div class="d-flex gap-3">
          <a href="register.php" class="btn btn-light btn-lg">
            Começar gratuitamente <i class="bi bi-arrow-right"></i>
          </a>
          <a href="login.php" class="btn btn-outline-light btn-lg">
            Já tenho conta
          </a>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block text-center">
        <img src="assets/logo.png" alt="Freecard" style="max-width: 400px; opacity: 0.15;">
      </div>
    </div>
  </div>
</section>

<section class="feature-section">
  <div class="container">
    <h2 class="section-title text-center">Tudo o que precisas para gerir as tuas finanças</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-credit-card-2-front"></i>
          </div>
          <h4>Gestão de Cartões</h4>
          <p>Adiciona e organiza todos os teus cartões de crédito num só lugar. Acompanha limites, saldos e mantém o controlo total.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-receipt"></i>
          </div>
          <h4>Registo de Transações</h4>
          <p>Regista todas as tuas despesas com descrições detalhadas e categorias. Associa cada transação ao cartão correspondente.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="bi bi-graph-up"></i>
          </div>
          <h4>Análise de Gastos</h4>
          <p>Visualiza resumos mensais, recebe alertas quando te aproximas dos limites e analisa o histórico completo das tuas finanças.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="stats-section">
  <div class="container">
    <div class="row">
      <div class="col-md-3">
        <div class="stat-box">
          <h2>100%</h2>
          <p>Gratuito</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-box">
          <h2><i class="bi bi-shield-check"></i></h2>
          <p>Seguro e Protegido</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-box">
          <h2><i class="bi bi-lightning-charge"></i></h2>
          <p>Rápido e Simples</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-box">
          <h2><i class="bi bi-phone"></i></h2>
          <p>100% Responsive</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container">
    <div class="cta-card">
      <h2>Pronto para começar?</h2>
      <p>Cria a tua conta gratuitamente e começa a gerir as tuas finanças de forma inteligente.</p>
      <a href="register.php" class="btn btn-success btn-lg">
        <i class="bi bi-check-circle"></i> Criar conta gratuita
      </a>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="row">
      <div class="col-md-6 mb-4 mb-md-0">
        <h5 class="mb-3">
          <img src="assets/logo.png" alt="Freecard" style="height: 30px; margin-right: 10px;">
          Freecard
        </h5>
        <p class="text-light">Gestão inteligente de cartões e transações. Simples, rápido e gratuito.</p>
      </div>
      <div class="col-md-3 mb-4 mb-md-0">
        <h6 class="mb-3">Links Rápidos</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="register.php">Criar Conta</a></li>
          <li class="mb-2"><a href="login.php">Entrar</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h6 class="mb-3">Projeto</h6>
        <p class="text-light small">Projeto de Aptidão Profissional<br>Desenvolvido por Diogo Freire</p>
      </div>
    </div>
    <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
    <div class="text-center">
      <p class="mb-0 text-light">&copy; <?=date('Y')?> Freecard. Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>