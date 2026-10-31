<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Limpa sessão no MySQL se conectado
if (!empty($_SESSION['skedd_usuario']['id'])) {
    $pdo = Database::getConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE usuarios SET lembrar_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['skedd_usuario']['id']]);
    }
}

// Destrói a sessão PHP
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
setcookie('skedd_lembrar_token', '', time() - 3600, '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Skedd - Saindo...</title>
    <script src="skedd-store.js"></script>
    <script>
        try {
            localStorage.removeItem('skedd_sessao');
            localStorage.removeItem('skedd_lembrar_token');
        } catch(e) {}
        window.location.href = 'index.php';
    </script>
</head>
<body>
    <p style="font-family: sans-serif; text-align: center; margin-top: 50px;">Saindo da conta...</p>
</body>
</html>

