<?php

if (! function_exists('user_initials')) {
    function user_initials(string $name, int $limit = 2): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn($word) => strtoupper(mb_substr($word, 0, 1)))
            ->take($limit)
            ->join('');
    }
}
