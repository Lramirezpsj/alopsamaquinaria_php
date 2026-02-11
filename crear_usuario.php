<?php
require 'db.php';

session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = strtoupper($_POST['usuario']);
    $contrasenia = strtoupper($_POST['contrasenia']);
    $rol = strtoupper($_POST['rol']);

    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, contrasenia, rol) VALUES (?, ?, ?)");
    $stmt->execute([$usuario, $contrasenia, $rol]);

    header("Location: listar_usuarios.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit,
        .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            color: white;
            transition: background-color 0.3s ease;
            flex: 1;
        }

        .btn-submit {
            background-color: #28a745;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

        .btn-cancel {
            background-color: #dc3545;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Nuevo Usuario</h1>
        <form method="POST" class="form-container">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <label for="contrasenia">Contraseña</label>
                <input type="text" id="contrasenia" name="contrasenia" required>
            </div>
            <div class="form-group">
                <label for="rol">Rol</label>
                <input type="text" id="rol" name="rol" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Guardar</button>
                <a href="listar_usuarios.php" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>
</body>

</html>