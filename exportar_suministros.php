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
    // Consulta con filtro por fechas
    $sql = "SELECT * FROM suministros 
            WHERE fecha BETWEEN ? AND ?
            ORDER BY fecha DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $suministros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($suministros)) {
        die("No hay registros para el rango de fechas seleccionado");
    }

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $encabezados = ['ID', 'Operador', 'Fecha', 'Máquina', 'Horometro', 'I_bomba', 'F_bomba', 'Total', 'Comentarios'];
    $sheet->fromArray($encabezados, null, 'A1');

    // Datos
    $row = 2;
    foreach ($suministros as $cont) {
        $sheet->fromArray([
            $cont['id_suministro'],
            $cont['operador'],
            $cont['fecha'],
            $cont['maquina'],
            $cont['horometro'],
            $cont['i_bomba'],
            $cont['f_bomba'],
            $cont['total'],
            $cont['comentarios']
        ], null, "A{$row}");
        $row++;
    }

    // Autoajustar columnas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Estilo para encabezados
    $sheet->getStyle('A1:I1')->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ]
    ]);

    // Configurar cabeceras para descarga
    $filename = 'suministros_' . date('Y-m-d_His') . '.xlsx';

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
