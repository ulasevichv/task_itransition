<?php
namespace App\Models\External\Product\ImportRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class CostAndStockMinRule extends BaseImportRule implements ValidationRule, DataAwareRule
{
    public const COST_MIN = 5;
    public const STOCK_MIN = 10;

    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cost = (float)$this->data['cost'];
        $stock = (int)$this->data['stock'];

        if ($cost < static::COST_MIN && $stock < static::STOCK_MIN) {
            $fail(sprintf("The `cost` must be >= %d and the `stock` must be >= %d.", static::COST_MIN, static::STOCK_MIN));
        }
    }
}