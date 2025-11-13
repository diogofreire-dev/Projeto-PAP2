<?php
// site/theme_helper.php
// Incluir este ficheiro em páginas que precisem do tema

function getUserTheme($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT theme FROM user_settings WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['theme'];
    }
    
    // Se não existir, criar configurações padrão
    try {
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme) VALUES (:uid, 'light')");
        $stmt->execute([':uid' => $user_id]);
        return 'light';
    } catch (PDOException $e) {
        return 'light';
    }
}

// Usar no início de cada página após auth.php:
// $currentTheme = getUserTheme($pdo, $uid);
?>