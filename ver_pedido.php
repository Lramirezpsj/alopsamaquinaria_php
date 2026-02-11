<?php
require 'db.php';

// Validación segura del ID
$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido) {
    header('Location: listar_pedidos.php');
    exit;
}

// Obtener el pedido con JOIN para el nombre del colaborador
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre AS nombre_colaborador
    FROM pedidos p
    JOIN colaboradores c ON p.colaborador = c.id_colaborador
    WHERE p.id_pedido = ?
");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die('Pedido no encontrado');
}

// Obtener detalles del pedido
$stmt = $pdo->prepare("
    SELECT a.nombre, a.precio, d.cantidad
    FROM detalle_pedidos d
    JOIN alimentos a ON d.id_alimento = a.id_alimento
    WHERE d.id_pedido = ?
");
$stmt->execute([$id_pedido]);
$detalles = $stmt->fetchAll();

// Calcular total
$total = 0;
foreach ($detalles as $detalle) {
    $total += $detalle['precio'] * $detalle['cantidad'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Detalles del Pedido #<?= htmlspecialchars($id_pedido) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #2c3e50;
        }

        .pedido-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .total-row {
            font-weight: bold;
            background-color: #e8f4fc !important;
        }

        .btn-volver {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }

        .btn-volver:hover {
            background: #2980b9;
        }
    </style>
</head>

<body>
    <h1>Detalles del Pedido #<?= htmlspecialchars($id_pedido) ?></h1>

    <div class="pedido-info">
        <p><strong>Colaborador:</strong> <?= htmlspecialchars($pedido['nombre_colaborador']) ?></p>
        <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido['fecha']) ?></p>
    </div>

    <h2>Alimentos</h2>
    <?php if (empty($detalles)): ?>
        <p>No hay alimentos registrados en este pedido.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= htmlspecialchars($detalle['nombre']) ?></td>
                        <td>Q<?= number_format($detalle['precio'], 2) ?></td>
                        <td><?= htmlspecialchars($detalle['cantidad']) ?></td>
                        <td>Q<?= number_format($detalle['precio'] * $detalle['cantidad'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3"><strong>Total General</strong></td>
                    <td><strong>Q<?= number_format($total, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="listar_pedidos.php" class="btn-volver">← Volver al Listado</a>
</body>

</html>