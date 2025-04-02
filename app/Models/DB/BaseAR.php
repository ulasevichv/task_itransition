<?php
namespace App\Models\DB;

use App\Helpers\StrHelper;
use Illuminate\Contracts\Database\Query\Expression as QueryExpression;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;

abstract class BaseAR extends EloquentModel
{
    public const BULK_INSERT_GROUP_SIZE_DEFAULT = 500;

    protected static string $tn = '';

    public function __construct(array $attributes = [])
    {
        $this->table = static::$tn;

        parent::__construct($attributes);
    }

    /**
     * Short method to get database table name.
     *
     * @return string Table name.
     */
    public static function tn(): string
    {
        return static::$tn;
    }

    /**
     * @param string $tableName
     * @param string[] $fieldNames
     * @param array[] $values
     * @param int $groupSize
     * @return int
     * @throws \Exception
     */
    public static function bulkInsert(string $tableName, array $fieldNames, array $values, int $groupSize = self::BULK_INSERT_GROUP_SIZE_DEFAULT): int
    {
        if (count($values) == 0) {
            return 0;
        }

        $escapedFieldNames = array_map(function ($value) {
            return '`' . $value . '`';
        }, $fieldNames);

        $headerLine = sprintf("INSERT INTO `%s` (%s) VALUES", $tableName, implode(', ', $escapedFieldNames));

        $numFieldNames = count($fieldNames);
        $numValues = count($values);

        $numRecordsAddedTotal = 0;
        $numRecordsAddedToGroup = 0;

        $feed = [$headerLine];

        for ($i = 0; $i < $numValues; $i++) {
            $recordValues = $values[$i];
            if (count($recordValues) != $numFieldNames) {
                throw new \Exception(sprintf("Invalid number of fields in row %d", $i + 1));
            }

            $lineFeed = [];
            foreach ($recordValues as $fieldValue) {
                if (is_a($fieldValue, QueryExpression::class)) {
                    $lineFeed[] = $fieldValue->getValue(DB::getQueryGrammar());
                } else {
                    $lineFeed[] = StrHelper::escapeValueForDB($fieldValue);
                }
            }
            $feed[] = ($numRecordsAddedToGroup == 0 ? '    ' : '  , ') . '(' . implode(', ', $lineFeed) . ')';
            $numRecordsAddedToGroup++;
            $numRecordsAddedTotal++;

            if ($numRecordsAddedToGroup % $groupSize === 0 || $i === $numValues - 1) {
                $feed[] = ';';

                $rawSQL = implode("\n", $feed);

                DB::unprepared($rawSQL);

                $numRecordsAddedToGroup = 0;
                $feed = [$headerLine];
            }
        }

        return $numRecordsAddedTotal;
    }
}