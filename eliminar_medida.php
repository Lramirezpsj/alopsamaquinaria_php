<?php
require __DIR__ . '/db.php';

// Iniciar sesion y verificar usuario
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['rol'] !== 'ADMIN') {
    die("No tienes permisos para eliminar.");
}

// verificar que el id sea valido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("El ID de medida no es valido.");
}

$id = $_GET['id'];

// Eliminar registro
try {
    $stmt = $pdo->prepare("DELETE FROM medidas WHERE id_medida = :id");
    $stmt->execute(['id' => $id]);
    header("Location: listar_medidas.php");
    exit();
} catch (PDOException $e) {
    die("Error al eliminar: " . $e->getMessage());
}