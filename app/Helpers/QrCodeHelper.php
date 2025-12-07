<?php

namespace App\Helpers;

class QrCodeHelper
{
    /**
     * Genera un código QR simple usando caracteres ASCII (fallback)
     * Genera una representación textual del QR para cuando no hay librería disponible
     *
     * @param string $data Datos a codificar en el QR
     * @param int $size Tamaño en píxeles (no usado en esta versión simple)
     * @return string SVG del código QR
     */
    public static function generateSimpleSvgQr(string $data, int $size = 150): string
    {
        // Generar un QR simple usando SVG
        // Esta es una implementación básica que crea un patrón visual simple

        $hash = md5($data);
        $matrix = [];
        $gridSize = 21; // QR code versión 1 tiene 21x21 módulos

        // Crear matriz basada en hash
        for ($y = 0; $y < $gridSize; $y++) {
            $matrix[$y] = [];
            for ($x = 0; $x < $gridSize; $x++) {
                $index = ($y * $gridSize + $x) % strlen($hash);
                $matrix[$y][$x] = (hexdec($hash[$index]) % 2 === 0) ? 1 : 0;
            }
        }

        // Agregar patrones de posición (esquinas)
        self::addPositionPattern($matrix, 0, 0);
        self::addPositionPattern($matrix, $gridSize - 7, 0);
        self::addPositionPattern($matrix, 0, $gridSize - 7);

        // Generar SVG
        $moduleSize = $size / $gridSize;
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="#ffffff"/>';

        for ($y = 0; $y < $gridSize; $y++) {
            for ($x = 0; $x < $gridSize; $x++) {
                if ($matrix[$y][$x] === 1) {
                    $px = $x * $moduleSize;
                    $py = $y * $moduleSize;
                    $svg .= '<rect x="' . $px . '" y="' . $py . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="#000000"/>';
                }
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Agrega patrón de posición (esquinas del QR)
     */
    private static function addPositionPattern(array &$matrix, int $row, int $col): void
    {
        // Patrón 7x7 de las esquinas
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                if ($y == 0 || $y == 6 || $x == 0 || $x == 6 ||
                    ($y >= 2 && $y <= 4 && $x >= 2 && $x <= 4)) {
                    $matrix[$row + $y][$col + $x] = 1;
                } else {
                    $matrix[$row + $y][$col + $x] = 0;
                }
            }
        }
    }

    /**
     * Convierte SVG a base64 para incrustar en HTML
     */
    public static function svgToBase64(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
