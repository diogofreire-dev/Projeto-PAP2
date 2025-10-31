<?php
// site/create_transaction.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$uid = $_SESSION['user_id'] ?? null;
$errors = [];
$success = false;

// Buscar cartões do utilizador
$stmt = $pdo->prepare("SELECT id, name, last4 FROM cards WHERE user_id = :uid AND active = 1");
$stmt->execute([':uid' => $uid]);
$cards = $stmt->fetchAll();

// Categorias comuns
$categories = ['Alimentação', 'Transporte', 'Compras', 'Saúde', 'Entretenimento', 'Educação', 'Outros'];

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
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nova Transação - PAP</title>
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
          <h4 class="mb-0">🧾 Nova Transação</h4>
        </div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
              ✅ Transação registada com sucesso!
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

          <?php if (empty($cards)): ?>
            <div class="alert alert-info">
              ℹ️ Ainda não tens cartões registados. 
              <a href="add_card.php" class="alert-link">Adiciona um cartão primeiro</a> 
              ou continua sem cartão associado.
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Valor (€) *</label>
              <input 
                type="number" 
                name="amount" 
                class="form-control form-control-lg" 
                placeholder="45.60"
                step="0.01"
                min="0.01"
                value="<?=htmlspecialchars($amount ?? '')?>" 
                required
                autofocus
              >
            </div>

            <div class="mb-3">
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

            <div class="mb-3">
              <label class="form-label">Categoria</label>
              <select name="category" class="form-select">
                <option value="">Seleciona uma categoria (opcional)</option>
                <?php foreach($categories as $cat): ?>
                  <option value="<?=$cat?>" <?=($category ?? '') === $cat ? 'selected' : ''?>>
                    <?=$cat?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Cartão Associado</label>
              <select name="card_id" class="form-select">
                <option value="">Nenhum / Dinheiro</option>
                <?php foreach($cards as $c): ?>
                  <option value="<?=$c['id']?>" <?=($card_id ?? '') == $c['id'] ? 'selected' : ''?>>
                    <?=htmlspecialchars($c['name'])?> (•••• <?=htmlspecialchars($c['last4'])?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Deixa vazio se foi pago em dinheiro</small>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                ➕ Registar Transação
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