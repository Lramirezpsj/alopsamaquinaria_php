<?php
require 'db.php';
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$alimentos = $pdo->query("SELECT * FROM alimentos")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta name="viewport" content="width=device-width initial-scale=1.0">
    <title>Alimentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_alimentos.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="navbar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f2f2f2;
        }

        h1 {
            color: #333;
        }



        table {
            border-collapse: collapse;
            width: 100%;
            border-collapse: collapse;
            margin: 30px auto;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        a {
            margin-right: 10px;
            color: #0066cc;
        }

        a:hover {
            text-decoration: underline;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .btn-edit {
            color: #28a745;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-delete {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php' ?>
    <h1>Lista de Alimentos</h1>
    <div class="actions">
        <a href="crear_alimento.php" class="btn">+ Nuevo Alimento</a>
    </div>
    <table border="1">
        <tr>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($alimentos as $alimento): ?>
            <tr>
                <td><?= htmlspecialchars($alimento['nombre']) ?></td>
                <td>Q<?= number_format($alimento['precio'], 2) ?></td>
                <td>
                    <a class="btn-edit" href="editar_alimento.php?id=<?= $alimento['id_alimento'] ?>">✏️ Editar</a>
                    <a class="btn-delete" href="eliminar_alimento.php?id=<?= $alimento['id_alimento'] ?>"
                        onclick="return confirm('¿Seguro que deseas eliminar este alimento?')">🗑️ Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>