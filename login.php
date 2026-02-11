<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = strtolower(trim($_POST['usuario']));
    $contrasenia = trim($_POST['contrasenia']);

    // Convertimos el usuario a minúsculas en la consulta
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(usuario) = LOWER(?) AND contrasenia = ?");
    $stmt->execute([$usuario, $contrasenia]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="login.css"> <!-- Enlaza tu archivo CSS -->
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Iniciar Sesión</h2>
            <?php if (isset($error)): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <div class="form-group">
                    <label for="contrasenia">Contraseña:</label>
                    <input type="password" id="contrasenia" name="contrasenia" required>
                </div>
                <button type="submit" class="btn-login">Entrar</button>
            </form>
        </div>
    </div>
</body>

</html>