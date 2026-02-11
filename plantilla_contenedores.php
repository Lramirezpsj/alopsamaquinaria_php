<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$sheet->setCellValue('A1', 'fecha');
$sheet->setCellValue('B1', 'maquina');
$sheet->setCellValue('C1', 'contenedor');
$sheet->setCellValue('D1', 'medida');
$sheet->setCellValue('E1', 'movimiento');
$sheet->setCellValue('F1', 'colaborador');
$sheet->setCellValue('G1', 'comentarios');
$sheet->setCellValue('H1', 'usuario');

// Ejemplo (opcional, pero recomendado)
$sheet->setCellValue('A2', '2026-01-01');
$sheet->setCellValue('B2', 'drt1');
$sheet->setCellValue('C2', 'msku123456');
$sheet->setCellValue('D2', '20std');
$sheet->setCellValue('E2', 'ingreso');
$sheet->setCellValue('F2', 'miguel batres');
$sheet->setCellValue('G2', 'turno 1');
$sheet->setCellValue('H2', 'lramirez');

// Autoajustar columnas
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar
$nombreArchivo = 'plantilla_contenedores.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;