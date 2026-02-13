<?php
require 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no soportado']);
    exit();
}

// Accept form-data or JSON
$id = null;
if (isset($_POST['id_suministro'])) {
    $id = (int) $_POST['id_suministro'];
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!empty($data['id_suministro'])) $id = (int)$data['id_suministro'];
}

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no recibido']);
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT foto FROM suministros WHERE id_suministro = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['foto'])) {
        $archivo = __DIR__ . '/uploads/suministros/' . $row['foto'];
        if (is_file($archivo)) @unlink($archivo);

        $u = $pdo->prepare('UPDATE suministros SET foto = NULL WHERE id_suministro = :id');
        $u->execute([':id' => $id]);

        echo json_encode(['success' => true, 'foto' => $row['foto']]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'No existe foto']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
