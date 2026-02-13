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

    // Manejo de foto (opcional)
    $fotoFilename = null;
    function resize_and_save_image($tmpPath, $destPath, $mime)
    {
        list($origW, $origH) = getimagesize($tmpPath);
        if (!$origW || !$origH) return false;
        $maxDim = 500; // max width/height (reducido)
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
                // preserve alpha
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
        // Save according to mime with reasonable quality
        switch ($mime) {
            case 'image/jpeg':
                $ok = imagejpeg($dst, $destPath, 80);
                break;
            case 'image/png':
                // PNG compression level 6
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
            // resize and save
            if (!resize_and_save_image($file['tmp_name'], $destino, $file['type'])) {
                // fallback: move original
                move_uploaded_file($file['tmp_name'], $destino);
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO suministros (fecha, maquina, horometro, i_bomba, f_bomba, total, comentarios, operador, foto) 
                               VALUES (:fecha, :maquina, :horometro, :i_bomba, :f_bomba, :total, :comentarios, :operador, :foto)");
        $stmt->execute([
            'fecha' => $fecha,
            'maquina' => $maquina,
            'horometro' => $horometro,
            'i_bomba' => $i_bomba,
            'f_bomba' => $f_bomba,
            'total' => $total,
            'comentarios' => $comentarios,
            'operador' => $operador,
            'foto' => $fotoFilename
        ]);
        header("Location: listar_suministros.php");
        exit();
    } catch (PDOException $e) {
        die("Error al guardar: " . $e->getMessage());
    }
}


