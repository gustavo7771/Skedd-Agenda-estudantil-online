<?php
/**
 * SKEDD - Agenda Estudantil Online
 * Configurações Gerais do Sistema e Parâmetros de Segurança
 * Repositório: https://github.com/gustavo7771/Skedd-Agenda-estudantil-online
 */

// Impede exibição de erros detalhados em produção para evitar vazamento de informações sensíveis
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Constantes Institucionais
if (!defined('SKEDD_CHAVE_INSTITUCIONAL')) {
    define('SKEDD_CHAVE_INSTITUCIONAL', 'SKEDE123');
}
if (!defined('SKEDD_SENHA_ADMIN_MASTER')) {
    define('SKEDD_SENHA_ADMIN_MASTER', 'admin123');
}
if (!defined('SKEDD_GITHUB_REPO')) {
    define('SKEDD_GITHUB_REPO', 'https://github.com/gustavo7771/Skedd-Agenda-estudantil-online');
}

// Configurações do Banco de Dados MySQL
// É possível customizar via variáveis de ambiente no servidor ou manter os padrões abaixo
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'skedd');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root');
define('DB_CHARSET', 'utf8mb4');

// Chave mestra de criptografia AES-256 (32 bytes)
define('SKEDD_ENCRYPTION_KEY', getenv('SKEDD_SECRET_KEY') ?: 'Skedd_SuperSecr3t_Key_2026_AES256GCM_Protegido!');

// Headers de Segurança HTTP
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Inicialização de Sessão Segura
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = [
        'lifetime' => 0, // Duração até fechar o navegador
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    
    session_name('SKEDD_SESSION');
    session_start();
}

// Gerador e Validador de Token CSRF
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Função utilitária para sanitização XSS
function sanitizar($texto) {
    if ($texto === null) return '';
    return htmlspecialchars(trim((string)$texto), ENT_QUOTES, 'UTF-8');
}

