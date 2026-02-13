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

// Manejo de foto (opcional)
$fotoFilename = null;
function resize_and_save_image($tmpPath, $destPath, $mime)
{
    list($origW, $origH) = getimagesize($tmpPath);
    if (!$origW || !$origH) return false;
    $maxDim = 500; // reducido a ~500px
    $ratio = min($maxDim / $origW, $maxDim / $origH, 1);
    $newW = (int)($origW * $ratio);
    $newH = (int)($origH * $ratio);

    $dst = imagecreatetruecolor($newW, $newH);
    if (!$dst) return false;

    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($tmpPath);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $src = imagecreatefromwebp($tmpPath);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }

    if (!$src) return false;

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $ok = false;
    switch ($mime) {
        case 'image/jpeg':
            $ok = imagejpeg($dst, $destPath, 80);
            break;
        case 'image/png':
            $ok = imagepng($dst, $destPath, 6);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) $ok = imagewebp($dst, $destPath, 80);
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}

if (isset($_FILES['foto']) && isset($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($file['type'], $allowed) && $file['size'] <= 8 * 1024 * 1024) {
        $uploadsDir = __DIR__ . '/uploads/suministros';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fotoFilename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destino = $uploadsDir . '/' . $fotoFilename;
        if (!resize_and_save_image($file['tmp_name'], $destino, $file['type'])) {
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                // proceed
            } else {
                $fotoFilename = null;
            }
        }

        if ($fotoFilename) {
            // eliminar foto anterior
            try {
                $stmtOld = $pdo->prepare('SELECT foto FROM suministros WHERE id_suministro = :id');
                $stmtOld->execute([':id' => $id]);
                $row = $stmtOld->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['foto'])) {
                    $antiguo = __DIR__ . '/uploads/suministros/' . $row['foto'];
                    if (is_file($antiguo)) @unlink($antiguo);
                }
            } catch (PDOException $e) {
                // ignore
            }
        }
    }
}

$sql = "UPDATE suministros SET
        fecha = :fecha,
        maquina = :maquina,
        horometro = :horometro,
        i_bomba = :i_bomba,
        f_bomba = :f_bomba,
        total = :total,
        comentarios = :comentarios,
        operador = :operador";
if ($fotoFilename !== null) {
    $sql .= ", foto = :foto";
}
$sql .= " WHERE id_suministro = :id";

/* 3. OPERADOR SEGURO */
if ($_SESSION['rol'] === 'ADMIN' && !empty($_POST['operador'])) {
    $operador = $_POST['operador'];
} else {
    $operador = $_SESSION['usuario'];
}

$params = [
    ':fecha' => $_POST['fecha'],
    ':maquina' => $_POST['maquina'],
    ':horometro' => $_POST['horometro'],
    ':i_bomba' => numOrZero($_POST['i_bomba'] ?? null),
    ':f_bomba' => numOrZero($_POST['f_bomba'] ?? null),
    ':total' => $_POST['total'],
    ':comentarios' => mb_strtoupper($_POST['comentarios'], 'UTF-8'),
    ':operador' => strtoupper($operador),
    ':id' => $id
];
if ($fotoFilename !== null) $params[':foto'] = $fotoFilename;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header("Location: listar_suministros.php");
exit();
