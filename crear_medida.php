<?php
require __DIR__ . '/db.php';

// iniciar sesion
session_start();

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit('No autorizado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_medidas.php");
    exit();
}

try {
    //$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $medida = strtoupper(trim($_POST['medida'] ?? null));

    if (!$medida) {
        throw new Exception("Campos obligatorios incompletos");
    }

    $sql = 'INSERT INTO medidas(medida) VALUES (?)';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$medida]);

    header("Location: listar_medidas.php");

    exit();
} catch (Exception $e) {
    echo "Error " . $e->getMessage();
}