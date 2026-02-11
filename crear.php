<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit('No autorizado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $fecha = $_POST['fecha'] ?? null;
    $maquina = $_POST['maquina'] ?? null;
    $cliente = $_POST['cliente'] ?? null;
    $h_inicio = $_POST['h_inicio'] ?? null;
    $h_final = $_POST['h_final'] !== '' ? $_POST['h_final'] : null;
    $turno = $_POST['turno'] ?? null;
    $comentarios = mb_strtoupper($_POST['comentarios'] ?? '', 'UTF-8');

    // 👇 AJUSTE CLAVE
    if ($_SESSION['rol'] === 'ADMIN' && !empty($_POST['operador'])) {
        $operador = $_POST['operador'];
    } else {
        $operador = $_SESSION['usuario'];
    }

    if (!$fecha || !$maquina || !$cliente || !$h_inicio || !$turno) {
        throw new Exception("Campos obligatorios incompletos");
    }

    $sql = "INSERT INTO horometros
            (fecha, maquina, cliente, h_inicio, h_final, turno, comentarios, operador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $fecha,
        $maquina,
        $cliente,
        $h_inicio,
        $h_final,
        $turno,
        $comentarios,
        $operador
    ]);

    header("Location: listar.php");
    exit();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

