<?php
namespace App\Models\DB\Product;

use App\Models\DB\Product\DTO\ProductDataDTO;
use Illuminate\Support\Facades\DB;

/**
 * @property int $intProductDataId
 * @property string $strProductName
 * @property string $strProductDesc
 * @property string $strProductCode
 * @property float $dcmCost
 * @property int $intStock
 * @property ?string $dtmAdded
 * @property ?string $dtmDiscontinued
 * @property string $stmTimestamp
 */
class ProductData extends \App\Models\DB\BaseAR
{
    protected static string $tn = 'tblProductData';

    public const PRODUCT_NAME_LENGTH_MAX = 50;

    public const PRODUCT_DESCRIPTION_LENGTH_MAX = 255;

    public const PRODUCT_CODE_MAX_LENGTH = 10;
    public const PRODUCT_CODE_REGEX = '/^P[0-9]{1,9}$/';

    /* MySQL unsigned medium int maximum value. */
    public const STOCK_QUANTITY_MAX = 16777215;

    public const COST_MIN = 0.01;
    public const COST_MAX = 999999.99;

    /**
     * @param string[] $productCodes
     * @return object[]
     */
    public static function getByCodes(array $productCodes): array
    {
        $query = DB::query()
            ->select(['*'])
            ->from(static::tn())
            ->whereIn('strProductCode', $productCodes)
            ->orderBy('intProductDataId');
        return $query->get()->toArray();
    }

    /**
     * @param ProductDataDTO[] $dtos
     * @return int
     * @throws \Exception
     */
    public static function insertDTOs(array $dtos): int
    {
        $insertValues = array_map(function ($dto) {
            return $dto->toArray(['code', 'name', 'description', 'cost', 'stock', function (ProductDataDTO $dto) {
                return ($dto->discontinued ? DB::raw('CURRENT_TIMESTAMP') : null);
            }]);
        }, $dtos);

        return static::bulkInsert(static::tn(), [
            'strProductCode', 'strProductName', 'strProductDesc', 'dcmCost', 'intStock', 'dtmDiscontinued'
        ], $insertValues);
    }
}