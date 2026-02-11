<?php
require 'db.php';
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'];
$pdo->prepare("DELETE FROM alimentos WHERE id_alimento = ?")->execute([$id]);
header("Location: listar_alimentos.php");
?>