<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || strlen($username) < 3) $errors[] = 'Nome de utilizador inválido (mín 3 chars).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($password) < 6) $errors[] = 'A password deve ter pelo menos 6 caracteres.';
    if ($password !== $password2) $errors[] = 'As passwords não coincidem.';

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
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registar - PAP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Registar</h2>

  <?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="mb-3">
      <label class="form-label">Nome de utilizador</label>
      <input type="text" name="username" class="form-control" value="<?=htmlspecialchars($username ?? '')?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($email ?? '')?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirmar Password</label>
      <input type="password" name="password2" class="form-control" required>
    </div>
    <button class="btn btn-primary" type="submit">Registar</button>
  </form>
</div>
</body>
</html>
