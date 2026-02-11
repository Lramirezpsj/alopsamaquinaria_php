<?php
session_start();
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Solo admins (opcional pero recomendado)
/*if ($_SESSION['rol'] !== 'admin') {
    die('Acceso denegado');
}*/

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== 0) {
        $mensaje = 'Error al subir el archivo';
    } else {

        $archivo = $_FILES['archivo']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $filas = $hoja->toArray();

            $pdo->beginTransaction();

            $sql = "INSERT INTO contenedores
            (fecha, maquina, contenedor, medida, movimiento, colaborador, comentarios, operador)
            VALUES (?,?,?,?,?,?,?,?)";

            $stmt = $pdo->prepare($sql);

            $insertados = 0;

            // Empezamos en 1 para saltar encabezados
            for ($i = 1; $i < count($filas); $i++) {

                // Evitar filas vacías
                if (empty($filas[$i][0])) {
                    continue;
                }

                $stmt->execute([
                    $filas[$i][0], // fecha
                    strtoupper(trim($filas[$i][1])), // maquina
                    strtoupper(trim($filas[$i][2])), // contenedor
                    strtoupper(trim($filas[$i][3])), // medida
                    strtoupper(trim($filas[$i][4])), // movimiento
                    strtoupper(trim($filas[$i][5])), // colaborador
                    strtoupper(trim($filas[$i][6])), // comentarios
                    strtoupper(trim($filas[$i][7]))  // operador
                ]);

                $insertados++;
            }

            $pdo->commit();

            $mensaje = "✅ Importación completada. Registros insertados: $insertados";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importar Contenedores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .box {
            max-width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        h2 {
            margin-top: 0;
            text-align: center;
        }

        input[type="file"] {
            width: 100%;
            margin: 15px 0;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #256428;
        }

        .mensaje {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 15px;
        }

        .btn-plantilla {
            display: block;
            text-align: center;
            margin-bottom: 15px;
            padding: 8px;
            background: #1976d2;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-plantilla:hover {
            background: #125ca1;
        }
    </style>
</head>

<body>

    <div class="box">
        <h2>📥 Importar Contenedores</h2>

        <a href="plantilla_contenedores.php" class="btn-plantilla">
            📄 Descargar plantilla Excel
        </a>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".xlsx,.xls" required>
            <button type="submit">Importar Excel</button>
        </form>

        <?php if ($mensaje): ?>
            <div class="mensaje"><?= $mensaje ?></div>
        <?php endif; ?>

        <a class="volver" href="listar_contenedores.php">⬅ Volver</a>
    </div>

</body>

</html>