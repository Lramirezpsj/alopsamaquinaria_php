<?php
require 'db.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Obtener las máquinas para el combo
$maquinas = $pdo->query("SELECT DISTINCT maquina FROM maquinas")->fetchAll(PDO::FETCH_ASSOC);

function numOrZero($value)
{
    return ($value === '' || $value === null) ? 0 : (float) $value;
}

// Procesar el formulario de creación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = $_POST['fecha'];
    $maquina = $_POST['maquina'];
    $horometro = $_POST['horometro'];
    $i_bomba = numOrZero($_POST['i_bomba'] ?? null);
    $f_bomba = numOrZero($_POST['f_bomba'] ?? null);
    $total = $_POST['total'];
    $comentarios = mb_strtoupper($_POST['comentarios'], 'UTF-8');
    $operador = $_SESSION['usuario']; // Usuario autenticado

    try {
        $stmt = $pdo->prepare("INSERT INTO suministros (fecha, maquina, horometro, i_bomba, f_bomba, total, comentarios, operador) 
                               VALUES (:fecha, :maquina, :horometro, :i_bomba, :f_bomba, :total, :comentarios, :operador)");
        $stmt->execute([
            'fecha' => $fecha,
            'maquina' => $maquina,
            'horometro' => $horometro,
            'i_bomba' => $i_bomba,
            'f_bomba' => $f_bomba,
            'total' => $total,
            'comentarios' => $comentarios,
            'operador' => $operador
        ]);
        header("Location: listar_suministros.php");
        exit();
    } catch (PDOException $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}


