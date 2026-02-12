<?php
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Limpiar buffers
ob_clean();
ob_start();

session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'ADMIN' && $_SESSION['rol'] !== 'USUARIO')) {
    die("Acceso no autorizado");
}

try {
    // Consulta para obtener todos los usuarios
    $sql = "SELECT * FROM usuarios ORDER BY usuario ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($usuarios)) {
        die("No hay usuarios registrados en el sistema");
    }

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $encabezados = ['ID', 'Usuario', 'Contraseña', 'Rol'];
    $sheet->fromArray($encabezados, null, 'A1');

    // Datos
    $row = 2;
    foreach ($usuarios as $usuario) {
        $sheet->fromArray([
            $usuario['id_usuario'],
            $usuario['usuario'],
            $usuario['contrasenia'],
            $usuario['rol']
        ], null, "A{$row}");
        $row++;
    }

    // Autoajustar columnas
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Estilo para encabezados
    $sheet->getStyle('A1:D1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ]
    ]);

    // Configurar cabeceras para descarga
    $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Generar archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}
