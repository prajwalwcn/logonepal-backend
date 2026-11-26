<?php

namespace App\Services\Design;

/**
 * Minimal, dependency-free QR Code encoder.
 *
 * Byte mode, error-correction level M, versions 1–6 (21×21 to 41×41 modules).
 * Returns a plain 0/1 matrix without the quiet zone so the design composer can
 * draw crisp, scannable modules as SVG paths. Content that does not fit in
 * version 6 returns null and the caller falls back to decorative artwork.
 */
final class QrCodeMatrix
{
    /**
     * @var array<int, array{size: int, data: int, ec: int, blocks: int, align: array<int, int>}>
     */
    private const VERSIONS = [
        1 => ['size' => 21, 'data' => 16, 'ec' => 10, 'blocks' => 1, 'align' => []],
        2 => ['size' => 25, 'data' => 28, 'ec' => 16, 'blocks' => 1, 'align' => [6, 18]],
        3 => ['size' => 29, 'data' => 44, 'ec' => 26, 'blocks' => 1, 'align' => [6, 22]],
        4 => ['size' => 33, 'data' => 64, 'ec' => 36, 'blocks' => 2, 'align' => [6, 26]],
        5 => ['size' => 37, 'data' => 86, 'ec' => 48, 'blocks' => 2, 'align' => [6, 30]],
        6 => ['size' => 41, 'data' => 108, 'ec' => 64, 'blocks' => 4, 'align' => [6, 34]],
    ];

    /** @var array<int, int>|null */
    private static ?array $exp = null;

    /** @var array<int, int>|null */
    private static ?array $log = null;

    /** @var array<int, array<int, int>> */
    private static array $generators = [];

    /**
     * @return array<int, array<int, int>>|null
     */
    public static function encode(string $content): ?array
    {
        $length = strlen($content);

        $version = null;
        $spec = null;

        foreach (self::VERSIONS as $candidate => $candidateSpec) {
            if (4 + 8 + $length * 8 <= $candidateSpec['data'] * 8) {
                $version = $candidate;
                $spec = $candidateSpec;
                break;
            }
        }

        if ($version === null || $spec === null) {
            return null;
        }

        $bits = [];
        self::appendBits($bits, 0b0100, 4);
        self::appendBits($bits, $length, 8);

        for ($i = 0; $i < $length; $i++) {
            self::appendBits($bits, ord($content[$i]), 8);
        }

        $capacity = $spec['data'] * 8;
        $terminator = min(4, max(0, $capacity - count($bits)));

        for ($i = 0; $i < $terminator; $i++) {
            $bits[] = 0;
        }

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];

        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;

            for ($b = 0; $b < 8; $b++) {
                $byte = ($byte << 1) | $bits[$i + $b];
            }

            $codewords[] = $byte;
        }

        $pad = [0xEC, 0x11];
        $padIndex = 0;

        while (count($codewords) < $spec['data']) {
            $codewords[] = $pad[$padIndex % 2];
            $padIndex++;
        }

        $blockSize = intdiv($spec['data'], $spec['blocks']);
        $ecPerBlock = intdiv($spec['ec'], $spec['blocks']);
        $dataBlocks = array_chunk($codewords, $blockSize);
        $ecBlocks = [];

        foreach ($dataBlocks as $block) {
            $ecBlocks[] = self::reedSolomon($block, $ecPerBlock);
        }

        $interleaved = [];

        for ($i = 0; $i < $blockSize; $i++) {
            foreach ($dataBlocks as $block) {
                $interleaved[] = $block[$i];
            }
        }

        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                $interleaved[] = $block[$i];
            }
        }

        $dataBits = [];

        foreach ($interleaved as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $dataBits[] = ($byte >> $b) & 1;
            }
        }

        return self::buildMatrix($spec, $dataBits);
    }

    /**
     * @param  array{size: int, data: int, ec: int, blocks: int, align: array<int, int>}  $spec
     * @param  array<int, int>  $dataBits
     * @return array<int, array<int, int>>
     */
    private static function buildMatrix(array $spec, array $dataBits): array
    {
        $size = $spec['size'];
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        $function = array_fill(0, $size, array_fill(0, $size, false));

        $set = function (int $row, int $col, int $value) use (&$matrix, &$function, $size): void {
            if ($row < 0 || $row >= $size || $col < 0 || $col >= $size) {
                return;
            }

            $matrix[$row][$col] = $value;
            $function[$row][$col] = true;
        };

        $reserve = function (int $row, int $col) use (&$function, $size): void {
            if ($row >= 0 && $row < $size && $col >= 0 && $col < $size) {
                $function[$row][$col] = true;
            }
        };

        foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$finderRow, $finderCol]) {
            for ($dr = -1; $dr <= 7; $dr++) {
                for ($dc = -1; $dc <= 7; $dc++) {
                    $inside = $dr >= 0 && $dr < 7 && $dc >= 0 && $dc < 7;
                    $distance = max(abs($dr - 3), abs($dc - 3));
                    $dark = $inside && ($distance === 3 || $distance <= 1);

                    $set($finderRow + $dr, $finderCol + $dc, $dark ? 1 : 0);
                }
            }
        }

        for ($i = 8; $i < $size - 8; $i++) {
            $set(6, $i, $i % 2 === 0 ? 1 : 0);
            $set($i, 6, $i % 2 === 0 ? 1 : 0);
        }

        foreach ($spec['align'] as $centerRow) {
            foreach ($spec['align'] as $centerCol) {
                if (($centerRow <= 8 && $centerCol <= 8)
                    || ($centerRow <= 8 && $centerCol >= $size - 9)
                    || ($centerRow >= $size - 9 && $centerCol <= 8)) {
                    continue;
                }

                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $distance = max(abs($dr), abs($dc));
                        $set($centerRow + $dr, $centerCol + $dc, $distance === 1 ? 0 : 1);
                    }
                }
            }
        }

        for ($i = 0; $i <= 8; $i++) {
            $reserve(8, $i);
            $reserve($i, 8);
        }

        for ($i = 0; $i < 8; $i++) {
            $reserve(8, $size - 1 - $i);
        }

        for ($i = 0; $i < 7; $i++) {
            $reserve($size - 1 - $i, 8);
        }

        $set($size - 8, 8, 1);

        $bitIndex = 0;
        $row = $size - 1;
        $direction = -1;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }

            while (true) {
                for ($offset = 0; $offset < 2; $offset++) {
                    $currentCol = $col - $offset;

                    if (! $function[$row][$currentCol]) {
                        $bit = $dataBits[$bitIndex] ?? 0;

                        if (($row + $currentCol) % 2 === 0) {
                            $bit ^= 1;
                        }

                        $matrix[$row][$currentCol] = $bit;
                        $bitIndex++;
                    }
                }

                $row += $direction;

                if ($row < 0 || $row >= $size) {
                    $row -= $direction;
                    $direction = -$direction;
                    break;
                }
            }
        }

        $format = self::formatBits();
        $bit = static fn (int $index): int => ($format >> $index) & 1;

        for ($i = 0; $i <= 5; $i++) {
            $matrix[$i][8] = $bit($i);
        }

        $matrix[7][8] = $bit(6);
        $matrix[8][8] = $bit(7);
        $matrix[8][7] = $bit(8);

        for ($i = 9; $i < 15; $i++) {
            $matrix[8][14 - $i] = $bit($i);
        }

        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 1 - $i] = $bit($i);
        }

        for ($i = 8; $i < 15; $i++) {
            $matrix[$size - 15 + $i][8] = $bit($i);
        }

        $matrix[$size - 8][8] = 1;

        return $matrix;
    }

    private static function formatBits(): int
    {
        // Error-correction level M (00) with mask pattern 0.
        $data = 0;
        $remainder = $data;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }

        return (($data << 10) | ($remainder & 0x3FF)) ^ 0x5412;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private static function reedSolomon(array $data, int $degree): array
    {
        self::bootGalois();

        $generator = self::$generators[$degree] ??= self::generator($degree);
        $remainder = array_fill(0, $degree, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;

            for ($i = 0; $i < $degree; $i++) {
                $remainder[$i] ^= self::multiply($generator[$i + 1], $factor);
            }
        }

        return $remainder;
    }

    /**
     * @return array<int, int>
     */
    private static function generator(int $degree): array
    {
        $polynomial = [1];

        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($polynomial) + 1, 0);
            $alpha = self::$exp[$i];

            foreach ($polynomial as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= self::multiply($coefficient, $alpha);
            }

            $polynomial = $next;
        }

        return $polynomial;
    }

    private static function bootGalois(): void
    {
        if (self::$exp !== null) {
            return;
        }

        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $value = 1;

        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $value;
            self::$log[$value] = $i;
            $value <<= 1;

            if ($value & 0x100) {
                $value ^= 0x11D;
            }
        }

        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function multiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /**
     * @param  array<int, int>  $bits
     */
    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }
}
