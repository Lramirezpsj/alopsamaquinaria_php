<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_suministros.php");
    exit();
}

function numOrZero($value)
{
    return ($value === '' || $value === null) ? 0 : (float) $value;
}

$id = $_POST['id_suministro'] ?? null;

if (!$id) {
    die("ID de suministro no recibido");
}

$stmt = $pdo->prepare("
    UPDATE suministros SET
        fecha = :fecha,
        maquina = :maquina,
        horometro = :horometro,
        i_bomba = :i_bomba,
        f_bomba = :f_bomba,
        total = :total,
        comentarios = :comentarios,
        operador = :operador
    WHERE id_suministro = :id
");

$stmt->execute([
    ':fecha' => $_POST['fecha'],
    ':maquina' => $_POST['maquina'],
    ':horometro' => $_POST['horometro'],
    ':i_bomba' => numOrZero($_POST['i_bomba'] ?? null),
    ':f_bomba' => numOrZero($_POST['f_bomba'] ?? null),
    ':total' => $_POST['total'],
    ':comentarios' => mb_strtoupper($_POST['comentarios'], 'UTF-8'),
    ':operador' => $_POST['operador'],
    ':id' => $id
]);

header("Location: listar_suministros.php");
exit();
