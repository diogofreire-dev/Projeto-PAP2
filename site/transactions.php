<?php
// site/transactions.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;

// Filtros
$category = $_GET['category'] ?? '';
$card_id = !empty($_GET['card_id']) ? intval($_GET['card_id']) : null;
$month = $_GET['month'] ?? date('Y-m');

// Buscar categorias únicas
$stmt = $pdo->prepare("SELECT DISTINCT category FROM transactions WHERE user_id = :uid AND category IS NOT NULL ORDER BY category");
$stmt->execute([':uid' => $uid]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar cartões
$stmt = $pdo->prepare("SELECT id, name, last4 FROM cards WHERE user_id = :uid ORDER BY name");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Query base
$sql = "
    SELECT t.*, c.name AS card_name, c.last4 
    FROM transactions t 
    LEFT JOIN cards c ON c.id = t.card_id 
    WHERE t.user_id = :uid
";
$params = [':uid' => $uid];

// Aplicar filtros
if ($category) {
    $sql .= " AND t.category = :cat";
    $params[':cat'] = $category;
}
if ($card_id) {
    $sql .= " AND t.card_id = :cid";
    $params[':cid'] = $card_id;
}
if ($month) {
    $sql .= " AND DATE_FORMAT(t.created_at, '%Y-%m') = :month";
    $params[':month'] = $month;
}

$sql .= " ORDER BY t.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Total filtrado
$total = array_sum(array_column($transactions, 'amount'));

// Agrupar por dia
$byDay = [];
foreach ($transactions as $t) {
    $day = date('Y-m-d', strtotime($t['created_at']));
    if (!isset($byDay[$day])) {
        $byDay[$day] = [];
    }
    $byDay[$day][] = $t;
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Transações - Freecard</title>
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
    }
    .filter-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .transaction-item {
      background: white;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      border: 1px solid #f0f0f0;
      transition: all 0.2s;
    }
    .transaction-item:hover {
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      transform: translateX(4px);
    }
    .transaction-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
      color: white;
    }
    .transaction-amount {
      font-size: 18px;
      font-weight: 700;
      color: #e74c3c;
    }
    .day-separator {
      font-weight: 700;
      color: #2c3e50;
      margin: 24px 0 16px;
      padding-bottom: 8px;
      border-bottom: 2px solid #e9ecef;
    }
    .category-badge {
      background: #f0f0f0;
      color: #2c3e50;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
    }
    .summary-card {
      background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
      color: white;
      border-radius: 16px;
      padding: 24px;
    }
    .stat-item {
      text-align: center;
      padding: 16px;
    }
    .stat-item h3 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 4px;
    }
    .stat-item p {
      font-size: 13px;
      opacity: 0.9;
      margin: 0;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="assets/logo2.png" alt="Freecard">
      Freecard
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

<div class="container mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><i class="bi bi-receipt"></i> As Minhas Transações</h2>
      <p class="text-muted mb-0">Histórico completo de despesas</p>
    </div>
    <a href="create_transaction.php" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Nova Transação
    </a>
  </div>

  <!-- Resumo -->
  <?php if (!empty($transactions)): ?>
    <div class="summary-card mb-4">
      <div class="row">
        <div class="col-4">
          <div class="stat-item">
            <h3><?=count($transactions)?></h3>
            <p>Transações</p>
          </div>
        </div>
        <div class="col-4 border-start border-end" style="border-color: rgba(255,255,255,0.2) !important;">
          <div class="stat-item">
            <h3>€<?=number_format($total, 2)?></h3>
            <p>Total Gasto</p>
          </div>
        </div>
        <div class="col-4">
          <div class="stat-item">
            <h3>€<?=count($transactions) > 0 ? number_format($total / count($transactions), 2) : '0.00'?></h3>
            <p>Média</p>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="filter-card mb-4">
    <form method="get" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold">
          <i class="bi bi-calendar"></i> Mês
        </label>
        <input type="month" name="month" class="form-control" value="<?=htmlspecialchars($month)?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">
          <i class="bi bi-tag"></i> Categoria
        </label>
        <select name="category" class="form-select">
          <option value="">Todas as categorias</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?=htmlspecialchars($cat)?>" <?=$category === $cat ? 'selected' : ''?>>
              <?=htmlspecialchars($cat)?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">
          <i class="bi bi-credit-card"></i> Cartão
        </label>
        <select name="card_id" class="form-select">
          <option value="">Todos os cartões</option>
          <?php foreach($cards as $c): ?>
            <option value="<?=$c['id']?>" <?=$card_id == $c['id'] ? 'selected' : ''?>>
              <?=htmlspecialchars($c['name'])?> (<?=$c['last4']?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">
            <i class="bi bi-search"></i> Filtrar
          </button>
          <a href="transactions.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Lista de Transações -->
  <?php if (empty($transactions)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="mb-4">
          <i class="bi bi-receipt" style="font-size: 80px; color: #e0e0e0;"></i>
        </div>
        <h4 class="text-muted mb-3">Nenhuma transação encontrada</h4>
        <p class="text-muted mb-4">Altera os filtros ou cria a tua primeira transação</p>
        <a href="create_transaction.php" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Criar Transação
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body p-4">
        <?php foreach($byDay as $day => $dayTransactions): ?>
          <div class="day-separator">
            <?php
              $dayObj = new DateTime($day);
              $today = new DateTime();
              $yesterday = (new DateTime())->modify('-1 day');
              
              if ($dayObj->format('Y-m-d') === $today->format('Y-m-d')) {
                echo 'Hoje';
              } elseif ($dayObj->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                echo 'Ontem';
              } else {
                echo strftime('%A, %d de %B', strtotime($day));
              }
              
              $dayTotal = array_sum(array_column($dayTransactions, 'amount'));
            ?>
            <span class="float-end text-danger">-€<?=number_format($dayTotal, 2)?></span>
          </div>

          <?php foreach($dayTransactions as $t): ?>
            <div class="transaction-item">
              <div class="d-flex align-items-center">
                <div class="transaction-icon me-3">
                  <i class="bi bi-<?=
                    match($t['category']) {
                      'Compras' => 'cart',
                      'Alimentação' => 'cup-straw',
                      'Transporte' => 'bus-front',
                      'Saúde' => 'heart-pulse',
                      'Entretenimento' => 'controller',
                      'Educação' => 'book',
                      'Casa' => 'house-door',
                      default => 'receipt'
                    }
                  ?>"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="fw-semibold"><?=htmlspecialchars($t['description'])?></div>
                  <div class="d-flex gap-2 align-items-center mt-1">
                    <?php if($t['category']): ?>
                      <span class="category-badge"><?=htmlspecialchars($t['category'])?></span>
                    <?php endif; ?>
                    <?php if($t['card_name']): ?>
                      <small class="text-muted">
                        <i class="bi bi-credit-card"></i>
                        <?=htmlspecialchars($t['card_name'])?> (<?=htmlspecialchars($t['last4'])?>)
                      </small>
                    <?php else: ?>
                      <small class="text-muted">
                        <i class="bi bi-cash"></i> Dinheiro
                      </small>
                    <?php endif; ?>
                    <small class="text-muted">
                      <i class="bi bi-clock"></i> <?=date('H:i', strtotime($t['created_at']))?>
                    </small>
                  </div>
                </div>
                <div class="transaction-amount">
                  -€<?=number_format($t['amount'], 2)?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>