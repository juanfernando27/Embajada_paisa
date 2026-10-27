<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Money
{
    public static function parse(mixed $v): int
    {
        if (! is_scalar($v) || is_bool($v) || ! preg_match('/^\d{1,8}([.,]\d{1,2})?$/D', (string) $v)) {
            throw ValidationException::withMessages(['monto' => 'Ingresa un monto positivo, sin separadores de miles y con máximo dos decimales.']);
        }
        $p = explode('.', str_replace(',', '.', (string) $v));

        return (int) $p[0] * 100 + (int) str_pad($p[1] ?? '', 2, '0');
    }

    public static function format(int $v): string
    {
        return '$ '.number_format($v / 100, $v % 100 === 0 ? 0 : 2, ',', '.');
    }

    public static function input(int $v): string
    {
        return intdiv($v, 100).'.'.str_pad((string) ($v % 100), 2, '0', STR_PAD_LEFT);
    }
}
