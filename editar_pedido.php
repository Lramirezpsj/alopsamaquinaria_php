<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_pedidos.php");
    exit();
}

/* ========= DATOS ========= */
$id_pedido = $_POST['id_pedido'];
$colaborador = $_POST['colaborador'];
$fecha = $_POST['fecha'];

$alimentos = $_POST['alimentos'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];

/* ========= VALIDACIONES ========= */
if (!$id_pedido || !$colaborador || !$fecha) {
    die("Datos incompletos");
}

/* ========= TRANSACCIÓN ========= */
try {
    $pdo->beginTransaction();

    // Actualizar pedido
    $stmt = $pdo->prepare("
        UPDATE pedidos 
        SET colaborador = ?, fecha = ? 
        WHERE id_pedido = ?
    ");
    $stmt->execute([$colaborador, $fecha, $id_pedido]);

    // Eliminar detalle anterior
    $stmt = $pdo->prepare("DELETE FROM detalle_pedidos WHERE id_pedido = ?");
    $stmt->execute([$id_pedido]);

    // Insertar nuevo detalle
    if (!empty($alimentos)) {
        $stmt = $pdo->prepare("
            INSERT INTO detalle_pedidos (id_pedido, id_alimento, cantidad)
            VALUES (?, ?, ?)
        ");

        foreach ($alimentos as $id_alimento) {
            $cantidad = $cantidades[$id_alimento] ?? 1;
            $stmt->execute([$id_pedido, $id_alimento, $cantidad]);
        }
    }

    $pdo->commit();
    header("Location: listar_pedidos.php");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al editar pedido: " . $e->getMessage());
}
