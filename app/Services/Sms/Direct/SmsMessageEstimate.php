<?php

namespace App\Services\Sms\Direct;

class SmsMessageEstimate
{
    public static function for(string $message): array
    {
        $basic = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        $extended = "\f^{}\\[~]|€";
        $units = 0;
        $unicode = false;
        foreach (mb_str_split($message) as $char) {
            if (mb_strpos($basic, $char) !== false) {
                $units++;
            } elseif (mb_strpos($extended, $char) !== false) {
                $units += 2;
            } else {
                $unicode = true;
                break;
            }
        }
        if ($unicode) {
            $units = intdiv(strlen(mb_convert_encoding($message, 'UTF-16BE', 'UTF-8')), 2);
        }
        $single = $unicode ? 70 : 160;
        $multi = $unicode ? 67 : 153;

        return ['encoding' => $unicode ? 'Unicode' : 'GSM-7', 'units' => $units, 'segments' => $units ? ($units <= $single ? 1 : (int) ceil($units / $multi)) : 0];
    }
}
