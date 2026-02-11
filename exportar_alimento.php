<?php
require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ob_clean();
ob_start();

session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'ADMIN' && $_SESSION['rol'] !== 'USUARIO')) {
    die("Acceso denegado");
}

$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';

if (empty($fecha_inicio) || empty($fecha_fin)) {
    die("Debes especificar ambas fechas");
}

try {
    $sql = "SELECT p.id_pedido, p.fecha, c.nombre AS nombre_colaborador
            FROM pedidos p
            JOIN colaboradores c ON p.colaborador = c.id_colaborador
            WHERE p.fecha BETWEEN ? AND ?
            ORDER BY p.fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pedidos)) {
        die("No hay datos para el rango seleccionado");
    }

    // Obtener los alimentos de cada pedido
    foreach ($pedidos as &$pedido) {
        $stmtDetalles = $pdo->prepare("
            SELECT a.nombre, a.precio, dp.cantidad
            FROM detalle_pedidos dp
            JOIN alimentos a ON dp.id_alimento = a.id_alimento
            WHERE dp.id_pedido = ?
        ");
        $stmtDetalles->execute([$pedido['id_pedido']]);
        $pedido['alimentos'] = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($pedido);

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $sheet->setCellValue('A1', 'ID Pedido');
    $sheet->setCellValue('B1', 'Fecha');
    $sheet->setCellValue('C1', 'Colaborador');
    $sheet->setCellValue('D1', 'Alimento');
    $sheet->setCellValue('E1', 'Cantidad');
    $sheet->setCellValue('F1', 'Precio Unitario');
    $sheet->setCellValue('G1', 'Subtotal');

    $fila = 2;
    foreach ($pedidos as $pedido) {
        if (empty($pedido['alimentos'])) {
            $sheet->setCellValue("A{$fila}", $pedido['id_pedido']);
            $sheet->setCellValue("B{$fila}", $pedido['fecha']);
            $sheet->setCellValue("C{$fila}", $pedido['nombre_colaborador']);
            $sheet->setCellValue("D{$fila}", 'Sin alimentos');
            $fila++;
        } else {
            foreach ($pedido['alimentos'] as $alimento) {
                $subtotal = $alimento['precio'] * $alimento['cantidad'];

                $sheet->setCellValue("A{$fila}", $pedido['id_pedido']);
                $sheet->setCellValue("B{$fila}", $pedido['fecha']);
                $sheet->setCellValue("C{$fila}", $pedido['nombre_colaborador']);
                $sheet->setCellValue("D{$fila}", $alimento['nombre']);
                $sheet->setCellValue("E{$fila}", $alimento['cantidad']);
                $sheet->setCellValue("F{$fila}", $alimento['precio']);
                $sheet->setCellValue("G{$fila}", $subtotal);
                $fila++;
            }
        }
    }

    // Ajustar tamaño columnas
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $filename = 'reporte_alimentos_' . date('Y-m-d_His') . '.xlsx';

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
