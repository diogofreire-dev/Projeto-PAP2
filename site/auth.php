<?php
// site/auth.php
// Inclui no topo de páginas que exigem autenticação.
// Uso: require_once __DIR__ . '/auth.php';

session_start();

if (empty($_SESSION['user_id'])) {
    // podes redirecionar para login com uma query string para voltar depois
    $return = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /login.php?next={$return}");
    exit;
}
