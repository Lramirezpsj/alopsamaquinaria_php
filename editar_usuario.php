<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_usuario'];
    $usuario = strtoupper(trim($_POST['usuario']));
    $contrasenia = strtoupper(trim($_POST['contrasenia']));
    $rol = strtoupper(trim($_POST['rol']));

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET usuario = ?, contrasenia = ?, rol = ? WHERE id_usuario = ?");
        $stmt->execute([$usuario, $contrasenia, $rol, $id]);
        header("Location: listar_usuarios.php");
        exit();
    } catch (PDOException $e) {
        die("Error al actualizar el usuario: " . $e->getMessage());
    }
}
?>