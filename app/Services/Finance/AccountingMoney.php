<?php

namespace App\Services\Finance;

use Illuminate\Validation\ValidationException;

class AccountingMoney
{
    public static function cents($value): int
    {
        $value = (string) ($value ?? '0');
        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['lines' => 'Nilai harus angka nonnegatif, maksimal 12 digit dan 2 angka desimal. Gunakan titik untuk desimal.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad(abs($cents) % 100, 2, '0', STR_PAD_LEFT);
    }

    public static function format(int $cents): string
    {
        $amount = number_format(intdiv(abs($cents), 100), 0, ',', '.').','.str_pad(abs($cents) % 100, 2, '0', STR_PAD_LEFT);
        return $cents < 0 ? '(Rp '.$amount.')' : 'Rp '.$amount;
    }
}
