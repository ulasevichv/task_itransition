<?php
namespace App\Models\External\Product\ImportRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CostMaxRule extends BaseImportRule implements ValidationRule
{
    public const COST_MAX = 1000;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cost = (float)$value;

        if ($cost > static::COST_MAX) {
            $fail(sprintf("The `{$attribute}` must not be greater than %d.", static::COST_MAX));
        }
    }
}