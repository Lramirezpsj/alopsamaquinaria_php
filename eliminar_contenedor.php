<?php
// 1. Iniciar sesión
session_start();

// 2. Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// 3. Verificar rol ADMIN
if ($_SESSION['rol'] !== 'ADMIN') {
    header("Location: listar_contenedores.php?error=permiso");
    exit();
}

// 4. Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listar_contenedores.php?error=id");
    exit();
}

$id = (int) $_GET['id'];

// 5. Conexión DB
require __DIR__ . '/db.php';

try {
    $sql = "DELETE FROM contenedores WHERE id_contenedor = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

} catch (PDOException $e) {
    // En producción podrías loguear el error
    header("Location: listar_contenedores.php?error=db");
    exit();
}

// 6. Redirigir
header("Location: listar_contenedores.php?ok=eliminado");
exit();

