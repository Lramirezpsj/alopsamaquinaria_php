<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper(trim($_POST['nombre']));
    $precio = $_POST['precio'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO alimentos (nombre, precio) VALUES (?, ?)");
        $stmt->execute([$nombre, $precio]);
        header("Location: listar_alimentos.php");
        exit();
    } catch (PDOException $e) {
        die("Error al crear el alimento: " . $e->getMessage());
    }
}
?>