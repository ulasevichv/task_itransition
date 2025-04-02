<?php
namespace App\Models\External\Product;

use App\Helpers\CSVHelper;
use App\Models\DB\Product\DTO\ProductDataDTO;
use App\Models\DB\Product\ProductData;
use App\Models\External\Product\ImportRules\BaseImportRule;
use App\Models\External\Product\ImportRules\CostAndStockMinRule;
use App\Models\External\Product\ImportRules\CostMaxRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductDataCSV
{
    public const IMPORT_GROUP_SIZE_DEFAULT = 500;

    /**
     * @return object[]
     */
    private static function getExpectedFields(): array
    {
        return [
            (object)[
                'name' => 'productCode',
                'validationRules' => ['required', 'string', 'max:' . ProductData::PRODUCT_CODE_MAX_LENGTH, 'regex:' . ProductData::PRODUCT_CODE_REGEX],
                'importRules' => [],
            ],
            (object)[
                'name' => 'productName',
                'validationRules' => ['required', 'string', 'max:' . ProductData::PRODUCT_NAME_LENGTH_MAX],
                'importRules' => [],
            ],
            (object)[
                'name' => 'productDescription',
                'validationRules' => ['required', 'string', 'max:' . ProductData::PRODUCT_DESCRIPTION_LENGTH_MAX],
                'importRules' => [],
            ],
            (object)[
                'name' => 'stock',
                'validationRules' => ['required', 'int', 'min:0', 'max:' . ProductData::STOCK_QUANTITY_MAX],
                'importRules' => [],
            ],
            (object)[
                'name' => 'cost',
                'validationRules' => ['required', 'numeric', 'min:' . ProductData::COST_MIN, 'max:' . ProductData::COST_MAX],
                'importRules' => [
                    new CostMaxRule(),
                    new CostAndStockMinRule(),
                ],
            ],
            (object)[
                'name' => 'discontinued',
                'validationRules' => ['string', 'regex:' . '/^(yes|no)$/i'],
                'importRules' => [],
            ],
        ];
    }

    /**
     * @param object[] $expectedFields
     * @return array<string,array>
     */
    private static function getValidationRules(array $expectedFields): array
    {
        $results = [];
        foreach ($expectedFields as $expectedField) {
            $results[$expectedField->name] = $expectedField->validationRules;
        }
        return $results;
    }

    /**
     * @param object[] $expectedFields
     * @return array<string,array>
     */
    private static function getImportRules(array $expectedFields): array
    {
        $results = [];
        foreach ($expectedFields as $expectedField) {
            $results[$expectedField->name] = $expectedField->importRules;
        }
        return $results;
    }

    /**
     * @param object[] $expectedFields
     * @return array<string,string>
     */
    private static function getFormattedAttributeNamesForValidator(array $expectedFields): array
    {
        $results = [];
        foreach ($expectedFields as $expectedField) {
            $results[$expectedField->name] = sprintf("`%s`", $expectedField->name);
        }
        return $results;
    }

    /**
     * @param string[] $parsedLineFields
     * @param object[] $expectedFields
     * @return array<string,string>
     */
    private static function createLineDataArr(array $parsedLineFields, array $expectedFields): array
    {
        $result = [];
        foreach ($expectedFields as $index => $expectedField) {
            $result[$expectedField->name] = $parsedLineFields[$index];
        }
        return $result;
    }

    /**
     * @return string[]
     */
    private static function getLineValidationErrors(ValidationException $ex, string $postfix = ''): array
    {
        $errors = [];

        $allValidationErrors = $ex->errors();
        foreach ($allValidationErrors as $parameterErrors) {
            foreach ($parameterErrors as $distinctRuleError) {
                $errors[] = $distinctRuleError;
            }
        }

        if (!empty($postfix)) {
            foreach ($errors as &$error) {
                $error = $error . ' ' . $postfix;
            }
        }

        return $errors;
    }

    private static function createSkippedItemInfo(int $lineIndex, array $errors): object
    {
        return (object)[
            'lineIndex' => $lineIndex,
            'originalLine' => null,
            'errors' => $errors,
        ];
    }

    /**
     * @param object[] $groupData
     * @throws \Exception
     */
    private static function processGroupDTOs(array $groupData, array &$skippedItems, bool $isInTestMode = false): int
    {
        $productCodes = array_column(array_column($groupData, 'dto'), 'code');

        $existingRows = ProductData::getByCodes($productCodes);

        if (count($existingRows) != 0) {
            foreach ($existingRows as $row) {
                for ($i = 0; $i < count($groupData); $i++) {
                    $obj = $groupData[$i];
                    if ($row->strProductCode === $obj->dto->code) {
                        $skippedItems[] = static::createSkippedItemInfo($obj->lineIndex, [sprintf("The `productCode` %s is already registered.", $obj->dto->code)]);
                        array_splice($groupData, $i, 1);
                        break;
                    }
                }
            }
        }

        if (count($groupData) != 0 && !$isInTestMode) {
            ProductData::insertDTOs(array_column($groupData, 'dto'));
        }

        return count($groupData);
    }

    /**
     * @throws \Exception
     */
    public static function importFromFile(string $fileFullPath, bool $isInTestMode = false, int $groupSize = self::IMPORT_GROUP_SIZE_DEFAULT): object
    {
        $parsedLines = CSVHelper::parseFile($fileFullPath);

        $expectedFields = static::getExpectedFields();
        $formattedAttributeNames = static::getFormattedAttributeNamesForValidator($expectedFields);

        $numItemsProcessed = 0;
        $numItemsSucceeded = 0;
        $skippedItems = [];

        $uniqueProductCodes = [];

        $numGroupsProcessed = 0;
        $groupData = [];

        for ($lineIndex = 0; $lineIndex < count($parsedLines); $lineIndex++) {
            $parsedLineFields = $parsedLines[$lineIndex];
            if (!isset($parsedLineFields)) {
                continue;
            }

            $numItemsProcessed++;

            // Validating number of fields in line and creating line data-array.

            if (count($parsedLineFields) !== count($expectedFields)) {
                $skippedItems[] = static::createSkippedItemInfo($lineIndex, [sprintf("Invalid number of fields: %d. Exactly %d fields are required.",
                    count($parsedLineFields), count($expectedFields))]);
                continue;
            }

            $lineDataArr = static::createLineDataArr($parsedLineFields, $expectedFields);

            // Validating line for general format-related errors.

            $validationRules = static::getValidationRules($expectedFields);

            try {
                Validator::make($lineDataArr, $validationRules, [], $formattedAttributeNames)->validate();
            }
            catch (ValidationException $ex) {
                $skippedItems[] = static::createSkippedItemInfo($lineIndex, static::getLineValidationErrors($ex));
                continue;
            }

            // Validating line for duplicate product codes.

            if (in_array($lineDataArr['productCode'], $uniqueProductCodes)) {
                $skippedItems[] = static::createSkippedItemInfo($lineIndex, [sprintf("Duplicate `productCode`: %s", $lineDataArr['productCode'])]);
                continue;
            } else {
                $uniqueProductCodes[] = $lineDataArr['productCode'];
            }

            // Validating line for custom business-logic errors.

            $importRules = static::getImportRules($expectedFields);

            try {
                Validator::make($lineDataArr, $importRules, [], $formattedAttributeNames)->validate();
            }
            catch (ValidationException $ex) {
                $skippedItems[] = static::createSkippedItemInfo($lineIndex, static::getLineValidationErrors($ex, '(' . BaseImportRule::getErrorPostfix() . ').'));
                continue;
            }

            // Generating DTO and adding it to group data.

            $dto = ProductDataDTO::fromValidatedCSVLineArray($lineDataArr);

            $groupData[] = (object)[
                'lineIndex' => $lineIndex,
                'dto' => $dto,
            ];

            // Adding info to database.

            if (count($groupData) === $groupSize) {
                $numItemsSucceeded += static::processGroupDTOs($groupData, $skippedItems, $isInTestMode);
                $numGroupsProcessed++;
                $groupData = [];
            }
        }

        if (count($groupData) != 0) {
            $numItemsSucceeded += static::processGroupDTOs($groupData, $skippedItems, $isInTestMode);
            $numGroupsProcessed++;
        }

        // Post-formatting skipped items info.

        foreach ($skippedItems as $skippedItem) {
            $skippedItem->originalLine = implode(',', $parsedLines[$skippedItem->lineIndex]);
        }

        usort($skippedItems, function ($a, $b) {
            return $a->lineIndex > $b->lineIndex;
        });

        return (object)[
            'itemStatistics' => (object)[
                'processed' => $numItemsProcessed,
                'succeeded' => $numItemsSucceeded,
                'skipped' => $numItemsProcessed - $numItemsSucceeded,
            ],
            'executionStatistics' => (object)[
                'numGroupsProcessed' => $numGroupsProcessed,
            ],
            'skippedItems' => $skippedItems,
        ];
    }
}