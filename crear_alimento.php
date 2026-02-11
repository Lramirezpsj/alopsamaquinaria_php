<?php
require 'db.php';
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper($_POST['nombre']);
    $precio = $_POST['precio'];
    $pdo->prepare("INSERT INTO alimentos (nombre, precio) VALUES (?, ?)")->execute([$nombre, $precio]);
    header("Location: listar_alimentos.php");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <style>
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin-top: 20px;
        }

        .container {
            max-width: 450px;
            background-color: #fff;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            border: none;
        }

        button:hover {
            background-color: #218838;
        }

        .btn-cancel {
            padding: 10px 171px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            color: white;
            transition: background-color 0.3s ease;
        }

        .btn-cancel {
            background-color: #dc3545;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Nuevo Alimento</h1>
        <form method="post">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Precio:</label>
                <input type="number" step="0.01" name="precio" required>
            </div>
            <button type="submit">Guardar</button><br><br>
            <a href="listar_alimentos.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>

</html>