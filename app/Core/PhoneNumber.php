<?php

declare(strict_types=1);

namespace App\Core;

final class PhoneNumber
{
    public static function normalize(string $value): string
    {
        $value = strtr(trim($value), array_combine(
            ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'],
            ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9']
        ));
        $value = preg_replace('/[\s().\-\x{200E}\x{200F}]+/u', '', $value) ?? '';
        if (str_starts_with($value, '0098')) $value = '0'.substr($value, 4);
        elseif (str_starts_with($value, '+98')) $value = '0'.substr($value, 3);
        elseif (str_starts_with($value, '98') && strlen($value) === 12) $value = '0'.substr($value, 2);
        return $value;
    }
}
