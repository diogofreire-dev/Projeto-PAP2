<?php
// site/add_card.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $last4 = trim($_POST['last4'] ?? '');
    $limit = floatval($_POST['limit_amount'] ?? 0);
    $balance = floatval($_POST['balance'] ?? 0);

    // Validações
    if (strlen($name) < 3) {
        $errors[] = 'O nome do cartão deve ter pelo menos 3 caracteres.';
    }
    if (!preg_match('/^\d{4}$/', $last4)) {
        $errors[] = 'Os últimos 4 dígitos devem ser numéricos.';
    }
    if ($limit < 0) {
        $errors[] = 'O limite não pode ser negativo.';
    }
    if ($balance < 0) {
        $errors[] = 'O saldo não pode ser negativo.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO cards (user_id, name, last4, limit_amount, balance, active) 
                VALUES (:uid, :name, :last4, :limit, :balance, 1)
            ");
            $stmt->execute([
                ':uid' => $uid,
                ':name' => $name,
                ':last4' => $last4,
                ':limit' => $limit,
                ':balance' => $balance
            ]);
            $success = true;
            
            // Limpar campos após sucesso
            $name = $last4 = '';
            $limit = $balance = 0;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao adicionar cartão. Tenta novamente.';
        }
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Adicionar Cartão - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">💳 PAP Finanças</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="cards.php">Cartões</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">💳 Adicionar Novo Cartão</h4>
        </div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
              ✅ Cartão adicionado com sucesso!
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <strong>Erros:</strong>
              <ul class="mb-0">
                <?php foreach($errors as $e): ?>
                  <li><?=htmlspecialchars($e)?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Nome do Cartão *</label>
              <input 
                type="text" 
                name="name" 
                class="form-control" 
                placeholder="ex: Visa Principal, Mastercard Secundário"
                value="<?=htmlspecialchars($name ?? '')?>" 
                required
              >
              <small class="text-muted">Dá um nome descritivo ao teu cartão</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Últimos 4 Dígitos *</label>
              <input 
                type="text" 
                name="last4" 
                class="form-control" 
                placeholder="1234"
                maxlength="4"
                pattern="\d{4}"
                value="<?=htmlspecialchars($last4 ?? '')?>" 
                required
              >
              <small class="text-muted">Apenas os últimos 4 números do cartão</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Limite do Cartão (€) *</label>
              <input 
                type="number" 
                name="limit_amount" 
                class="form-control" 
                placeholder="1500.00"
                step="0.01"
                min="0"
                value="<?=htmlspecialchars($limit ?? 0)?>" 
                required
              >
              <small class="text-muted">Limite máximo de crédito disponível</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Saldo Atual/Gasto (€)</label>
              <input 
                type="number" 
                name="balance" 
                class="form-control" 
                placeholder="0.00"
                step="0.01"
                min="0"
                value="<?=htmlspecialchars($balance ?? 0)?>"
              >
              <small class="text-muted">Quanto já gastaste/usaste do limite (opcional)</small>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                ➕ Adicionar Cartão
              </button>
              <a href="dashboard.php" class="btn btn-outline-secondary">
                ← Voltar ao Dashboard
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>