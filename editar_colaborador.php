<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_colaborador'];
    $nombre = strtoupper(trim($_POST['nombre']));

    if (!empty($id) && !empty($nombre)) {
        $stmt = $pdo->prepare(
            "UPDATE colaboradores 
             SET nombre = ? 
             WHERE id_colaborador = ?"
        );
        $stmt->execute([$nombre, $id]);
    }

    header("Location: listar_colaboradores.php");
    exit();
}
