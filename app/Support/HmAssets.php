<?php

namespace App\Support;

class HmAssets
{
    public static function version(): string
    {
        return (string) config('hm.version', '1.0.0');
    }
}
