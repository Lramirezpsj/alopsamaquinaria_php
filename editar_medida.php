<?php
session_start();

require __DIR__ . '/db.php';

//POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_medidas.php");
    exit();
}

// Validar ID
if (!isset($_POST['id_medida']) || !is_numeric($_POST['id_medida'])) {
    die("ID medida invalido");
}

$id = (int) $_POST['id_medida'];

// Actualizar

$sql = "UPDATE medidas SET medida=? WHERE id_medida = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    strtoupper(trim($_POST['medida'] ?? null)),
    $id
]);

header("Location: listar_medidas.php");
exit();