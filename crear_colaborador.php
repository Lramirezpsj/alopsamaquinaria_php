<?php
require 'db.php';
// Iniciar sesión y verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper($_POST['nombre']);
    $pdo->prepare("INSERT INTO colaboradores (nombre) VALUES (?)")->execute([$nombre]);
    header("Location: listar_colaboradores.php");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f2f2f2;
        }

        .container {
            max-width: 450px;
            background-color: #fff;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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


        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }

        input[type="text"],
        button {
            width: 80%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #28a745;
            color: white;
            border: none;
        }

        button:hover {
            background-color: #218838;
        }

        .btn-cancel {
            padding: 10px 128px;
            border: none;
            border-radius: 4px;
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
        <h1>Nuevo Colaborador</h1>
        <form method="post">
            Nombre: <input type="text" name="nombre" required>
            <button type="submit" class="btn-submit">Guardar</button>
            <a href="listar_colaboradores.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>

</html>