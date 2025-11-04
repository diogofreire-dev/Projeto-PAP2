<?php
// site/edit_transaction.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$errors = [];
$success = false;

// Obter ID da transação
$transaction_id = !empty($_GET['id']) ? intval($_GET['id']) : null;

if (!$transaction_id) {
    header('Location: transactions.php');
    exit;
}

// Buscar a transação
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = :id AND user_id = :uid");
$stmt->execute([':id' => $transaction_id, ':uid' => $uid]);
$transaction = $stmt->fetch();

if (!$transaction) {
    header('Location: transactions.php');
    exit;
}

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
    
    $old_amount = $transaction['amount'];
    $old_card_id = $transaction['card_id'];

    // Validações
    if ($amount <= 0) {
        $errors[] = 'O valor deve ser maior que zero.';
    }
    if (strlen($description) < 3) {
        $errors[] = 'A descrição deve ter pelo menos 3 caracteres.';
    }

    // Verificar se o cartão tem limite disponível (se mudou)
    if ($card_id && ($card_id != $old_card_id || $amount != $old_amount)) {
        $stmt = $pdo->prepare("SELECT limit_amount, balance FROM cards WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $card_id, ':uid' => $uid]);
        $card = $stmt->fetch();
        
        if ($card && $card['limit_amount'] > 0) {
            // Calcular o novo saldo se aplicarmos esta transação
            $currentBalance = $card['balance'];
            
            // Se era o mesmo cartão, remover o valor antigo
            if ($card_id == $old_card_id) {
                $currentBalance -= $old_amount;
            }
            
            // Adicionar o novo valor
            $newBalance = $currentBalance + $amount;
            
            if ($newBalance > $card['limit_amount']) {
                $errors[] = 'Esta transação excede o limite disponível do cartão.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Atualizar a transação
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET amount = :amt, description = :desc, category = :cat, card_id = :cid
                WHERE id = :id AND user_id = :uid
            ");
            $stmt->execute([
                ':amt' => $amount,
                ':desc' => $description,
                ':cat' => $category ?: null,
                ':cid' => $card_id,
                ':id' => $transaction_id,
                ':uid' => $uid
            ]);

            // Ajustar saldos dos cartões
            // 1. Se tinha cartão antigo, devolver o valor
            if ($old_card_id) {
                $stmt = $pdo->prepare("
                    UPDATE cards 
                    SET balance = balance - :amt 
                    WHERE id = :cid AND user_id = :uid
                ");
                $stmt->execute([':amt' => $old_amount, ':cid' => $old_card_id, ':uid' => $uid]);
            }

            // 2. Se tem cartão novo, adicionar o valor
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
            
            // Atualizar os dados da transação para mostrar os novos valores
            $transaction['amount'] = $amount;
            $transaction['description'] = $description;
            $transaction['category'] = $category;
            $transaction['card_id'] = $card_id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Erro ao atualizar transação. Tenta novamente.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar Transação - FreeCard</title>
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
    .category-option.selected {
      border-color: var(--primary-green);
      background: rgba(46, 204, 113, 0.05);
    }
    .category-icon {
      font-size: 32px;
      margin-bottom: 8px;
    }
    .info-box {
      background: #f8f9fa;
      border-left: 4px solid #3498db;
      padding: 16px;
      border-radius: 8px;
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
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5 mb-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="mb-4">
        <a href="transactions.php" class="text-decoration-none text-muted">
          <i class="bi bi-arrow-left"></i> Voltar às Transações
        </a>
      </div>

      <div class="row g-4">
        <div class="col-lg-7">
          <div class="card">
            <div class="card-header text-white">
              <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Transação</h4>
            </div>
            <div class="card-body p-4">
              <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                  <i class="bi bi-check-circle"></i> Transação atualizada com sucesso!
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

              <div class="info-box mb-4">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-info-circle text-primary"></i>
                  <div>
                    <strong>Data original:</strong> <?=date('d/m/Y H:i', strtotime($transaction['created_at']))?>
                    <br>
                    <small class="text-muted">A data da transação não será alterada</small>
                  </div>
                </div>
              </div>

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
                    value="<?=htmlspecialchars($transaction['amount'])?>" 
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
                    value="<?=htmlspecialchars($transaction['description'])?>" 
                    required
                  >
                </div>

                <div class="mb-4">
                  <label class="form-label mb-3">Categoria</label>
                  <div class="row g-2">
                    <?php foreach($categories as $cat): ?>
                      <div class="col-6 col-md-3">
                        <label class="category-option <?=($transaction['category'] ?? '') === $cat['name'] ? 'selected' : ''?>">
                          <input type="radio" name="category" value="<?=$cat['name']?>" <?=($transaction['category'] ?? '') === $cat['name'] ? 'checked' : ''?>>
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
                    <option value="" <?=!$transaction['card_id'] ? 'selected' : ''?>>💰 Nenhum / Dinheiro</option>
                    <?php foreach($cards as $c): ?>
                      <option value="<?=$c['id']?>" <?=($transaction['card_id'] ?? '') == $c['id'] ? 'selected' : ''?>>
                        💳 <?=htmlspecialchars($c['name'])?> (•••• <?=htmlspecialchars($c['last4'])?>)
                        - Disponível: €<?=number_format($c['limit_amount'] - $c['balance'], 2)?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted">Deixa vazio se foi pago em dinheiro</small>
                </div>

                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Guardar Alterações
                  </button>
                  <a href="transactions.php" class="btn btn-outline-secondary">
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
              <h5 class="mb-4"><i class="bi bi-info-circle"></i> Informações</h5>
              
              <div class="mb-3">
                <h6><i class="bi bi-calendar text-primary"></i> Data preservada</h6>
                <p class="text-muted small mb-0">A data e hora originais da transação serão mantidas, mesmo após editar.</p>
              </div>
              
              <div class="mb-3">
                <h6><i class="bi bi-credit-card text-success"></i> Saldos automáticos</h6>
                <p class="text-muted small mb-0">Se mudares o cartão ou o valor, os saldos serão ajustados automaticamente.</p>
              </div>
              
              <div class="mb-3">
                <h6><i class="bi bi-pencil text-warning"></i> Editar com cuidado</h6>
                <p class="text-muted small mb-0">Certifica-te que os dados estão corretos antes de guardar as alterações.</p>
              </div>

              <hr class="my-4">

              <h6 class="mb-3">Dados Originais</h6>
              <div class="p-3 bg-light rounded">
                <div class="mb-2">
                  <small class="text-muted">Valor</small>
                  <div class="fw-bold text-danger">€<?=number_format($transaction['amount'], 2)?></div>
                </div>
                <div class="mb-2">
                  <small class="text-muted">Descrição</small>
                  <div><?=htmlspecialchars($transaction['description'])?></div>
                </div>
                <?php if ($transaction['category']): ?>
                <div class="mb-2">
                  <small class="text-muted">Categoria</small>
                  <div><span class="badge bg-info"><?=htmlspecialchars($transaction['category'])?></span></div>
                </div>
                <?php endif; ?>
                <div>
                  <small class="text-muted">Data</small>
                  <div><?=date('d/m/Y H:i', strtotime($transaction['created_at']))?></div>
                </div>
              </div>
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
    document.querySelectorAll('.category-option').forEach(l => {
      l.classList.remove('selected');
    });
    this.classList.add('selected');
  });
});
</script>
</body>
</html>