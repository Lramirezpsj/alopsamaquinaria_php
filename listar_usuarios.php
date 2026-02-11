<?php
// 1. Iniciar sesión
session_start();

// 2. Verificar autenticación y rol de administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// 3. Verificar conexión a la base de datos
if (!isset($pdo) || $pdo === null) {
    header("Location: /error.php?code=db");
    exit();
}

// 4. Configurar headers de seguridad
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Obtener todos los usuarios
$usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="listar_usuarios.css">
    <link rel="stylesheet" href="nodal.css">
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

        a {
            color: #0066cc;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table,
        th,
        td {
            border: 1px solid #ccc;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
        }



        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            color: white;
            background-color: #007bff;
            transition: background-color 0.3s ease;
        }

        .actions-cell {
            display: flex;
            gap: 10px;
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
    <h1>Usuarios</h1>
    <div class="actions">
        <a href="crear_usuario.php" class="btn">✚ Agregar usuario</a>
    </div>
    <table border="1">
        <tr>
            <th>Id</th>
            <th>USUARIO</th>
            <th>CONTRASEÑA</th>
            <th>ROL</th>
            <th>ACCIONES</th>
        </tr>
        <?php foreach ($usuarios as $usu): ?>
            <tr>
                <td><?= htmlspecialchars($usu['id_usuario']) ?></td>
                <td><?= htmlspecialchars($usu['usuario']) ?></td>
                <td><?= str_repeat('*', strlen($usu['contrasenia'])) ?></td> <!-- ocultar contraseña -->
                <td><?= htmlspecialchars($usu['rol']) ?></td>
                <td class="actions-cell">
                    <a href="editar_usuario.php?id=<?= $usu['id_usuario'] ?>" class="btn-edit">✏️ Editar</a> |
                    <a href="eliminar_usuario.php?id=<?= $usu['id_usuario'] ?>" class="btn-delete"
                        onclick="return confirm('¿Eliminar?')">🗑️
                        Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>