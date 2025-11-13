<?php
// site/theme_helper.php
function getUserTheme($pdo, $user_id) {
    if (!$user_id) {
        return 'light';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT theme FROM user_settings WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['theme'];
        }
        
        // Se não existir, criar configurações padrão
        $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, theme) VALUES (:uid, 'light')");
        $stmt->execute([':uid' => $user_id]);
        return 'light';
    } catch (PDOException $e) {
        return 'light';
    }
}
?>