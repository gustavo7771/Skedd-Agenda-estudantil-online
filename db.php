<?php
/**
 * SKEDD - Conexão PDO MySQL com Auto-Provisionamento de Tabelas
 * Repositório: https://github.com/gustavo7771/Skedd-Agenda-estudantil-online
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Crypto.php';

class Database {
    private static $pdo = null;
    private static $conexaoErro = null;

    /**
     * Retorna a instância PDO única (Singleton)
     */
    public static function getConnection() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        // Lista de senhas para tentar (configurada primeiro, depois alternativas comuns de dev)
        $passwordsToTry = [DB_PASS];
        if (DB_PASS !== '' && !in_array('', $passwordsToTry)) {
            $passwordsToTry[] = '';
        }
        if (DB_PASS !== 'root' && !in_array('root', $passwordsToTry)) {
            $passwordsToTry[] = 'root';
        }

        foreach ($passwordsToTry as $currentPass) {
            try {
                // Tenta conexão direta com a base de dados
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );

                self::$pdo = new PDO($dsn, DB_USER, $currentPass, $options);
                self::verificarEInicializarSchema(self::$pdo);

                return self::$pdo;
            } catch (PDOException $e) {
                // Se o erro for de banco inexistente (código 1049), tenta criá-lo automaticamente
                if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $dsnRoot = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
                        $pdoRoot = new PDO($dsnRoot, DB_USER, $currentPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                        // Reconecta agora com o banco criado
                        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
                        self::$pdo = new PDO($dsn, DB_USER, $currentPass, $options);
                        self::verificarEInicializarSchema(self::$pdo);
                        return self::$pdo;
                    } catch (Exception $e2) {
                        self::$conexaoErro = $e2->getMessage();
                    }
                } else {
                    self::$conexaoErro = $e->getMessage();
                }
            }
        }

        error_log("Skedd DB Connection Error: " . self::$conexaoErro);
        return null;
    }

    /**
     * Retorna se a conexão está ativa
     */
    public static function isConectado() {
        return (self::getConnection() !== null);
    }

    /**
     * Retorna o erro da última conexão
     */
    public static function getErro() {
        return self::$conexaoErro;
    }

    /**
     * Verifica se as tabelas existem. Se não, executa a inicialização do database.sql automaticamente
     */
    private static function verificarEInicializarSchema(PDO $pdo) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
            if ($stmt->rowCount() === 0) {
                $sqlPath = __DIR__ . '/database.sql';
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    // Remove delimitadores USE e divide comandos por ponto e vírgula
                    $comandos = explode(';', $sql);
                    foreach ($comandos as $cmd) {
                        $cmd = trim($cmd);
                        if (!empty($cmd) && stripos($cmd, 'CREATE DATABASE') === false && stripos($cmd, 'USE ') !== 0) {
                            try {
                                $pdo->exec($cmd);
                            } catch (PDOException $ignore) {
                                // Segue para os próximos comandos
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao checar/inicializar schema do Skedd: " . $e->getMessage());
        }
    }
}

