<?php
// site/dashboard.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    header('Location: login.php');
    exit;
}

// total gasto no mês
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total_month FROM transactions WHERE user_id = :uid AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$stmt->execute([':uid' => $uid]);
$totalMonth = $stmt->fetchColumn();

// transacções últimos 30 dias
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = :uid AND created_at >= NOW() - INTERVAL 30 DAY");
$stmt->execute([':uid' => $uid]);
$count30 = $stmt->fetchColumn();

// últimos registos
$stmt = $pdo->prepare("SELECT t.*, c.name AS card_name, c.last4 FROM transactions t LEFT JOIN cards c ON c.id = t.card_id WHERE t.user_id = :uid ORDER BY t.created_at DESC LIMIT 8");
$stmt->execute([':uid' => $uid]);
$recent = $stmt->fetchAll();

// cartões
$stmt = $pdo->prepare("SELECT id, name, last4, limit_amount, balance, active FROM cards WHERE user_id = :uid");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// alertas simples: cartão com >80% do limite
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
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="index.php">PAP</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#"><?=htmlspecialchars($_SESSION['username'])?></a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="row gy-4">
    <div class="col-12 col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Resumo rápido</h5>
          <p class="mb-1"><strong>Gasto (mês):</strong> €<?=number_format($totalMonth,2)?></p>
          <p class="mb-1"><strong>Transacções (30d):</strong> <?=intval($count30)?></p>
          <p class="mb-1"><strong>Cartões:</strong> <?=count($cards)?></p>
          <hr>
          <a href="create_transaction.php" class="btn btn-primary btn-sm">Nova transacção</a>
          <a href="add_card.php" class="btn btn-outline-primary btn-sm">Adicionar cartão</a>
        </div>
      </div>

      <?php if (!empty($alerts)): ?>
        <div class="mt-3">
          <div class="card border-warning shadow-sm">
            <div class="card-body">
              <h6 class="card-title text-warning">Alertas</h6>
              <ul class="mb-0">
                <?php foreach($alerts as $a): ?>
                  <li><?=htmlspecialchars($a)?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="mt-3">
        <div class="card shadow-sm">
          <div class="card-body">
            <h6 class="card-title">Os teus cartões</h6>
            <?php if (empty($cards)): ?>
              <p class="text-muted small">Não tens cartões adicionados.</p>
            <?php else: ?>
              <?php foreach($cards as $c): ?>
                <div class="mb-2">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong><?=htmlspecialchars($c['name'])?></strong><br>
                      <small class="text-muted">•••• <?=htmlspecialchars($c['last4'])?></small>
                    </div>
                    <div class="text-end">
                      <div>Limite: €<?=number_format($c['limit_amount'],2)?></div>
                      <div>Gasto: €<?=number_format($c['balance'],2)?></div>
                    </div>
                  </div>
                </div>
                <hr class="my-2">
              <?php endforeach; ?>
            <?php endif; ?>
            <a href="cards.php" class="btn btn-sm btn-outline-secondary">Gerir cartões</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Últimas transacções</h5>
          <?php if (empty($recent)): ?>
            <p class="text-muted">Ainda não tens transacções.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Data</th><th>Descrição</th><th>Cartão</th><th class="text-end">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($recent as $r): ?>
                    <tr>
                      <td><?=htmlspecialchars($r['created_at'])?></td>
                      <td><?=htmlspecialchars($r['description'] ?: '-')?></td>
                      <td><?=htmlspecialchars($r['card_name'] ? $r['card_name']." (".$r['last4'].")" : '-')?></td>
                      <td class="text-end">€<?=number_format($r['amount'],2)?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <a href="transactions.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
