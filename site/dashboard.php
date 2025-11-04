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

// Total mês anterior
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0) AS total_last_month 
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
    AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')
");
$stmt->execute([':uid' => $uid]);
$totalLastMonth = $stmt->fetchColumn();

// Calcular tendência
$tendency = 0;
if ($totalLastMonth > 0) {
    $tendency = (($totalMonth - $totalLastMonth) / $totalLastMonth) * 100;
}

// Transacções últimos 30 dias
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= NOW() - INTERVAL 30 DAY
");
$stmt->execute([':uid' => $uid]);
$count30 = $stmt->fetchColumn();

// Gastos por categoria (mês atual)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(category, 'Sem Categoria') as category,
        SUM(amount) as total,
        COUNT(*) as count
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute([':uid' => $uid]);
$categoryData = $stmt->fetchAll();

// Maior despesa do mês
$stmt = $pdo->prepare("
    SELECT description, amount, category
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ORDER BY amount DESC
    LIMIT 1
");
$stmt->execute([':uid' => $uid]);
$biggestExpense = $stmt->fetch();

// Atividade dos últimos 7 dias
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as day,
        SUM(amount) as total,
        COUNT(*) as count
    FROM transactions 
    WHERE user_id = :uid 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$stmt->execute([':uid' => $uid]);
$last7Days = $stmt->fetchAll();

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

// Cores para categorias
$categoryColors = [
    'Compras' => '#3498db',
    'Alimentação' => '#e74c3c',
    'Transporte' => '#f39c12',
    'Saúde' => '#1abc9c',
    'Entretenimento' => '#9b59b6',
    'Educação' => '#34495e',
    'Casa' => '#e67e22',
    'Outros' => '#95a5a6',
    'Sem Categoria' => '#bdc3c7'
];
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - FreeCard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-green: #2ecc71;
      --dark-green: #27ae60;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .card { 
      transition: transform 0.2s; 
      border: none;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .card:hover { transform: translateY(-5px); }
    .stat-card { border-left: 4px solid var(--primary-green); }
    .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .navbar-brand img { height: 35px; margin-right: 8px; }
    .btn-primary { 
      background: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .btn-primary:hover { 
      background: var(--dark-green); 
      border-color: var(--dark-green); 
    }
    .btn-outline-primary { 
      color: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .btn-outline-primary:hover { 
      background: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .text-primary { color: var(--primary-green) !important; }
    
    /* Gráfico de barras custom */
    .category-bar-container {
      margin-bottom: 20px;
    }
    .category-bar-label {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .category-bar-wrapper {
      background: #f0f0f0;
      border-radius: 10px;
      height: 32px;
      overflow: hidden;
      position: relative;
    }
    .category-bar {
      height: 100%;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding-right: 12px;
      color: white;
      font-weight: 600;
      font-size: 13px;
      transition: width 1s ease-out;
      background: linear-gradient(90deg, var(--bar-color), var(--bar-color-light));
    }
    
    /* Stats cards */
    .stat-mini-card {
      background: white;
      border-radius: 12px;
      padding: 16px;
      border-left: 3px solid;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    /* Timeline */
    .activity-timeline {
      display: flex;
      gap: 4px;
      justify-content: space-between;
    }
    .activity-day {
      flex: 1;
      height: 80px;
      background: #e9ecef;
      border-radius: 8px;
      position: relative;
      overflow: hidden;
      transition: all 0.3s;
    }
    .activity-day:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .activity-day-fill {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(180deg, var(--primary-green), var(--dark-green));
      transition: height 0.8s ease-out;
    }
    .activity-day-label {
      position: absolute;
      bottom: 4px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 10px;
      color: white;
      font-weight: 600;
    }
    .activity-day-amount {
      position: absolute;
      top: 4px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      color: #2c3e50;
    }
    
    .tendency-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
    }
    .tendency-up {
      background: #fee;
      color: #c00;
    }
    .tendency-down {
      background: #efe;
      color: #0a0;
    }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
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
        <li class="nav-item"><a class="nav-link" href="cards.php"><i class="bi bi-wallet2"></i> Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php"><i class="bi bi-receipt"></i> Transações</a></li>
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

<div class="container mt-4">
  <?php if (!empty($alerts)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong><i class="bi bi-exclamation-triangle"></i> Alertas:</strong>
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
          <h5 class="card-title mb-3"><i class="bi bi-graph-up"></i> Resumo Rápido</h5>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">Gasto este mês</small>
              <?php if ($tendency != 0): ?>
                <span class="tendency-badge <?=$tendency > 0 ? 'tendency-up' : 'tendency-down'?>">
                  <i class="bi bi-arrow-<?=$tendency > 0 ? 'up' : 'down'?>"></i>
                  <?=abs(round($tendency))?>%
                </span>
              <?php endif; ?>
            </div>
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
            <a href="create_transaction.php" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Nova Transação
            </a>
            <a href="add_card.php" class="btn btn-outline-primary">
              <i class="bi bi-credit-card-2-front"></i> Adicionar Cartão
            </a>
          </div>
        </div>
      </div>

      <!-- Os teus cartões -->
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="bi bi-wallet2"></i> Os Teus Cartões</h6>
          <?php if (empty($cards)): ?>
            <div class="text-center py-3">
              <p class="text-muted mb-2">Ainda não tens cartões</p>
              <a href="add_card.php" class="btn btn-sm btn-primary">Adicionar primeiro cartão</a>
            </div>
          <?php else: ?>
            <?php 
            $displayCards = array_slice($cards, 0, 2); // Mostrar apenas 2 cartões
            $remainingCount = count($cards) - 2;
            foreach($displayCards as $c): ?>
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
            
            <?php if ($remainingCount > 0): ?>
              <div class="text-center mb-3 p-3 border rounded bg-light">
                <i class="bi bi-plus-circle text-muted"></i>
                <small class="text-muted d-block">+<?=$remainingCount?> cart<?=$remainingCount > 1 ? 'ões' : 'ão'?></small>
              </div>
            <?php endif; ?>
            
            <a href="cards.php" class="btn btn-sm btn-outline-secondary w-100">Gerir todos os cartões</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Coluna direita: Análise e Transações -->
    <div class="col-12 col-lg-8">
      
      <!-- Estatísticas Mini -->
      <?php if (!empty($categoryData) || $biggestExpense): ?>
      <div class="row g-3 mb-4">
        <?php if ($biggestExpense): ?>
        <div class="col-md-6">
          <div class="stat-mini-card" style="border-left-color: #e74c3c;">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <small class="text-muted">Maior Despesa</small>
                <h4 class="mb-0 text-danger">€<?=number_format($biggestExpense['amount'],2)?></h4>
                <small class="text-muted"><?=htmlspecialchars($biggestExpense['description'])?></small>
              </div>
              <i class="bi bi-exclamation-circle text-danger" style="font-size: 24px;"></i>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($categoryData)): ?>
        <div class="col-md-6">
          <div class="stat-mini-card" style="border-left-color: var(--primary-green);">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <small class="text-muted">Categoria Top</small>
                <h4 class="mb-0 text-primary"><?=htmlspecialchars($categoryData[0]['category'])?></h4>
                <small class="text-muted">€<?=number_format($categoryData[0]['total'],2)?> em <?=$categoryData[0]['count']?> transações</small>
              </div>
              <i class="bi bi-star-fill text-warning" style="font-size: 24px;"></i>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Gráfico de Gastos por Categoria -->
      <?php if (!empty($categoryData)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title mb-4"><i class="bi bi-bar-chart"></i> Gastos por Categoria</h5>
          <?php 
          $maxAmount = max(array_column($categoryData, 'total'));
          foreach ($categoryData as $cat): 
            $percentage = ($cat['total'] / $maxAmount) * 100;
            $color = $categoryColors[$cat['category']] ?? '#95a5a6';
          ?>
          <div class="category-bar-container">
            <div class="category-bar-label">
              <span><strong><?=htmlspecialchars($cat['category'])?></strong> <small class="text-muted">(<?=$cat['count']?> transações)</small></span>
              <span class="text-danger fw-bold">€<?=number_format($cat['total'],2)?></span>
            </div>
            <div class="category-bar-wrapper">
              <div class="category-bar" 
                   style="--bar-color: <?=$color?>; --bar-color-light: <?=$color?>aa; width: 0%;"
                   data-width="<?=$percentage?>%">
                <?=round(($cat['total'] / $totalMonth) * 100)?>%
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Últimas Transações -->
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="bi bi-receipt"></i> Últimas Transações</h5>
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
              <a href="transactions.php" class="btn btn-outline-primary">Ver todas as transações</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Animar barras de categoria
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(() => {
    document.querySelectorAll('.category-bar').forEach(bar => {
      bar.style.width = bar.dataset.width;
    });
  }, 100);
  
  // Animar timeline de atividade
  setTimeout(() => {
    document.querySelectorAll('.activity-day-fill').forEach(fill => {
      fill.style.height = fill.dataset.height;
    });
  }, 300);
});
</script>
</body>
</html>