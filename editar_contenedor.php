<?php
session_start();
require 'db.php';

$sql = "UPDATE contenedores SET
fecha=?,
maquina=?,
contenedor=?,
medida=?,
movimiento=?,
colaborador=?,
comentarios=?,
operador=?
WHERE id_contenedor=?";

/* OPERADOR SEGURO */
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'ADMIN' && !empty($_POST['operador'])) {
    $operador = $_POST['operador'];
} else {
    $operador = $_SESSION['usuario'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $_POST['fecha'],
    $_POST['maquina'],
    strtoupper(trim($_POST['contenedor'])),
    $_POST['medida'],
    $_POST['movimiento'],
    $_POST['colaborador'],
    mb_strtoupper($_POST['comentarios'] ?? 'UTF-8'),
    strtoupper($operador), 
    $_POST['id_contenedor']
]);

header("Location: listar_contenedores.php");
