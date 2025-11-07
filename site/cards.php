<?php
// site/cards.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$message = '';
$messageType = 'info';

// Mapeamento de cores
$cardColors = [
    'purple' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'blue' => 'linear-gradient(135deg, #2196F3 0%, #1976D2 100%)',
    'green' => 'linear-gradient(135deg, #13d168ff 0%, #005218ff 100%)',
    'orange' => 'linear-gradient(135deg, #FF9800 0%, #F57C00 100%)',
    'red' => 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)',
    'pink' => 'linear-gradient(135deg, #E91E63 0%, #C2185B 100%)',
    'teal' => 'linear-gradient(135deg, #00BCD4 0%, #0097A7 100%)',
    'indigo' => 'linear-gradient(135deg, #3F51B5 0%, #303F9F 100%)'
];

// Ações: ativar/desativar/eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $cardId = intval($_POST['card_id'] ?? 0);
    $action = $_POST['action'];

    try {
        switch($action) {
            case 'toggle':
                $stmt = $pdo->prepare("UPDATE cards SET active = NOT active WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $cardId, ':uid' => $uid]);
                $message = 'Estado do cartão alterado com sucesso!';
                $messageType = 'success';
                break;
            
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM cards WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $cardId, ':uid' => $uid]);
                $message = 'Cartão eliminado com sucesso!';
                $messageType = 'success';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Erro ao executar a ação.';
        $messageType = 'danger';
    }
}

// Buscar todos os cartões com estatísticas
$stmt = $pdo->prepare("
    SELECT c.*, 
           COALESCE(SUM(t.amount), 0) as total_spent,
           COUNT(t.id) as transaction_count
    FROM cards c
    LEFT JOIN transactions t ON t.card_id = c.id
    WHERE c.user_id = :uid
    GROUP BY c.id
    ORDER BY c.active DESC, c.created_at DESC
");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Estatísticas gerais
$totalLimit = array_sum(array_column($cards, 'limit_amount'));
$totalBalance = array_sum(array_column($cards, 'balance'));
$activeCards = count(array_filter($cards, fn($c) => $c['active']));
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerir Cartões - FreeCard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-green: #2ecc71;
      --dark-green: #27ae60;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: #f8f9fa;
    }
    .navbar { 
      box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
      background: white;
    }
    .navbar-brand img { height: 35px; margin-right: 8px; }
    .btn-primary { 
      background: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .btn-primary:hover { 
      background: var(--dark-green); 
      border-color: var(--dark-green); 
    }
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    .card-visual {
      border-radius: 12px;
      padding: 20px;
      color: white;
      min-height: 140px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }
    .card-visual::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -30%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }
    .card-visual-inactive {
      background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%) !important;
      opacity: 0.7;
    }
    .card-number {
      font-size: 20px;
      letter-spacing: 3px;
      font-weight: 600;
      position: relative;
    }
    .card-name {
      font-size: 14px;
      text-transform: uppercase;
      font-weight: 700;
      position: relative;
    }
    .stat-box {
      background: white;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      border: 2px solid #f0f0f0;
    }
    .stat-box h3 {
      color: var(--primary-green);
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 8px;
    }
    .stat-box p {
      color: #7f8c8d;
      margin: 0;
      font-size: 14px;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="assets/logo2.png" alt="Freecard">
      FreeCard
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="cards.php"><i class="bi bi-wallet2"></i> Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php"><i class="bi bi-receipt"></i> Transações</a></li>
        <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-graph-up"></i> Análise</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?=htmlspecialchars($_SESSION['username'])?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><i class="bi bi-wallet2"></i> Os Meus Cartões</h2>
      <p class="text-muted mb-0">Gere os teus cartões e acompanha os limites</p>
    </div>
    <a href="add_card.php" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Adicionar Cartão
    </a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?=$messageType?> alert-dismissible fade show">
      <i class="bi bi-<?=$messageType === 'success' ? 'check-circle' : 'info-circle'?>"></i>
      <?=htmlspecialchars($message)?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($cards)): ?>
    <!-- Estatísticas -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="stat-box">
          <h3><?=count($cards)?></h3>
          <p>Total de Cartões</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-box">
          <h3>€<?=number_format($totalLimit, 2)?></h3>
          <p>Limite Total</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-box">
          <h3>€<?=number_format($totalLimit - $totalBalance, 2)?></h3>
          <p>Disponível</p>
        </div>
      </div>
    </div>

    <!-- Cartões -->
    <div class="row g-4">
      <?php foreach($cards as $c): ?>
        <?php 
          $percentage = $c['limit_amount'] > 0 ? ($c['balance'] / $c['limit_amount']) * 100 : 0;
          $progressColor = $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
          $available = $c['limit_amount'] - $c['balance'];
          $cardColor = $c['color'] ?? 'purple';
          $gradient = $cardColors[$cardColor] ?? $cardColors['purple'];
        ?>
        <div class="col-12 col-md-6 col-xl-4">
          <div class="card h-100">
            <div class="card-body p-4">
              <!-- Card Visual -->
              <div class="card-visual <?=$c['active'] ? '' : 'card-visual-inactive'?> mb-3" style="background: <?=$gradient?>;">
                <div>
                  <div class="mb-2">
                    <i class="bi bi-credit-card" style="font-size: 28px;"></i>
                  </div>
                  <div class="card-number">•••• •••• •••• <?=htmlspecialchars($c['last4'])?></div>
                </div>
                <div>
                  <div class="card-name"><?=htmlspecialchars($c['name'])?></div>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small>FreeCard</small>
                    <span class="badge bg-<?=$c['active'] ? 'light' : 'secondary'?> text-dark">
                      <?=$c['active'] ? 'ATIVO' : 'INATIVO'?>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Informações -->
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Utilização do Limite</span>
                  <span class="fw-bold small"><?=round($percentage)?>%</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 10px;">
                  <div class="progress-bar bg-<?=$progressColor?>" style="width: <?=min($percentage, 100)?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                  <small class="text-muted">€<?=number_format($c['balance'],2)?> usado</small>
                  <small class="text-muted">€<?=number_format($c['limit_amount'],2)?> limite</small>
                </div>
              </div>

              <div class="p-3 bg-light rounded mb-3">
                <div class="row text-center">
                  <div class="col-6 border-end">
                    <div class="fw-bold text-success">€<?=number_format($available, 2)?></div>
                    <small class="text-muted">Disponível</small>
                  </div>
                  <div class="col-6">
                    <div class="fw-bold"><?=$c['transaction_count']?></div>
                    <small class="text-muted">Transações</small>
                  </div>
                </div>
              </div>

              <!-- Ações -->
              <div class="d-flex gap-2">
                <form method="post" class="flex-fill">
                  <input type="hidden" name="card_id" value="<?=$c['id']?>">
                  <input type="hidden" name="action" value="toggle">
                  <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-<?=$c['active'] ? 'pause-circle' : 'play-circle'?>"></i>
                    <?=$c['active'] ? 'Desativar' : 'Ativar'?>
                  </button>
                </form>
                <form method="post" onsubmit="return confirm('Tens a certeza que queres eliminar este cartão? Esta ação não pode ser revertida.');">
                  <input type="hidden" name="card_id" value="<?=$c['id']?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>

              <div class="text-center mt-3">
                <small class="text-muted">
                  <i class="bi bi-calendar"></i> Criado em <?=date('d/m/Y', strtotime($c['created_at']))?>
                </small>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <!-- Estado vazio -->
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="mb-4">
          <i class="bi bi-credit-card-2-front" style="font-size: 80px; color: #e0e0e0;"></i>
        </div>
        <h4 class="text-muted mb-3">Ainda não tens cartões registados</h4>
        <p class="text-muted mb-4">Adiciona o teu primeiro cartão para começares a gerir as tuas finanças de forma inteligente</p>
        <a href="add_card.php" class="btn btn-primary btn-lg">
          <i class="bi bi-plus-circle"></i> Adicionar Primeiro Cartão
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>