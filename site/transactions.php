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
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Transações - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💳 PAP Finanças</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="transactions.php">Transações</a></li>
        <li class="nav-item"><a class="nav-link" href="cards.php">Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>🧾 Histórico de Transações</h2>
    <a href="create_transaction.php" class="btn btn-primary">➕ Nova Transação</a>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-3">
          <label class="form-label small">Mês</label>
          <input type="month" name="month" class="form-control form-control-sm" value="<?=htmlspecialchars($month)?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Categoria</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">Todas as categorias</option>
            <?php foreach($categories as $cat): ?>
              <option value="<?=htmlspecialchars($cat)?>" <?=$category === $cat ? 'selected' : ''?>>
                <?=htmlspecialchars($cat)?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Cartão</label>
          <select name="card_id" class="form-select form-select-sm">
            <option value="">Todos os cartões</option>
            <?php foreach($cards as $c): ?>
              <option value="<?=$c['id']?>" <?=$card_id == $c['id'] ? 'selected' : ''?>>
                <?=htmlspecialchars($c['name'])?> (<?=$c['last4']?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">🔍 Filtrar</button>
          <a href="transactions.php" class="btn btn-outline-secondary btn-sm">✕</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Resumo -->
  <div class="alert alert-info d-flex justify-content-between align-items-center">
    <span><strong><?=count($transactions)?></strong> transações encontradas</span>
    <span>Total: <strong class="text-danger">€<?=number_format($total, 2)?></strong></span>
  </div>

  <!-- Lista de Transações -->
  <?php if (empty($transactions)): ?>
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <h4 class="text-muted mb-3">Nenhuma transação encontrada</h4>
        <p class="text-muted">Altera os filtros ou cria a tua primeira transação</p>
        <a href="create_transaction.php" class="btn btn-primary">➕ Criar Transação</a>
      </div>
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th width="140">Data/Hora</th>
              <th>Descrição</th>
              <th width="120">Categoria</th>
              <th width="180">Cartão</th>
              <th width="100" class="text-end">Valor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($transactions as $t): ?>
              <tr>
                <td>
                  <small class="text-muted">
                    <?=date('d/m/Y', strtotime($t['created_at']))?><br>
                    <?=date('H:i', strtotime($t['created_at']))?>
                  </small>
                </td>
                <td>
                  <strong><?=htmlspecialchars($t['description'])?></strong>
                </td>
                <td>
                  <?php if($t['category']): ?>
                    <span class="badge bg-info"><?=htmlspecialchars($t['category'])?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($t['card_name']): ?>
                    <small>
                      <?=htmlspecialchars($t['card_name'])?><br>
                      <span class="text-muted">•••• <?=htmlspecialchars($t['last4'])?></span>
                    </small>
                  <?php else: ?>
                    <small class="text-muted">Dinheiro</small>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <strong class="text-danger">-€<?=number_format($t['amount'], 2)?></strong>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td colspan="4" class="text-end"><strong>Total:</strong></td>
              <td class="text-end"><strong class="text-danger">-€<?=number_format($total, 2)?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>