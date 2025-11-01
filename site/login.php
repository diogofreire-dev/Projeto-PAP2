<?php
// site/login.php
session_start();
require_once __DIR__ . '/../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['email_or_username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($emailOrUsername === '' || $password === '') {
        $errors[] = 'Preenche todos os campos.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = :e OR username = :u LIMIT 1');
        $stmt->execute([':e' => $emailOrUsername, ':u' => $emailOrUsername]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true);
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Credenciais inválidas.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar - Freecard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-green: #2ecc71;
      --dark-green: #27ae60;
      --light-bg: #f8f9fa;
    }
    
    body {
      background: #2c3e50;
      min-height: 100vh;
      display: flex;
      align-items: center;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .login-container {
      max-width: 450px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .login-header {
      background: white;
      padding: 40px 40px 30px;
      text-align: center;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .logo-container {
      margin-bottom: 20px;
    }
    
    .logo-container img {
      width: 120px;
      height: auto;
    }
    
    .login-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    
    .login-header p {
      color: #7f8c8d;
      font-size: 15px;
      margin: 0;
    }
    
    .login-body {
      padding: 40px;
    }
    
    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 8px;
      font-size: 14px;
    }
    
    .form-control {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 15px;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary-green);
      box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }
    
    .btn-primary {
      background: var(--primary-green);
      border: none;
      border-radius: 10px;
      padding: 14px;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s;
      width: 100%;
    }
    
    .btn-primary:hover {
      background: var(--dark-green);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
    }
    
    .alert {
      border-radius: 10px;
      border: none;
      padding: 12px 16px;
    }
    
    .divider {
      text-align: center;
      margin: 25px 0;
      position: relative;
    }
    
    .divider::before {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      top: 50%;
      height: 1px;
      background: #e9ecef;
    }
    
    .divider span {
      background: white;
      padding: 0 15px;
      position: relative;
      color: #95a5a6;
      font-size: 14px;
    }
    
    .register-link {
      text-align: center;
      padding: 20px 40px 30px;
      background: #f8f9fa;
      border-top: 1px solid #e9ecef;
    }
    
    .register-link p {
      margin: 0;
      color: #7f8c8d;
    }
    
    .register-link a {
      color: var(--primary-green);
      font-weight: 600;
      text-decoration: none;
    }
    
    .register-link a:hover {
      color: var(--dark-green);
      text-decoration: underline;
    }
    
    .input-group-text {
      background: transparent;
      border: 2px solid #e9ecef;
      border-right: none;
      border-radius: 10px 0 0 10px;
    }
    
    .input-group .form-control {
      border-left: none;
      border-radius: 0 10px 10px 0;
    }
    
    .input-group:focus-within .input-group-text {
      border-color: var(--primary-green);
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <div class="logo-container">
        <img src="assets/logo.png" alt="Freecard">
      </div>
      <h1>Bem-vindo de volta</h1>
      <p>Entra na tua conta para continuar</p>
    </div>

    <div class="login-body">
      <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle"></i>
          <?php foreach($errors as $e): ?>
            <?=htmlspecialchars($e)?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email ou Nome de utilizador</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input 
              type="text" 
              name="email_or_username" 
              class="form-control" 
              placeholder="Introduz o teu email ou utilizador"
              value="<?=htmlspecialchars($emailOrUsername ?? '')?>" 
              required
              autofocus
            >
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Palavra-passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input 
              type="password" 
              name="password" 
              class="form-control" 
              placeholder="Introduz a tua palavra-passe"
              required
            >
          </div>
        </div>

        <button class="btn btn-primary" type="submit">
          <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
      </form>
    </div>

    <div class="register-link">
      <p>Ainda não tens conta? <a href="register.php">Regista-te aqui</a></p>
    </div>
  </div>

  <div class="text-center mt-4">
    <a href="index.php" class="text-white text-decoration-none">
      <i class="bi bi-arrow-left"></i> Voltar à página inicial
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>