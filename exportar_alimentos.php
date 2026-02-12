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

// Obtener fechas del formulario
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';

// Validar fechas
if (empty($fecha_inicio) || empty($fecha_fin)) {
    die("Debes especificar ambas fechas");
}

try {
    // Consulta para obtener todos los alimentos
    $sql = "SELECT * FROM alimentos ORDER BY nombre ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $alimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($alimentos)) {
        die("No hay alimentos registrados en el sistema");
    }

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $encabezados = ['ID', 'Nombre', 'Precio'];
    $sheet->fromArray($encabezados, null, 'A1');

    // Datos
    $row = 2;
    foreach ($alimentos as $alimento) {
        $sheet->fromArray([
            $alimento['id_alimento'],
            $alimento['nombre'],
            'Q' . number_format($alimento['precio'], 2)
        ], null, "A{$row}");
        $row++;
    }

    // Autoajustar columnas
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Estilo para encabezados
    $sheet->getStyle('A1:C1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ]
    ]);

    // Configurar cabeceras para descarga
    $filename = 'alimentos_' . date('Y-m-d_His') . '.xlsx';

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
