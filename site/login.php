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
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = :e OR username = :e LIMIT 1');
        $stmt->execute([':e' => $emailOrUsername]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Credenciais inválidas.';
        }
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Entrar</h2>

  <?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Email ou Nome de utilizador</label>
      <input type="text" name="email_or_username" class="form-control" value="<?=htmlspecialchars($emailOrUsername ?? '')?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
