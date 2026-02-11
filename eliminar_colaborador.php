<?php
require 'db.php';
// Iniciar sesión y verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
// Validar que se pase un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de contenedor no válido.");
}

$id = $_GET['id'];

// Eliminar el colaborador
try {
    $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id_colaborador = :id");
    $stmt->execute(['id' => $id]);
    header("Location: listar_colaboradores.php");
    exit();
} catch (PDOException $e) {
    die("Error al eliminar: " . $e->getMessage());
}
?>