<?php
session_start();

// Verificar si NO hay sesión activa
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="dashboard.css"> <!-- Enlaza tu archivo CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <!-- Izquierda -->
        <span class="navbar-text">
            Bienvenido, <?= htmlspecialchars($_SESSION['usuario']); ?>
        </span>

        <!-- Derecha -->
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </nav>

    <!-- Contenido principal -->
    <div class="grid-container">
        <div class="grid-item">
            <i class="fas fa-gauge fa-2x mb-3 d-block"></i>
            <a href="listar.php" class="btn btn-secondary btn-lg">Horómetros</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-cube fa-2x mb-3 d-block"></i>
            <a href="listar_contenedores.php" class="btn btn-secondary btn-lg">Contenedores</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-gas-pump fa-2x mb-3 d-block"></i>
            <a href="listar_suministros.php" class="btn btn-secondary btn-lg">Suministros</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-clipboard-list fa-2x mb-3 d-block"></i>
            <a href="listar_pedidos.php" class="btn btn-secondary btn-lg">Pedidos</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-user fa-2x mb-3 d-block"></i>
            <a href="listar_colaboradores.php" class="btn btn-secondary btn-lg">Colaboradores</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-utensils fa-2x mb-3 d-block"></i>
            <a href="listar_alimentos.php" class="btn btn-secondary btn-lg">Alimentos</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-user-shield fa-2x mb-3 d-block"></i>
            <a href="listar_usuarios.php" class="btn btn-secondary btn-lg">Usuarios</a><br />
        </div>
        <div class="grid-item">
            <i class="fas fa-ruler-combined fa-2x mb-3 d-block"></i>
            <a href="listar_medidas.php" class="btn btn-secondary btn-lg">Medidas</a><br />
        </div>
    </div>
    </div>
</body>

</html>