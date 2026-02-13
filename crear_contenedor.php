<?php
session_start();
require 'db.php';
require_once "./validaciones.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Acceso no permitido");
}

/* ===== Sanitizar datos ===== */

$fecha = $_POST['fecha'] ?? null;
$maquina = $_POST['maquina'] ?? null;
$contenedor = strtoupper(trim($_POST['contenedor'] ?? ''));
$medida = $_POST['medida'] ?? null;
$movimiento = $_POST['movimiento'] ?? null;
$colaborador = $_POST['colaborador'] ?? null;
$comentarios = mb_strtoupper(trim($_POST['comentarios'] ?? ''), 'UTF-8');
/* OPERADOR SEGURO */
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN' && !empty($_POST['operador'])) {
    $operador = $_POST['operador'];
} else {
    $operador = $_SESSION['usuario'];
}

/* ===== Validar campos obligatorios ===== */

if (!$fecha || !$maquina || !$contenedor) {
    echo "ERROR_DATOS";
    exit;
}

/* ===== Validar contenedor ===== */

if (!validarContenedor($contenedor)) {
    echo "ERROR_CONTENEDOR";
    exit;
}

try {

    $sql = "INSERT INTO contenedores 
    (fecha, maquina, contenedor, medida, movimiento, colaborador, comentarios, operador)
    VALUES (?,?,?,?,?,?,?,?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $fecha,
        $maquina,
        $contenedor,
        $medida,
        $movimiento,
        $colaborador,
        $comentarios,
        $operador
    ]);

    header("Location: listar_contenedores.php");
    exit;

} catch (PDOException $e) {

    echo "ERROR_DB";
}
