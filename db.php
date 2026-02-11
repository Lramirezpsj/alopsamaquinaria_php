<?php // NADA antes de esta línea - absolutamente nada
// Verificar si ya está definido IN_SCRIPT para evitar inclusiones múltiples
if (!defined('ALOPSA_DB_CONNECTED')) {
    define('ALOPSA_DB_CONNECTED', true);

    // Configuración silenciosa
    $host = 'localhost';
    //$host = '107.172.81.104';
    $dbname = 'alopsamaquinaria';
    $username = 'root';
    //$username = 'lramirez';
    $password = '';
    //$password = 'clarus25';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]
        );
    } catch (PDOException $e) {
        // Registrar error sin output
        error_log('[' . date('Y-m-d H:i:s') . '] DB Error: ' . $e->getMessage());
        $pdo = null;
    }
}
?>