<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = strtoupper(trim($_POST['usuario']));
    $contrasenia = strtoupper(trim($_POST['contrasenia']));
    $rol = strtoupper(trim($_POST['rol']));

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, contrasenia, rol) VALUES (?, ?, ?)");
        $stmt->execute([$usuario, $contrasenia, $rol]);
        header("Location: listar_usuarios.php");
        exit();
    } catch (PDOException $e) {
        die("Error al crear el usuario: " . $e->getMessage());
    }
}
?>