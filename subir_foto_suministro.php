<?php
require 'db.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_suministro'])) {
    header('Location: listar_suministros.php');
    exit();
}

$id = (int) $_POST['id_suministro'];

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    header('Location: listar_suministros.php');
    exit();
}

$file = $_FILES['foto'];
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    header('Location: listar_suministros.php');
    exit();
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
    header('Location: listar_suministros.php');
    exit();
}

$uploadsDir = __DIR__ . '/uploads/suministros';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$nombreArchivo = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destino = $uploadsDir . '/' . $nombreArchivo;

// Try to resize and save to reduce size
function resize_and_save_image($tmpPath, $destPath, $mime)
{
    list($origW, $origH) = getimagesize($tmpPath);
    if (!$origW || !$origH) return false;
    $maxDim = 500;
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

if (!resize_and_save_image($file['tmp_name'], $destino, $file['type'])) {
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        header('Location: listar_suministros.php');
        exit();
    }
}

// Eliminar foto anterior si existe
try {
    $stmt = $pdo->prepare('SELECT foto FROM suministros WHERE id_suministro = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['foto'])) {
        $antiguo = __DIR__ . '/uploads/suministros/' . $row['foto'];
        if (is_file($antiguo)) @unlink($antiguo);
    }

    $stmt = $pdo->prepare('UPDATE suministros SET foto = :foto WHERE id_suministro = :id');
    $stmt->execute([':foto' => $nombreArchivo, ':id' => $id]);
} catch (PDOException $e) {
    if (is_file($destino)) @unlink($destino);
}

header('Location: listar_suministros.php');
exit();

?>
