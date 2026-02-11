<?php
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Limpiar buffers
ob_clean();
ob_start();

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Validar rol
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'ADMIN' && $_SESSION['rol'] !== 'USUARIO')) {
    die("Acceso denegado");
}

// Fechas
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';

if (empty($fecha_inicio) || empty($fecha_fin)) {
    die("Debes especificar ambas fechas");
}

try {

    // Consulta
    $sql = "SELECT * FROM horometros
            WHERE fecha BETWEEN ? AND ?
            ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$datos) {
        die("No hay datos para el rango seleccionado");
    }

    // Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $sheet->fromArray([
        'ID',
        'Operador',
        'Fecha',
        'Máquina',
        'Cliente',
        'H. Inicio',
        'H. Final',
        'Total Horas',
        'Turno',
        'Comentarios'
    ], null, 'A1');

    // Datos
    $fila = 2;
    foreach ($datos as $reg) {

        // ✅ CALCULO CORRECTO PARA [hh]:mm
        $total_horas = ($reg['h_final'] - $reg['h_inicio']) / 24;

        $sheet->setCellValue("A{$fila}", $reg['id_horometro']);
        $sheet->setCellValue("B{$fila}", $reg['operador']);
        $sheet->setCellValue("C{$fila}", $reg['fecha']);
        $sheet->setCellValue("D{$fila}", $reg['maquina']);
        $sheet->setCellValue("E{$fila}", $reg['cliente']);
        $sheet->setCellValue("F{$fila}", $reg['h_inicio']);
        $sheet->setCellValue("G{$fila}", $reg['h_final']);
        $sheet->setCellValue("H{$fila}", $total_horas); // 👈 duración
        $sheet->setCellValue("I{$fila}", $reg['turno']);
        $sheet->setCellValue("J{$fila}", $reg['comentarios']);

        $fila++;
    }

    // Estilo para encabezados
    $sheet->getStyle('A1:J1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ]
    ]);

    // 👉 FORMATO DURACIÓN [hh]:mm
    $sheet->getStyle("H2:H" . ($fila - 1))
        ->getNumberFormat()
        ->setFormatCode('[hh]:mm');

    // Autoajustar columnas
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Salida   
    $writer = new Xlsx($spreadsheet);
    $filename = 'reporte_horometros_' . date('Y-m-d_His') . '.xlsx';

    ob_end_clean();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

