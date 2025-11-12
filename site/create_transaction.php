<?php
// site/create_transaction.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$errors = [];
$success = false;

// Buscar cartões do utilizador
$stmt = $pdo->prepare("SELECT id, name, last4, limit_amount, balance FROM cards WHERE user_id = :uid AND active = 1 ORDER BY name");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Categorias comuns
$categories = [
    ['icon' => 'cart', 'name' => 'Compras'],
    ['icon' => 'cup-straw', 'name' => 'Alimentação'],
    ['icon' => 'bus-front', 'name' => 'Transporte'],
    ['icon' => 'heart-pulse', 'name' => 'Saúde'],
    ['icon' => 'controller', 'name' => 'Entretenimento'],
    ['icon' => 'book', 'name' => 'Educação'],
    ['icon' => 'house-door', 'name' => 'Casa'],
    ['icon' => 'three-dots', 'name' => 'Outros']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $card_id = !empty($_POST['card_id']) ? intval($_POST['card_id']) : null;

    // Validações
    if ($amount <= 0) {
        $errors[] = 'O valor deve ser maior que zero.';
    }
    if (strlen($description) < 3) {
        $errors[] = 'A descrição deve ter pelo menos 3 caracteres.';
    }

    // Verificar se o cartão tem limite disponível
    if ($card_id) {
        $stmt = $pdo->prepare("SELECT limit_amount, balance FROM cards WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $card_id, ':uid' => $uid]);
        $card = $stmt->fetch();
        
        if ($card && $card['limit_amount'] > 0) {
            $newBalance = $card['balance'] + $amount;
            if ($newBalance > $card['limit_amount']) {
                $errors[] = 'Esta transação excede o limite disponível do cartão.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Inserir transação
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, card_id, amount, description, category, created_at) 
                VALUES (:uid, :cid, :amt, :desc, :cat, NOW())
            ");
            $stmt->execute([
                ':uid' => $uid,
                ':cid' => $card_id,
                ':amt' => $amount,
                ':desc' => $description,
                ':cat' => $category ?: null
            ]);

            // Se tiver cartão associado, atualizar o saldo
            if ($card_id) {
                $stmt = $pdo->prepare("
                    UPDATE cards 
                    SET balance = balance + :amt 
                    WHERE id = :cid AND user_id = :uid
                ");
                $stmt->execute([':amt' => $amount, ':cid' => $card_id, ':uid' => $uid]);
            }

            $pdo->commit();
            $success = true;
            
            // Limpar campos
            $amount = 0;
            $description = $category = '';
            $card_id = null;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Erro ao criar transação. Tenta novamente.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nova Transação - FreeCard</title>
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
    .amount-input {
      font-size: 32px;
      font-weight: 700;
      text-align: center;
      border: 3px solid #e9ecef;
    }
    .amount-input:focus {
      border-color: var(--primary-green);
    }
    .category-option {
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 16px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: white;
    }
    .category-option:hover {
      border-color: var(--primary-green);
      transform: translateY(-2px);
    }
    .category-option input[type="radio"] {
      display: none;
    }
    .category-option input[type="radio"]:checked + .category-content {
      color: var(--primary-green);
    }
    .category-option input[type="radio"]:checked ~ .category-option {
      border-color: var(--primary-green);
      background: rgba(46, 204, 113, 0.05);
    }
    .category-icon {
      font-size: 32px;
      margin-bottom: 8px;
    }
    .summary-box {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px;
      padding: 24px;
      color: white;
    }
    .summary-item {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .summary-item:last-child {
      border-bottom: none;
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
        <li class="nav-item"><a class="nav-link active" href="transactions.php"><i class="bi bi-receipt"></i> Transações</a></li>
        <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-graph-up"></i> Análise</a></li>
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
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header text-white">
              <h4 class="mb-0"><i class="bi bi-receipt"></i> Nova Transação</h4>
            </div>
            <div class="card-body p-4">
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                  <i class="bi bi-check-circle"></i> Transação registada com sucesso!
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

              <?php if (empty($cards)): ?>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle"></i> Ainda não tens cartões registados. 
                  <a href="add_card.php" class="alert-link">Adiciona um cartão primeiro</a> 
                  ou continua sem cartão associado.
                </div>
              <?php endif; ?>

              <form method="post">
                <div class="mb-4">
                  <label class="form-label text-center w-100">Valor da Transação (€) *</label>
                  <input 
                    type="number" 
                    name="amount" 
                    class="form-control amount-input" 
                    placeholder="0.00"
                    step="0.01"
                    min="0.01"
                    value="<?=htmlspecialchars($amount ?? '')?>" 
                    required
                    autofocus
                  >
                </div>

                <div class="mb-4">
                  <label class="form-label">Descrição *</label>
                  <input 
                    type="text" 
                    name="description" 
                    class="form-control" 
                    placeholder="ex: Café e snack, Supermercado, Gasolina"
                    value="<?=htmlspecialchars($description ?? '')?>" 
                    required
                  >
                </div>

                <div class="mb-4">
                  <label class="form-label mb-3">Categoria</label>
                  <div class="row g-2">
                    <?php foreach($categories as $cat): ?>
                      <div class="col-6 col-md-3">
                        <label class="category-option">
                          <input type="radio" name="category" value="<?=$cat['name']?>" <?=($category ?? '') === $cat['name'] ? 'checked' : ''?>>
                          <div class="category-content">
                            <div class="category-icon">
                              <i class="bi bi-<?=$cat['icon']?>"></i>
                            </div>
                            <small class="fw-semibold"><?=$cat['name']?></small>
                          </div>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label">Cartão Associado</label>
                  <select name="card_id" class="form-select">
                    <option value="">Nenhum / Dinheiro</option>
                    <?php foreach($cards as $c): ?>
                      <option value="<?=$c['id']?>" <?=($card_id ?? '') == $c['id'] ? 'selected' : ''?>>
                        <?=htmlspecialchars($c['name'])?> (•••• <?=htmlspecialchars($c['last4'])?>)
                        - Disponível: €<?=number_format($c['limit_amount'] - $c['balance'], 2)?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted">Deixa vazio se foi pago em dinheiro</small>
                </div>

                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Registar Transação
                  </button>
                  <a href="dashboard.php" class="btn btn-outline-secondary">
                    Cancelar
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card">
            <div class="card-body p-4">
              <h5 class="mb-4"><i class="bi bi-info-circle"></i> Dicas</h5>
              
              <div class="mb-3">
                <h6><i class="bi bi-lightbulb text-warning"></i> Organiza melhor</h6>
                <p class="text-muted small mb-0">Usa categorias para analisar onde gastas mais dinheiro.</p>
              </div>
              
              <div class="mb-3">
                <h6><i class="bi bi-credit-card text-primary"></i> Associa ao cartão</h6>
                <p class="text-muted small mb-0">Liga a transação ao cartão para acompanhar o saldo automaticamente.</p>
              </div>
              
              <div class="mb-3">
                <h6><i class="bi bi-pencil text-success"></i> Sê específico</h6>
                <p class="text-muted small mb-0">Descrições detalhadas facilitam a gestão futura.</p>
              </div>

              <?php if (!empty($cards)): ?>
                <hr class="my-4">
                <h6 class="mb-3">Os Teus Cartões</h6>
                <?php foreach($cards as $c): ?>
                  <?php 
                    $available = $c['limit_amount'] - $c['balance'];
                    $percent = $c['limit_amount'] > 0 ? ($c['balance'] / $c['limit_amount']) * 100 : 0;
                  ?>
                  <div class="mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between mb-2">
                      <strong class="small"><?=htmlspecialchars($c['name'])?></strong>
                      <small class="text-muted">•••• <?=htmlspecialchars($c['last4'])?></small>
                    </div>
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-<?=$percent >= 80 ? 'danger' : ($percent >= 60 ? 'warning' : 'success')?>" 
                           style="width: <?=min($percent, 100)?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                      <small class="text-muted">Disponível: €<?=number_format($available, 2)?></small>
                      <small class="text-muted"><?=round($percent)?>%</small>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Style for selected category
document.querySelectorAll('.category-option').forEach(label => {
  label.addEventListener('click', function() {
    document.querySelectorAll('.category-option').forEach(l => l.style.borderColor = '#e9ecef');
    document.querySelectorAll('.category-option').forEach(l => l.style.background = 'white');
    this.style.borderColor = '#2ecc71';
    this.style.background = 'rgba(46, 204, 113, 0.05)';
  });
});
</script>
</body>
</html>