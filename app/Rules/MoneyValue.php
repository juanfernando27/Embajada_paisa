<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MoneyValue implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value) || is_bool($value) || ! preg_match('/^\d{1,8}([.,]\d{1,2})?$/D', (string) $value)) {
            $fail('Ingresa un monto positivo sin separadores de miles y con máximo dos decimales.');
        }
    }
}
