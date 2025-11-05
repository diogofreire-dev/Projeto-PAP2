<?php
// site/analytics.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;

// Filtros
$year = !empty($_GET['year']) ? intval($_GET['year']) : date('Y');
$card_id = !empty($_GET['card_id']) ? intval($_GET['card_id']) : null;

// Buscar anos disponíveis
$stmt = $pdo->prepare("
    SELECT DISTINCT YEAR(created_at) as year 
    FROM transactions 
    WHERE user_id = :uid 
    ORDER BY year DESC
");
$stmt->execute([':uid' => $uid]);
$availableYears = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Buscar cartões
$stmt = $pdo->prepare("SELECT id, name, last4 FROM cards WHERE user_id = :uid ORDER BY name");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Dados por mês (para o gráfico de linhas)
$monthlyData = [];
$categories = ['Compras', 'Alimentação', 'Transporte', 'Saúde', 'Entretenimento', 'Educação', 'Casa', 'Outros'];

foreach ($categories as $cat) {
    $monthlyData[$cat] = array_fill(1, 12, 0);
}

$sql = "
    SELECT 
        MONTH(created_at) as month,
        COALESCE(category, 'Outros') as category,
        SUM(amount) as total
    FROM transactions 
    WHERE user_id = :uid 
    AND YEAR(created_at) = :year
";
$params = [':uid' => $uid, ':year' => $year];

if ($card_id) {
    $sql .= " AND card_id = :cid";
    $params[':cid'] = $card_id;
}

$sql .= " GROUP BY MONTH(created_at), category";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

foreach ($results as $row) {
    $month = intval($row['month']);
    $category = $row['category'];
    $total = floatval($row['total']);
    
    if (isset($monthlyData[$category])) {
        $monthlyData[$category][$month] = $total;
    } else {
        $monthlyData['Outros'][$month] += $total;
    }
}

// Total por categoria (para o gráfico de barras)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(category, 'Outros') as category,
        SUM(amount) as total,
        COUNT(*) as count
    FROM transactions 
    WHERE user_id = :uid 
    AND YEAR(created_at) = :year
    " . ($card_id ? "AND card_id = :cid" : "") . "
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute($params);
$categoryTotals = $stmt->fetchAll();

// Total do ano
$totalYear = array_sum(array_column($categoryTotals, 'total'));

// Estatísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        AVG(amount) as avg_amount,
        MAX(amount) as max_amount,
        MIN(amount) as min_amount
    FROM transactions 
    WHERE user_id = :uid 
    AND YEAR(created_at) = :year
    " . ($card_id ? "AND card_id = :cid" : "")
);
$stmt->execute($params);
$stats = $stmt->fetch();

// Cores para categorias
$categoryColors = [
    'Compras' => '#3498db',
    'Alimentação' => '#e74c3c',
    'Transporte' => '#f39c12',
    'Saúde' => '#1abc9c',
    'Entretenimento' => '#9b59b6',
    'Educação' => '#34495e',
    'Casa' => '#e67e22',
    'Outros' => '#95a5a6'
];

$months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Análise de Gastos - FreeCard</title>
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
    
    /* Gráfico de Linhas */
    .chart-container {
      position: relative;
      height: 400px;
      padding: 20px;
      background: white;
      border-radius: 16px;
    }
    
    .line-chart {
      position: relative;
      height: 100%;
      border-left: 2px solid #ddd;
      border-bottom: 2px solid #ddd;
    }
    
    .chart-grid {
      position: absolute;
      width: 100%;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .grid-line {
      width: 100%;
      height: 1px;
      background: #f0f0f0;
      position: relative;
    }
    
    .grid-label {
      position: absolute;
      left: -40px;
      top: -8px;
      font-size: 12px;
      color: #999;
    }
    
    .chart-canvas {
      position: absolute;
      width: calc(100% - 40px);
      height: calc(100% - 40px);
      left: 40px;
      top: 20px;
    }
    
    .month-labels {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
      padding-left: 40px;
    }
    
    .month-label {
      font-size: 12px;
      color: #666;
      font-weight: 600;
    }
    
    .legend {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 20px;
      justify-content: center;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    
    .legend-item:hover {
      opacity: 0.7;
    }
    
    .legend-color {
      width: 20px;
      height: 3px;
      border-radius: 2px;
    }
    
    /* Gráfico de Barras Horizontal */
    .bar-chart {
      padding: 20px 0;
    }
    
    .bar-item {
      margin-bottom: 20px;
    }
    
    .bar-label {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }
    
    .bar-container {
      background: #f0f0f0;
      border-radius: 10px;
      height: 32px;
      position: relative;
      overflow: hidden;
    }
    
    .bar-fill {
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
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      border-left: 4px solid;
    }
    
    .stat-card h3 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 4px;
    }
    
    .stat-card p {
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
        <li class="nav-item"><a class="nav-link" href="cards.php"><i class="bi bi-wallet2"></i> Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php"><i class="bi bi-receipt"></i> Transações</a></li>
        <li class="nav-item"><a class="nav-link active" href="analytics.php"><i class="bi bi-graph-up"></i> Análise</a></li>
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
      <h2><i class="bi bi-graph-up"></i> Análise de Gastos</h2>
      <p class="text-muted mb-0">Visualiza os teus padrões de gastos ao longo do tempo</p>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">
            <i class="bi bi-calendar"></i> Ano
          </label>
          <select name="year" class="form-select">
            <?php if (empty($availableYears)): ?>
              <option value="<?=date('Y')?>"><?=date('Y')?></option>
            <?php else: ?>
              <?php foreach($availableYears as $y): ?>
                <option value="<?=$y?>" <?=$year == $y ? 'selected' : ''?>><?=$y?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-4">
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
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filtrar
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php if (empty($categoryTotals)): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="mb-4">
          <i class="bi bi-graph-up" style="font-size: 80px; color: #e0e0e0;"></i>
        </div>
        <h4 class="text-muted mb-3">Sem dados para análise</h4>
        <p class="text-muted mb-4">Ainda não tens transações registadas para este período</p>
        <a href="create_transaction.php" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Criar Transação
        </a>
      </div>
    </div>
  <?php else: ?>

  <!-- Estatísticas -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card" style="border-left-color: var(--primary-green);">
        <h3 class="text-success">€<?=number_format($totalYear, 2)?></h3>
        <p>Total Gasto</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="border-left-color: #3498db;">
        <h3 class="text-primary"><?=$stats['total_transactions']?></h3>
        <p>Transações</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="border-left-color: #f39c12;">
        <h3 class="text-warning">€<?=number_format($stats['avg_amount'], 2)?></h3>
        <p>Média por Transação</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="border-left-color: #e74c3c;">
        <h3 class="text-danger">€<?=number_format($stats['max_amount'], 2)?></h3>
        <p>Maior Gasto</p>
      </div>
    </div>
  </div>

  <!-- Gráfico de Linhas -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title mb-4">
        <i class="bi bi-graph-up"></i> Evolução Mensal por Categoria (<?=$year?>)
      </h5>
      
      <div class="chart-container">
        <canvas id="lineChart" width="800" height="400"></canvas>
        
        <div class="month-labels">
          <?php foreach($months as $m): ?>
            <div class="month-label"><?=$m?></div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div class="legend" id="legend">
        <?php foreach($categories as $cat): ?>
          <div class="legend-item" data-category="<?=$cat?>">
            <div class="legend-color" style="background: <?=$categoryColors[$cat]?>;"></div>
            <span><?=$cat?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Gráfico de Barras -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-4">
        <i class="bi bi-bar-chart"></i> Total por Categoria (<?=$year?>)
      </h5>
      
      <div class="bar-chart">
        <?php 
        $maxTotal = !empty($categoryTotals) ? max(array_column($categoryTotals, 'total')) : 1;
        foreach($categoryTotals as $cat): 
          $percentage = ($cat['total'] / $maxTotal) * 100;
          $color = $categoryColors[$cat['category']] ?? '#95a5a6';
        ?>
        <div class="bar-item">
          <div class="bar-label">
            <span><strong><?=htmlspecialchars($cat['category'])?></strong> <small class="text-muted">(<?=$cat['count']?> transações)</small></span>
            <span class="text-danger fw-bold">€<?=number_format($cat['total'], 2)?></span>
          </div>
          <div class="bar-container">
            <div class="bar-fill" 
                 style="background: <?=$color?>; width: 0%;"
                 data-width="<?=$percentage?>%">
              <?=round(($cat['total'] / $totalYear) * 100)?>%
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Dados do gráfico
const monthlyData = <?=json_encode($monthlyData)?>;
const categoryColors = <?=json_encode($categoryColors)?>;
const months = <?=json_encode($months)?>;

// Configurar Chart.js
const ctx = document.getElementById('lineChart').getContext('2d');

const datasets = Object.keys(monthlyData).map(category => ({
  label: category,
  data: Object.values(monthlyData[category]),
  borderColor: categoryColors[category],
  backgroundColor: categoryColors[category] + '20',
  borderWidth: 3,
  tension: 0.4,
  pointRadius: 5,
  pointHoverRadius: 7,
  pointBackgroundColor: categoryColors[category],
  pointBorderColor: '#fff',
  pointBorderWidth: 2
}));

const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: months,
    datasets: datasets
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        titleFont: {
          size: 14,
          weight: 'bold'
        },
        bodyFont: {
          size: 13
        },
        callbacks: {
          label: function(context) {
            return context.dataset.label + ': €' + context.parsed.y.toFixed(2);
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: '#f0f0f0'
        },
        ticks: {
          callback: function(value) {
            return '€' + value;
          }
        }
      },
      x: {
        grid: {
          display: false
        }
      }
    }
  }
});

// Interatividade da legenda
document.querySelectorAll('.legend-item').forEach((item, index) => {
  item.addEventListener('click', function() {
    const meta = chart.getDatasetMeta(index);
    meta.hidden = !meta.hidden;
    chart.update();
    this.style.opacity = meta.hidden ? '0.3' : '1';
  });
});

// Animar barras
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(() => {
    document.querySelectorAll('.bar-fill').forEach(bar => {
      bar.style.width = bar.dataset.width;
    });
  }, 100);
});
</script>
</body>
</html>