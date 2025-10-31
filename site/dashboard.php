<?php
// site/dashboard.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    header('Location: login.php');
    exit;
}

// Total gasto no mês
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) AS total_month 
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
");
$stmt->execute([':uid' => $uid]);
$totalMonth = $stmt->fetchColumn();

// Transacções últimos 30 dias
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= NOW() - INTERVAL 30 DAY
");
$stmt->execute([':uid' => $uid]);
$count30 = $stmt->fetchColumn();

// Últimos registos
$stmt = $pdo->prepare("
    SELECT t.*, c.name AS card_name, c.last4 
    FROM transactions t 
    LEFT JOIN cards c ON c.id = t.card_id 
    WHERE t.user_id = :uid 
    ORDER BY t.created_at DESC 
    LIMIT 8
");
$stmt->execute([':uid' => $uid]);
$recent = $stmt->fetchAll();

// Cartões
$stmt = $pdo->prepare("
    SELECT id, name, last4, limit_amount, balance, active 
    FROM cards 
    WHERE user_id = :uid
");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Alertas: cartão com >80% do limite
$alerts = [];
foreach ($cards as $card) {
    if ($card['limit_amount'] > 0) {
        $pct = ($card['balance'] / $card['limit_amount']) * 100;
        if ($pct >= 80) {
            $alerts[] = "Cartão {$card['name']} ({$card['last4']}) atingiu " . round($pct) . "% do limite.";
        }
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .card { transition: transform 0.2s; }
    .card:hover { transform: translateY(-5px); }
    .stat-card { border-left: 4px solid #0d6efd; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💳 PAP Finanças</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transações</a></li>
        <li class="nav-item"><a class="nav-link" href="cards.php">Cartões</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            👤 <?=htmlspecialchars($_SESSION['username'])?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="logout.php">Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <?php if (!empty($alerts)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong>⚠️ Alertas:</strong>
      <ul class="mb-0 mt-2">
        <?php foreach($alerts as $a): ?>
          <li><?=htmlspecialchars($a)?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Coluna esquerda: Resumo -->
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm stat-card">
        <div class="card-body">
          <h5 class="card-title mb-3">📊 Resumo Rápido</h5>
          <div class="mb-3">
            <small class="text-muted">Gasto este mês</small>
            <h3 class="mb-0 text-primary">€<?=number_format($totalMonth,2)?></h3>
          </div>
          <hr>
          <div class="d-flex justify-content-between mb-2">
            <span>Transações (30d)</span>
            <strong><?=intval($count30)?></strong>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span>Cartões ativos</span>
            <strong><?=count($cards)?></strong>
          </div>
          <div class="d-grid gap-2">
            <a href="create_transaction.php" class="btn btn-primary">➕ Nova Transação</a>
            <a href="add_card.php" class="btn btn-outline-primary">💳 Adicionar Cartão</a>
          </div>
        </div>
      </div>

      <!-- Os teus cartões -->
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h6 class="card-title mb-3">💳 Os Teus Cartões</h6>
          <?php if (empty($cards)): ?>
            <div class="text-center py-3">
              <p class="text-muted mb-2">Ainda não tens cartões</p>
              <a href="add_card.php" class="btn btn-sm btn-primary">Adicionar primeiro cartão</a>
            </div>
          <?php else: ?>
            <?php foreach($cards as $c): ?>
              <?php 
                $percentage = $c['limit_amount'] > 0 ? ($c['balance'] / $c['limit_amount']) * 100 : 0;
                $progressColor = $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
              ?>
              <div class="mb-3 p-3 border rounded">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <strong><?=htmlspecialchars($c['name'])?></strong><br>
                    <small class="text-muted">•••• <?=htmlspecialchars($c['last4'])?></small>
                  </div>
                  <span class="badge bg-<?=$c['active'] ? 'success' : 'secondary'?>">
                    <?=$c['active'] ? 'Ativo' : 'Inativo'?>
                  </span>
                </div>
                <div class="progress mb-2" style="height: 8px;">
                  <div class="progress-bar bg-<?=$progressColor?>" style="width: <?=min($percentage, 100)?>%"></div>
                </div>
                <div class="d-flex justify-content-between small">
                  <span>€<?=number_format($c['balance'],2)?> / €<?=number_format($c['limit_amount'],2)?></span>
                  <span><?=round($percentage)?>%</span>
                </div>
              </div>
            <?php endforeach; ?>
            <a href="cards.php" class="btn btn-sm btn-outline-secondary w-100">Gerir todos os cartões</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Coluna direita: Transações -->
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0">🧾 Últimas Transações</h5>
        </div>
        <div class="card-body">
          <?php if (empty($recent)): ?>
            <div class="text-center py-5">
              <p class="text-muted mb-3">Ainda não tens transações registadas</p>
              <a href="create_transaction.php" class="btn btn-primary">Criar primeira transação</a>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Cartão</th>
                    <th class="text-end">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($recent as $r): ?>
                    <tr>
                      <td><?=date('d/m/Y H:i', strtotime($r['created_at']))?></td>
                      <td><?=htmlspecialchars($r['description'] ?: '-')?></td>
                      <td>
                        <?php if($r['category']): ?>
                          <span class="badge bg-info"><?=htmlspecialchars($r['category'])?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if($r['card_name']): ?>
                          <small><?=htmlspecialchars($r['card_name'])?> (<?=htmlspecialchars($r['last4'])?>)</small>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <strong class="text-danger">-€<?=number_format($r['amount'],2)?></strong>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-center mt-3">
              <a href="transactions.php" class="btn btn-outline-primary">Ver todas as transações →</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>