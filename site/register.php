<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || strlen($username) < 3) $errors[] = 'Nome de utilizador inválido (mínimo 3 caracteres).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($password) < 6) $errors[] = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    if ($password !== $password2) $errors[] = 'As palavras-passe não coincidem.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Nome de utilizador ou email já em uso.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)');
            $ins->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            session_regenerate_id(true);
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registar - Freecard</title>
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
      padding: 40px 0;
    }
    
    .register-container {
      max-width: 500px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .register-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .register-header {%);
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
    
    .register-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 8px;
    }
    
    .register-header p {
      color: #7f8c8d;
      font-size: 15px;
      margin: 0;
    }
    
    .register-body {
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
    
    .login-link {
      text-align: center;
      padding: 20px 40px 30px;
      background: #f8f9fa;
      border-top: 1px solid #e9ecef;
    }
    
    .login-link p {
      margin: 0;
      color: #7f8c8d;
    }
    
    .login-link a {
      color: var(--primary-green);
      font-weight: 600;
      text-decoration: none;
    }
    
    .login-link a:hover {
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
    
    .password-strength {
      height: 4px;
      background: #e9ecef;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0;
      transition: all 0.3s;
      border-radius: 2px;
    }
    
    .strength-weak { width: 33%; background: #e74c3c; }
    .strength-medium { width: 66%; background: #f39c12; }
    .strength-strong { width: 100%; background: #2ecc71; }
  </style>
</head>
<body>

<div class="register-container">
  <div class="register-card">
    <div class="register-header">
      <div class="logo-container">
        <img src="assets/logo.png" alt="Freecard">
      </div>
      <h1>Cria a tua conta</h1>
      <p>Começa a gerir as tuas finanças hoje</p>
    </div>

    <div class="register-body">
      <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle"></i>
          <ul class="mb-0 mt-2 ps-3">
            <?php foreach($errors as $e): ?>
              <li><?=htmlspecialchars($e)?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Nome de utilizador</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input 
              type="text" 
              name="username" 
              class="form-control" 
              placeholder="Escolhe um nome de utilizador"
              value="<?=htmlspecialchars($username ?? '')?>" 
              required
              autofocus
            >
          </div>
          <small class="text-muted">Mínimo 3 caracteres</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input 
              type="email" 
              name="email" 
              class="form-control" 
              placeholder="O teu melhor email"
              value="<?=htmlspecialchars($email ?? '')?>" 
              required
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Palavra-passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input 
              type="password" 
              name="password" 
              class="form-control" 
              placeholder="Cria uma palavra-passe segura"
              id="password"
              required
            >
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="strength-bar"></div>
          </div>
          <small class="text-muted">Mínimo 6 caracteres</small>
        </div>

        <div class="mb-4">
          <label class="form-label">Confirmar palavra-passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input 
              type="password" 
              name="password2" 
              class="form-control" 
              placeholder="Repete a palavra-passe"
              required
            >
          </div>
        </div>

        <button class="btn btn-primary" type="submit">
          <i class="bi bi-check-circle"></i> Criar conta
        </button>
      </form>
    </div>

    <div class="login-link">
      <p>Já tens conta? <a href="login.php">Entra aqui</a></p>
    </div>
  </div>

  <div class="text-center mt-4">
    <a href="index.php" class="text-white text-decoration-none">
      <i class="bi bi-arrow-left"></i> Voltar à página inicial
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function(e) {
  const password = e.target.value;
  const strengthBar = document.getElementById('strength-bar');
  
  let strength = 0;
  if (password.length >= 6) strength++;
  if (password.length >= 10) strength++;
  if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;
  
  strengthBar.className = 'password-strength-bar';
  if (strength >= 4) {
    strengthBar.classList.add('strength-strong');
  } else if (strength >= 2) {
    strengthBar.classList.add('strength-medium');
  } else if (strength >= 1) {
    strengthBar.classList.add('strength-weak');
  }
});
</script>
</body>
</html>