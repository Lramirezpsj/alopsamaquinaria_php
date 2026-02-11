<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "ID de pedido no especificado.";
    exit();
}

$id_pedido = $_GET['id'];

// Eliminar primero los detalles del pedido (por la relación)
$pdo->prepare("DELETE FROM detalle_pedidos WHERE id_pedido = ?")->execute([$id_pedido]);

// Luego eliminar el pedido
$pdo->prepare("DELETE FROM pedidos WHERE id_pedido = ?")->execute([$id_pedido]);

header("Location: listar_pedidos.php");
exit();
