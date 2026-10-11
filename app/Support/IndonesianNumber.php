<?php

namespace App\Support;

class IndonesianNumber
{
    public static function rupiah(float $amount): string
    {
        $cents = (int) round($amount * 100);
        $whole = intdiv($cents, 100);
        $fraction = $cents % 100;
        return ucfirst(static::words($whole)).' rupiah'.($fraction ? ' dan '.static::words($fraction).' sen' : '');
    }

    private static function words(int $number): string
    {
        $small = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($number < 12) return $small[$number];
        if ($number < 20) return static::words($number - 10).' belas';
        if ($number < 100) return static::words(intdiv($number, 10)).' puluh'.($number % 10 ? ' '.static::words($number % 10) : '');
        if ($number < 200) return 'seratus'.($number % 100 ? ' '.static::words($number % 100) : '');
        if ($number < 1000) return static::words(intdiv($number, 100)).' ratus'.($number % 100 ? ' '.static::words($number % 100) : '');
        if ($number < 2000) return 'seribu'.($number % 1000 ? ' '.static::words($number % 1000) : '');
        foreach ([1000000000000 => 'triliun', 1000000000 => 'miliar', 1000000 => 'juta', 1000 => 'ribu'] as $unit => $name) {
            if ($number >= $unit) return static::words(intdiv($number, $unit)).' '.$name.($number % $unit ? ' '.static::words($number % $unit) : '');
        }
        return '';
    }
}
