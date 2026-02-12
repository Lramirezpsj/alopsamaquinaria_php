<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_alimento'];
    $nombre = strtoupper(trim($_POST['nombre']));
    $precio = $_POST['precio'];
    
    try {
        $stmt = $pdo->prepare("UPDATE alimentos SET nombre = ?, precio = ? WHERE id_alimento = ?");
        $stmt->execute([$nombre, $precio, $id]);
        header("Location: listar_alimentos.php");
        exit();
    } catch (PDOException $e) {
        die("Error al actualizar el alimento: " . $e->getMessage());
    }
}
?>