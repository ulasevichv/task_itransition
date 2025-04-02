<?php
namespace App\Models\DB\Product\DTO;

use App\Models\DB\BaseDTO;

final readonly class ProductDataDTO extends BaseDTO
{
    private function __construct(
        public string $code,
        public string $name,
        public string $description,
        public float  $cost,
        public int    $stock,
        public bool   $discontinued,
    ) {}

    /**
     * @param array<string,string> $data
     * @return self
     */
    public static function fromValidatedCSVLineArray(array $data): self
    {
        return new self(
            code: $data['productCode'],
            name: $data['productName'],
            description: $data['productDescription'],
            cost: (float)$data['cost'],
            stock: (int)$data['stock'],
            discontinued: (strtolower($data['discontinued']) === 'yes')
        );
    }
}