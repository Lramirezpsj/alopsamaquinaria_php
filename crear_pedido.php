<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $colaborador = $_POST['colaborador'];
    $fecha = $_POST['fecha'];
    $alimentos = $_POST['alimentos']; // Array de id_alimento
    $cantidades = $_POST['cantidades']; // Array de cantidades

    $stmt = $pdo->prepare("INSERT INTO pedidos (colaborador, fecha) VALUES (?, ?)");
    $stmt->execute([$colaborador, $fecha]);
    $id_pedido = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO detalle_pedidos (id_pedido, id_alimento, cantidad) VALUES (?, ?, ?)");
    for ($i = 0; $i < count($alimentos); $i++) {
        $stmt->execute([$id_pedido, $alimentos[$i], $cantidades[$i]]);
    }

    header("Location: listar_pedidos.php");
    exit;
}