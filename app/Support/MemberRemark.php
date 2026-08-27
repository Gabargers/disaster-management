<?php

namespace App\Support;

class MemberRemark
{
    public const OPTIONS = [
        'ELDERLY' => 'Elderly',
        'PWD' => 'Person with disability',
        'PREG' => 'Pregnant women',
        'LM' => 'Lactating women',
        'SP' => 'Solo parent',
        '4PS' => '4PS',
        'INFANT' => 'Infant',
        'CHILD' => 'Child',
    ];

    private const LEGACY = ['A'=>'ELDERLY','B'=>'PWD','C'=>'INFANT','D'=>'PREG','E'=>'LM','F'=>'CHILD'];

    public static function normalize(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') return null;
        return self::LEGACY[$code] ?? $code;
    }

    public static function label(?string $code): string
    {
        $normalized = self::normalize($code);
        return self::OPTIONS[$normalized] ?? ($normalized ?: 'None');
    }
}
