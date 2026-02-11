<?php
function validarContenedor($contenedor)
{
    $contenedor = strtoupper(trim($contenedor));

    if (!preg_match('/^[A-Z]{4}[0-9]{7}$/', $contenedor)) {
        return false;
    }

    $letras = [
        'A' => 10,
        'B' => 12,
        'C' => 13,
        'D' => 14,
        'E' => 15,
        'F' => 16,
        'G' => 17,
        'H' => 18,
        'I' => 19,
        'J' => 20,
        'K' => 21,
        'L' => 23,
        'M' => 24,
        'N' => 25,
        'O' => 26,
        'P' => 27,
        'Q' => 28,
        'R' => 29,
        'S' => 30,
        'T' => 31,
        'U' => 32,
        'V' => 34,
        'W' => 35,
        'X' => 36,
        'Y' => 37,
        'Z' => 38
    ];

    $suma = 0;

    for ($i = 0; $i < 10; $i++) {
        $char = $contenedor[$i];
        $valor = ctype_alpha($char) ? $letras[$char] : intval($char);
        $suma
            += $valor * pow(2, $i);
    }
    $digito = $suma % 11;
    if ($digito == 10)
        $digito = 0;
    return $digito == intval($contenedor[10]);
}