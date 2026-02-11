<?php
require 'db.php';

// iniciar sesion y verificar que el usuario esté autenticado

session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// validar que el id se pase un ID valido

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID del usuario no es valido.");
}

$id = $_GET['id'];

// Eliminar colaborador
try {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
    $stmt->execute(['id' => $id]);
    header("location: listar_usuarios.php");
    exit();
} catch (PDOException $e) {
    die("Error al eliminar." . $e->getMessage());
}
?>