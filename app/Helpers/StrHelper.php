<?php
namespace App\Helpers;

abstract class StrHelper
{
    public static function escapeQuotes(string $s): string
    {
        return preg_replace('/\"/', '\\"', $s);
    }

    /**
     * @throws \Exception
     */
    public static function escapeValueForDB(mixed $value): string
    {
        return match (gettype($value)) {
            'NULL' => 'NULL',
            'string' => '"' . static::escapeQuotes((string)$value) . '"',
            'integer', 'double' => $value,
            'boolean' => ($value ? '1' : '0'),
            default => throw new \Exception(sprintf("Invalid value type: %s", gettype($value))),
        };
    }
}