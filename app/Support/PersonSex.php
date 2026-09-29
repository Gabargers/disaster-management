<?php

namespace App\Support;

class PersonSex
{
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return match (mb_strtolower(trim($value))) {
            'm', 'male', 'lalaki' => 'Male',
            'f', 'female', 'babae' => 'Female',
            default => null,
        };
    }

    public static function normalizeOrPreserve(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return self::normalize($value) ?? trim($value);
    }
}
