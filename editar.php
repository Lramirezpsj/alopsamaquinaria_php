<?php
session_start();
require 'db.php';

/* 1. SOLO POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar.php");
    exit();
}

/* 2. VALIDAR ID */
if (!isset($_POST['id_horometro']) || !is_numeric($_POST['id_horometro'])) {
    die("ID horómetro inválido");
}

$id = (int) $_POST['id_horometro'];

/* 3. OPERADOR SEGURO */
if ($_SESSION['rol'] === 'ADMIN' && !empty($_POST['operador'])) {
    $operador = $_POST['operador'];
} else {
    $operador = $_SESSION['usuario'];
}

/* 4. UPDATE */
$sql = "UPDATE horometros SET
        fecha = ?,
        maquina = ?,
        cliente = ?,
        h_inicio = ?,
        h_final = ?,
        turno = ?,
        comentarios = ?,
        operador = ?
        WHERE id_horometro = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_POST['fecha'],
    $_POST['maquina'],
    $_POST['cliente'],
    $_POST['h_inicio'],
    $_POST['h_final'] !== '' ? $_POST['h_final'] : null,
    $_POST['turno'],
    mb_strtoupper($_POST['comentarios'] ?? 'UTF-8'),
    strtoupper($_POST['operador']), //
    $id
]);

header("Location: listar.php");
exit();
