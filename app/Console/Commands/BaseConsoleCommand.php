<?php
namespace App\Console\Commands;

abstract class BaseConsoleCommand extends \Illuminate\Console\Command
{
    /**
     * @throws \Exception
     */
    protected function getObligatoryOption(string $optionName, string $typeName = 'string'): string|int|bool|array
    {
        $value = (string)$this->option($optionName) ?? null;
        if (!isset($value) || strlen($value) === 0) {
            $this->error(sprintf("Error: --%s option is required", $optionName));
            exit();
        }
        return static::castToType($value, $typeName);
    }

    /**
     * @throws \Exception
     */
    protected function getOption(string $optionName, string $typeName = 'string', mixed $defaultValue = null): string|int|bool|array|null
    {
        $value = (string)$this->option($optionName) ?? null;
        if (!isset($value) || strlen($value) === 0) {
            return $defaultValue;
        }
        return static::castToType($value, $typeName);
    }

    /**
     * @throws \Exception
     */
    protected static function castToType(string $value, string $typeName): string|int|bool|array
    {
        return match ($typeName) {
            'string' => $value,
            'int', 'integer' => (int)$value,
            'bool', 'boolean' => !(empty($value) || strtolower($value) === 'false'),
            'array' => array_map(function ($v) {
                return trim($v);
            }, explode(',', $value)),
            default => throw new \Exception(sprintf("Invalid type: %s", $typeName)),
        };
    }
}