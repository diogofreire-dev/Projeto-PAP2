<?php
// site/settings.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
$uid = $_SESSION['user_id'] ?? null;
require_once __DIR__ . '/theme_helper.php';
$currentTheme = getUserTheme($pdo, $uid);

$message = '';
$messageType = 'info';

// Buscar ou criar configurações do utilizador
$stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = :uid");
$stmt->execute([':uid' => $uid]);
$settings = $stmt->fetch();

if (!$settings) {
    // Criar configurações padrão se não existirem
    $stmt = $pdo->prepare("INSERT INTO user_settings (user_id) VALUES (:uid)");
    $stmt->execute([':uid' => $uid]);
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = :uid");
    $stmt->execute([':uid' => $uid]);
    $settings = $stmt->fetch();
}

// Processar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_settings 
            SET theme = :theme, notifications = :notifications
            WHERE user_id = :uid
        ");
        $stmt->execute([
            ':theme' => $theme,
            ':notifications' => $notifications,
            ':uid' => $uid
        ]);
        
        // Atualizar configurações locais
        $settings['theme'] = $theme;
        $settings['notifications'] = $notifications;
        
        $message = 'Configurações atualizadas com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro ao atualizar configurações.';
        $messageType = 'danger';
    }
}

// Incluir o tema atual
$currentTheme = $settings['theme'] ?? 'light';
?>
<!doctype html>
<html lang="pt-PT" data-theme="<?=$currentTheme?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configurações - FreeCard</title>
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
      background-color: var(--bg-primary);
      color: var(--text-primary);
      transition: background-color 0.3s, color 0.3s;
    }
    .navbar { 
      box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
      background: var(--bg-secondary);
      transition: background-color 0.3s;
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
      box-shadow: 0 4px 20px var(--shadow);
      background: var(--bg-secondary);
      color: var(--text-primary);
      transition: all 0.3s;
    }
    .form-label {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 8px;
    }
    .form-control, .form-select {
      border: 2px solid var(--border-color);
      border-radius: 10px;
      padding: 12px 16px;
      transition: all 0.3s;
      background: var(--bg-primary);
      color: var(--text-primary);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-green);
      box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
      background: var(--bg-primary);
      color: var(--text-primary);
    }
    .setting-item {
      padding: 24px;
      border-bottom: 1px solid var(--border-color);
      transition: background-color 0.3s;
    }
    .setting-item:last-child {
      border-bottom: none;
    }
    .setting-item:hover {
      background: var(--bg-hover);
    }
    .theme-preview {
      width: 80px;
      height: 50px;
      border-radius: 8px;
      border: 3px solid transparent;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .theme-preview:hover {
      transform: scale(1.05);
    }
    .theme-preview.active {
      border-color: var(--primary-green);
      box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }
    .theme-preview-light {
      background: linear-gradient(135deg, #ffffff 50%, #f8f9fa 50%);
    }
    .theme-preview-dark {
      background: linear-gradient(135deg, #1a1d29 50%, #2c3e50 50%);
    }
    .theme-preview input[type="radio"] {
      display: none;
    }
    .theme-preview.active::after {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: var(--primary-green);
      font-size: 24px;
      font-weight: bold;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .form-check-input:checked {
      background-color: var(--primary-green);
      border-color: var(--primary-green);
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
        <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-graph-up"></i> Análise</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?=htmlspecialchars($_SESSION['username'])?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item active" href="settings.php"><i class="bi bi-gear"></i> Configurações</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4 mb-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="mb-4">
        <a href="dashboard.php" class="text-decoration-none" style="color: var(--text-secondary);">
          <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
        </a>
      </div>

      <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green)); border-radius: 16px 16px 0 0; padding: 24px;">
          <h4 class="mb-0 text-white"><i class="bi bi-gear"></i> Configurações</h4>
        </div>

        <?php if ($message): ?>
          <div class="alert alert-<?=$messageType?> alert-dismissible fade show m-4 mb-0">
            <i class="bi bi-<?=$messageType === 'success' ? 'check-circle' : 'info-circle'?>"></i>
            <?=htmlspecialchars($message)?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="post">
          <!-- Aparência -->
          <div class="setting-item">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="mb-1"><i class="bi bi-palette"></i> Aparência</h5>
                <p class="text-muted small mb-0">Escolhe o tema que preferes para a interface</p>
              </div>
            </div>
            
            <div class="d-flex gap-3">
              <label class="theme-preview theme-preview-light <?=$currentTheme === 'light' ? 'active' : ''?>">
                <input type="radio" name="theme" value="light" <?=$currentTheme === 'light' ? 'checked' : ''?>>
                <div class="p-2">
                  <small class="fw-bold" style="color: #2c3e50;">Claro</small>
                </div>
              </label>
              
              <label class="theme-preview theme-preview-dark <?=$currentTheme === 'dark' ? 'active' : ''?>">
                <input type="radio" name="theme" value="dark" <?=$currentTheme === 'dark' ? 'checked' : ''?>>
                <div class="p-2">
                  <small class="fw-bold" style="color: #ecf0f1;">Escuro</small>
                </div>
              </label>
            </div>
          </div>

          <!-- Notificações -->
          <div class="setting-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><i class="bi bi-bell"></i> Notificações</h5>
                <p class="text-muted small mb-0">Recebe alertas quando te aproximas dos limites dos cartões</p>
              </div>
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  name="notifications" 
                  id="notifications"
                  <?=$settings['notifications'] ? 'checked' : ''?>
                  style="width: 3em; height: 1.5em;"
                >
              </div>
            </div>
          </div>

          <!-- Informações da Conta -->
          <div class="setting-item">
            <h5 class="mb-3"><i class="bi bi-person"></i> Informações da Conta</h5>
            <div class="p-3 rounded" style="background: var(--bg-primary);">
              <div class="row">
                <div class="col-md-6 mb-2">
                  <small class="text-muted">Nome de utilizador</small>
                  <div class="fw-semibold"><?=htmlspecialchars($_SESSION['username'])?></div>
                </div>
                <div class="col-md-6 mb-2">
                  <small class="text-muted">Membro desde</small>
                  <div class="fw-semibold">
                    <?php
                    $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = :uid");
                    $stmt->execute([':uid' => $uid]);
                    $user = $stmt->fetch();
                    echo date('d/m/Y', strtotime($user['created_at']));
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Botões de Ação -->
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Guardar Alterações
              </button>
              <a href="dashboard.php" class="btn btn-outline-secondary">
                Cancelar
              </a>
            </div>
          </div>
        </form>
      </div>

      <!-- Informações Adicionais -->
      <div class="card mt-4">
        <div class="card-body">
          <h6 class="mb-3"><i class="bi bi-info-circle"></i> Sobre o FreeCard</h6>
          <p class="text-muted small mb-2">Versão: 1.0.0</p>
          <p class="text-muted small mb-0">
            Desenvolvido por Diogo Freire e Jandro Antunes<br>
            Projeto de Aptidão Profissional
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preview ao vivo do tema
document.querySelectorAll('input[name="theme"]').forEach(radio => {
  radio.addEventListener('change', function() {
    document.documentElement.setAttribute('data-theme', this.value);
    
    // Atualizar classes ativas
    document.querySelectorAll('.theme-preview').forEach(preview => {
      preview.classList.remove('active');
    });
    this.closest('.theme-preview').classList.add('active');
  });
});
</script>
</body>
</html>