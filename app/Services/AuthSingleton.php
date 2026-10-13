<?php

namespace App\Services;

class AuthSingleton
{
    private static ?int $currentChemistId = 1;

    public static function getId(): int
    {
        // Функция-singleton: возвращаем зафиксированную константу создателя
        return self::$currentChemistId;
    }
}
