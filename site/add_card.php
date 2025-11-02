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
    if ($balance > $limit) {
        $errors[] = 'O saldo não pode ser superior ao limite.';
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
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Adicionar Cartão - FreeCard</title>
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
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    }
    .btn-outline-primary { 
      color: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .btn-outline-primary:hover { 
      background: var(--primary-green); 
      border-color: var(--primary-green); 
    }
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .card-header {
      background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
      border-radius: 16px 16px 0 0 !important;
      padding: 24px;
    }
    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    .form-control, .form-select {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 16px;
      transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-green);
      box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }
    .card-preview {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px;
      padding: 24px;
      color: white;
      min-height: 180px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .card-preview .card-number {
      font-size: 24px;
      letter-spacing: 4px;
      font-weight: 600;
    }
    .card-preview .card-name {
      font-size: 16px;
      text-transform: uppercase;
      font-weight: 600;
    }
    .progress-custom {
      height: 8px;
      border-radius: 10px;
      background: #e9ecef;
    }
    .progress-bar-custom {
      background: var(--primary-green);
      border-radius: 10px;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="assets/logo.png" alt="Freecard">
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
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5 mb-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="mb-4">
        <a href="dashboard.php" class="text-decoration-none text-muted">
          <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
        </a>
      </div>

      <div class="row g-4">
        <div class="col-lg-5">
          <div class="card">
            <div class="card-body p-4">
              <h5 class="mb-4"><i class="bi bi-eye"></i> Pré-visualização</h5>
              <div class="card-preview">
                <div>
                  <div class="mb-3">
                    <i class="bi bi-credit-card" style="font-size: 32px;"></i>
                  </div>
                  <div class="card-number" id="preview-number">•••• •••• •••• ••••</div>
                </div>
                <div>
                  <div class="card-name" id="preview-name">NOME DO CARTÃO</div>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small>Limite: <span id="preview-limit">€0.00</span></small>
                    <small>Saldo: <span id="preview-balance">€0.00</span></small>
                  </div>
                </div>
              </div>
              
              <div class="mt-4">
                <h6 class="mb-3">Utilização do Limite</h6>
                <div class="progress-custom">
                  <div class="progress-bar-custom" id="usage-bar" style="width: 0%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                  <small class="text-muted">0%</small>
                  <small class="text-muted" id="usage-percent">0% usado</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card">
            <div class="card-header text-white">
              <h4 class="mb-0"><i class="bi bi-credit-card-2-front"></i> Adicionar Novo Cartão</h4>
            </div>
            <div class="card-body p-4">
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                  <i class="bi bi-check-circle"></i> Cartão adicionado com sucesso!
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                  <strong><i class="bi bi-exclamation-circle"></i> Erros:</strong>
                  <ul class="mb-0 mt-2">
                    <?php foreach($errors as $e): ?>
                      <li><?=htmlspecialchars($e)?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <form method="post" id="cardForm">
                <div class="mb-3">
                  <label class="form-label">Nome do Cartão *</label>
                  <input 
                    type="text" 
                    name="name" 
                    id="cardName"
                    class="form-control" 
                    placeholder="ex: Visa Principal, Mastercard Viagens"
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
                    id="cardLast4"
                    class="form-control" 
                    placeholder="1234"
                    maxlength="4"
                    pattern="\d{4}"
                    value="<?=htmlspecialchars($last4 ?? '')?>" 
                    required
                  >
                  <small class="text-muted">Apenas os últimos 4 números do cartão</small>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Limite do Cartão (€) *</label>
                    <input 
                      type="number" 
                      name="limit_amount" 
                      id="cardLimit"
                      class="form-control" 
                      placeholder="1500.00"
                      step="0.01"
                      min="0"
                      value="<?=htmlspecialchars($limit ?? 0)?>" 
                      required
                    >
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Saldo Atual (€)</label>
                    <input 
                      type="number" 
                      name="balance" 
                      id="cardBalance"
                      class="form-control" 
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                      value="<?=htmlspecialchars($balance ?? 0)?>"
                    >
                  </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle"></i> Adicionar Cartão
                  </button>
                  <a href="dashboard.php" class="btn btn-outline-secondary">
                    Cancelar
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live preview
document.getElementById('cardName').addEventListener('input', function(e) {
  const name = e.target.value || 'NOME DO CARTÃO';
  document.getElementById('preview-name').textContent = name.toUpperCase();
});

document.getElementById('cardLast4').addEventListener('input', function(e) {
  const last4 = e.target.value || '••••';
  document.getElementById('preview-number').textContent = `•••• •••• •••• ${last4}`;
});

document.getElementById('cardLimit').addEventListener('input', updateUsage);
document.getElementById('cardBalance').addEventListener('input', updateUsage);

function updateUsage() {
  const limit = parseFloat(document.getElementById('cardLimit').value) || 0;
  const balance = parseFloat(document.getElementById('cardBalance').value) || 0;
  
  document.getElementById('preview-limit').textContent = `€${limit.toFixed(2)}`;
  document.getElementById('preview-balance').textContent = `€${balance.toFixed(2)}`;
  
  if (limit > 0) {
    const percent = Math.min((balance / limit) * 100, 100);
    document.getElementById('usage-bar').style.width = percent + '%';
    document.getElementById('usage-percent').textContent = Math.round(percent) + '% usado';
    
    // Mudar cor baseado no uso
    const bar = document.getElementById('usage-bar');
    if (percent >= 80) {
      bar.style.background = '#e74c3c';
    } else if (percent >= 60) {
      bar.style.background = '#f39c12';
    } else {
      bar.style.background = '#2ecc71';
    }
  }
}
</script>
</body>
</html>