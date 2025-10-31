<?php
// site/cards.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$message = '';

// Ações: ativar/desativar/eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $cardId = intval($_POST['card_id'] ?? 0);
    $action = $_POST['action'];

    try {
        switch($action) {
            case 'toggle':
                $stmt = $pdo->prepare("UPDATE cards SET active = NOT active WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $cardId, ':uid' => $uid]);
                $message = 'Estado do cartão alterado!';
                break;
            
            case 'delete':
                // Nota: as transações ficam com card_id = NULL (ON DELETE SET NULL)
                $stmt = $pdo->prepare("DELETE FROM cards WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $cardId, ':uid' => $uid]);
                $message = 'Cartão eliminado com sucesso!';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Erro ao executar a ação.';
    }
}

// Buscar todos os cartões
$stmt = $pdo->prepare("
    SELECT c.*, 
           COALESCE(SUM(t.amount), 0) as total_spent,
           COUNT(t.id) as transaction_count
    FROM cards c
    LEFT JOIN transactions t ON t.card_id = c.id
    WHERE c.user_id = :uid
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerir Cartões - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💳 PAP Finanças</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transações</a></li>
        <li class="nav-item"><a class="nav-link active" href="cards.php">Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>💳 Os Teus Cartões</h2>
    <a href="add_card.php" class="btn btn-primary">➕ Adicionar Cartão</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show">
      <?=htmlspecialchars($message)?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($cards)): ?>
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <h4 class="text-muted mb-3">Ainda não tens cartões registados</h4>
        <p class="text-muted">Adiciona o teu primeiro cartão para começar a gerir as tuas finanças</p>
        <a href="add_card.php" class="btn btn-primary">➕ Adicionar Primeiro Cartão</a>
      </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach($cards as $c): ?>
        <?php 
          $percentage = $c['limit_amount'] > 0 ? ($c['balance'] / $c['limit_amount']) * 100 : 0;
          $progressColor = $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'success');
        ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="card-title mb-1"><?=htmlspecialchars($c['name'])?></h5>
                  <small class="text-muted">•••• <?=htmlspecialchars($c['last4'])?></small>
                </div>
                <span class="badge bg-<?=$c['active'] ? 'success' : 'secondary'?>">
                  <?=$c['active'] ? '✓ Ativo' : '✗ Inativo'?>
                </span>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted">Utilização</small>
                  <small class="fw-bold"><?=round($percentage)?>%</small>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar bg-<?=$progressColor?>" style="width: <?=min($percentage, 100)?>%"></div>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted small">Gasto: €<?=number_format($c['balance'],2)?></span>
                  <span class="text-muted small">Limite: €<?=number_format($c['limit_amount'],2)?></span>
                </div>
              </div>

              <div class="mb-3 p-2 bg-light rounded">
                <div class="d-flex justify-content-between">
                  <span class="small">Transações:</span>
                  <strong><?=$c['transaction_count']?></strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="small">Total gasto:</span>
                  <strong>€<?=number_format($c['total_spent'],2)?></strong>
                </div>
              </div>

              <div class="d-flex gap-2">
                <form method="post" class="flex-fill">
                  <input type="hidden" name="card_id" value="<?=$c['id']?>">
                  <input type="hidden" name="action" value="toggle">
                  <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <?=$c['active'] ? '⏸️ Desativar' : '▶️ Ativar'?>
                  </button>
                </form>
                <form method="post" onsubmit="return confirm('Tens a certeza que queres eliminar este cartão?');">
                  <input type="hidden" name="card_id" value="<?=$c['id']?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    🗑️
                  </button>
                </form>
              </div>
            </div>
            <div class="card-footer bg-white small text-muted">
              Criado: <?=date('d/m/Y', strtotime($c['created_at']))?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>