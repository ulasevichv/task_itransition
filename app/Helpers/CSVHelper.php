<?php
namespace App\Helpers;

abstract class CSVHelper
{
    /**
     * NOTE: empty lines (and, optionally, header line) will be converted to null in order to keep correct line numbers for further processing.
     *
     * @return array<array<int, string>>
     * @throws \Exception
     */
    public static function parseFile(string $fileFullPath, bool $skipFirstLine = true): array
    {
        $handle = @fopen($fileFullPath, 'r');
        if ($handle === false) {
            throw new \Exception(sprintf("Cannot read file: %s", $fileFullPath));
        }

        try {
            $parsedLines = [];
            $firstLineProcessed = false;

            while (!feof($handle)) {
                $originalLine = fgets($handle);

                if (!$firstLineProcessed) {
                    $firstLineProcessed = true;
                    if ($skipFirstLine) {
                        $parsedLines[] = null;
                        continue;
                    } else {
                        // Removing UTF-8 byte order mark, if it presents.
                        $originalLine = static::removeUTF8BOM($originalLine);
                    }
                }

                if (empty($originalLine) || $originalLine === "\n" || $originalLine === "\r\n") {
                    $parsedLines[] = null;
                    continue;
                }

                $parsedLines[] = str_getcsv($originalLine);
            }

            return $parsedLines;
        }
        finally {
            fclose($handle);
        }
    }

    /**
     * Remove UTF-8 byte order mark, if it presents.
     */
    public static function removeUTF8BOM(string $str): string
    {
        $bom = pack('H*', 'EFBBBF');
        return preg_replace("/^$bom/", '', $str);
    }
}