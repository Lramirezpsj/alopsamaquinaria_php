<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar si el usuario es admin
$esAdmin = ($_SESSION['rol'] === 'ADMIN');

// Eliminar un suministro
if ($esAdmin && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM suministros WHERE id_suministro = :id");
        $stmt->execute(['id' => $id]);
        header("Location: listar_suministros.php");
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}

?>
