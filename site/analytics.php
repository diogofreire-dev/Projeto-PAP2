<?php
// site/analytics.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
$uid = $_SESSION['user_id'] ?? null;
require_once __DIR__ . '/theme_helper.php';
$currentTheme = getUserTheme($pdo, $uid);


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

// Inicializar array para cada categoria com 12 meses (índice 0-11)
foreach ($categories as $cat) {
    $monthlyData[$cat] = array_fill(0, 12, 0);
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
    $month = intval($row['month']) - 1; // Converter para índice 0-11
    $category = $row['category'];
    $total = floatval($row['total']);
    
    if (isset($monthlyData[$category])) {
        $monthlyData[$category][$month] = $total;
    } else {
        // Se a categoria não existe na lista, adicionar aos "Outros"
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
  <link rel="stylesheet" href="css/theme.css">
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
    
    /* Stats resumo */
    .summary-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .stat-item {
      text-align: center;
      padding: 16px;
    }
    .stat-item h3 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 4px;
      color: #2c3e50;
    }
    .stat-item p {
      font-size: 13px;
      margin: 0;
      color: #7f8c8d;
    }
    .stat-item:not(:last-child) {
      border-right: 1px solid #e9ecef;
    }
    
    /* Card usage styling */
    .card-usage-item {
      padding: 12px;
      background: #f8f9fa;
      border-radius: 8px;
    }
    
    .card-usage-item .progress-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      transition: width 1s ease-out;
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
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><hr class="dropdown-divider"></li>
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
  <div class="summary-card mb-4">
    <div class="row">
      <div class="col-md-3">
        <div class="stat-item">
          <h3 class="text-success">€<?=number_format($totalYear, 2)?></h3>
          <p>Total Gasto</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-item">
          <h3 style="color: #3498db;"><?=$stats['total_transactions']?></h3>
          <p>Transações</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-item">
          <h3 style="color: #f39c12;">€<?=number_format($stats['avg_amount'], 2)?></h3>
          <p>Média por Transação</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-item">
          <h3 class="text-danger">€<?=number_format($stats['max_amount'], 2)?></h3>
          <p>Maior Gasto</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos de Cartões e Insights -->
  <div class="row mb-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-4">
            <i class="bi bi-pie-chart"></i> Distribuição de Gastos por Cartão
          </h5>
          <?php
          // Buscar gastos por cartão no ano selecionado
          $stmtCardSpending = $pdo->prepare("
            SELECT c.name, c.last4, COALESCE(SUM(t.amount), 0) as total
            FROM cards c
            LEFT JOIN transactions t ON t.card_id = c.id 
              AND YEAR(t.created_at) = :year
              " . ($card_id ? "AND c.id = :cid" : "") . "
            WHERE c.user_id = :uid
            GROUP BY c.id
            HAVING total > 0
            ORDER BY total DESC
          ");
          $cardSpendParams = [':uid' => $uid, ':year' => $year];
          if ($card_id) {
            $cardSpendParams[':cid'] = $card_id;
          }
          $stmtCardSpending->execute($cardSpendParams);
          $cardSpending = $stmtCardSpending->fetchAll();
          
          if (empty($cardSpending)): ?>
            <div class="text-center py-5">
              <i class="bi bi-credit-card-2-front" style="font-size: 64px; color: #e0e0e0;"></i>
              <h6 class="text-muted mt-3 mb-2">Sem transações por cartão</h6>
              <p class="text-muted small mb-0">
                Nenhum dos teus cartões tem transações registadas neste período.
                <br>Começa por <a href="create_transaction.php">criar uma transação</a> e associá-la a um cartão.
              </p>
            </div>
          <?php else: ?>
            <div class="chart-container" style="height: 300px;">
              <canvas id="cardPieChart"></canvas>
            </div>
            <div class="mt-3 text-center">
              <small class="text-muted">Mostra a distribuição percentual de gastos entre os teus cartões</small>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-lg-6">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-4">
            <i class="bi bi-trophy"></i> Top 5 Maiores Gastos
          </h5>
          <div style="max-height: 350px; overflow-y: auto;">
            <?php
            // Buscar as 5 maiores transações do período
            $stmtTopTransactions = $pdo->prepare("
              SELECT t.*, c.name as card_name, c.last4
              FROM transactions t
              LEFT JOIN cards c ON c.id = t.card_id
              WHERE t.user_id = :uid 
              AND YEAR(t.created_at) = :year
              " . ($card_id ? "AND t.card_id = :cid" : "") . "
              ORDER BY t.amount DESC
              LIMIT 5
            ");
            $topParams = [':uid' => $uid, ':year' => $year];
            if ($card_id) {
              $topParams[':cid'] = $card_id;
            }
            $stmtTopTransactions->execute($topParams);
            $topTransactions = $stmtTopTransactions->fetchAll();
            
            if (empty($topTransactions)): ?>
              <div class="text-center py-4">
                <i class="bi bi-receipt" style="font-size: 48px; color: #e0e0e0;"></i>
                <p class="text-muted mt-3 mb-0">Sem transações registadas</p>
              </div>
            <?php else:
              $position = 1;
              foreach($topTransactions as $topT):
                $medalColor = match($position) {
                  1 => '#FFD700',
                  2 => '#C0C0C0',
                  3 => '#CD7F32',
                  default => '#95a5a6'
                };
            ?>
              <div class="card-usage-item mb-3" style="border-left: 4px solid <?=$medalColor?>;">
                <div class="d-flex align-items-center gap-3">
                  <div class="flex-shrink-0 text-center" style="width: 40px;">
                    <div style="font-size: 24px; font-weight: 800; color: <?=$medalColor?>;">
                      #<?=$position?>
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold mb-1"><?=htmlspecialchars($topT['description'])?></div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                      <?php if($topT['category']): ?>
                        <small class="badge bg-secondary"><?=htmlspecialchars($topT['category'])?></small>
                      <?php endif; ?>
                      <?php if($topT['card_name']): ?>
                        <small class="text-muted">
                          <i class="bi bi-credit-card"></i> <?=htmlspecialchars($topT['card_name'])?> (<?=htmlspecialchars($topT['last4'])?>)
                        </small>
                      <?php endif; ?>
                      <small class="text-muted">
                        <i class="bi bi-calendar"></i> <?=date('d/m/Y', strtotime($topT['created_at']))?>
                      </small>
                    </div>
                  </div>
                  <div class="flex-shrink-0 text-end">
                    <div class="fw-bold text-danger" style="font-size: 18px;">
                      €<?=number_format($topT['amount'], 2)?>
                    </div>
                    <?php if ($totalYear > 0): ?>
                      <small class="text-muted">
                        <?=round(($topT['amount'] / $totalYear) * 100, 1)?>% do total
                      </small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php 
                $position++;
              endforeach; 
            endif; ?>
          </div>
          <?php if (!empty($topTransactions)): ?>
            <div class="text-center mt-3">
              <small class="text-muted">
                <i class="bi bi-info-circle"></i> Estas são as tuas maiores despesas em <?=$year?>
              </small>
            </div>
          <?php endif; ?>
        </div>
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
// Dados do gráfico de linhas
const monthlyData = <?=json_encode($monthlyData)?>;
const categoryColors = <?=json_encode($categoryColors)?>;
const months = <?=json_encode($months)?>;

// Configurar Chart.js para gráfico de linhas
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

const lineChart = new Chart(ctx, {
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
        filter: function(tooltipItem) {
          return tooltipItem.parsed.y > 0;
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

<?php
$cardLabels = array_map(function($c) {
  return $c['name'] . ' (' . $c['last4'] . ')';
}, $cardSpending);
$cardData = array_map(function($c) {
  return floatval($c['total']);
}, $cardSpending);
?>

// Gráfico de Pizza - Distribuição por Cartão
<?php if (!empty($cardSpending)): ?>
const cardPieCtx = document.getElementById('cardPieChart').getContext('2d');

const cardPieData = {
  labels: <?=json_encode($cardLabels)?>,
  datasets: [{
    data: <?=json_encode($cardData)?>,
    backgroundColor: [
      '#3498db',
      '#e74c3c', 
      '#f39c12',
      '#1abc9c',
      '#9b59b6',
      '#34495e',
      '#e67e22',
      '#95a5a6'
    ],
    borderWidth: 2,
    borderColor: '#fff'
  }]
};

const cardPieChart = new Chart(cardPieCtx, {
  type: 'doughnut',
  data: cardPieData,
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 15,
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        callbacks: {
          label: function(context) {
            const label = context.label || '';
            const value = context.parsed || 0;
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return label + ': €' + value.toFixed(2) + ' (' + percentage + '%)';
          }
        }
      }
    }
  }
});
<?php endif; ?>

// Interatividade da legenda do gráfico de linhas
document.querySelectorAll('.legend-item').forEach((item, index) => {
  item.addEventListener('click', function() {
    const meta = lineChart.getDatasetMeta(index);
    meta.hidden = !meta.hidden;
    lineChart.update();
    this.style.opacity = meta.hidden ? '0.3' : '1';
  });
});

// Animar barras de categoria
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