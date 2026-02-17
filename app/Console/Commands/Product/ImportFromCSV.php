<?php
namespace App\Console\Commands\Product;

use App\Console\Commands\BaseConsoleCommand;
use App\Models\External\Product\ProductDataCSV;

class ImportFromCSV extends BaseConsoleCommand
{
    protected $signature = 'product:import-from-csv {--file=} {--testMode=0}';

    protected $description = 'Import products from a CSV-file';

    public function handle(): void
    {
        $fileFullPath = $this->getObligatoryOption('file');
        $isInTestMode = $this->getOption('testMode', 'bool', false);

        try {
            $this->line('Processing ...');
            $startMicroTime = microtime(true);

            $importResults = ProductDataCSV::importFromFile($fileFullPath, $isInTestMode);

            $this->line(sprintf("... completed (%s sec, %d group(s))", round(microtime(true) - $startMicroTime, 3), $importResults->executionStatistics->numGroupsProcessed));

            // Showing results.

            $indent = '    ';

            $this->line("\n" . 'Items statistics:');

            $this->line($indent . sprintf("processed: %d", $importResults->itemStatistics->processed));
            $this->line($indent . sprintf("succeeded: %d", $importResults->itemStatistics->succeeded));
            $this->line($indent . sprintf("skipped: %d", $importResults->itemStatistics->skipped));

            $this->line("\n" . sprintf("Skipped items (%d total):", count($importResults->skippedItems)));

            if (count($importResults->skippedItems) === 0) {
                $this->line('No skipped items');
            } else {
                foreach ($importResults->skippedItems as $skippedItem) {
                    $this->line($indent . sprintf("Line %d", $skippedItem->lineIndex + 1) . ': ' . $skippedItem->originalLine);
                    foreach ($skippedItem->errors as $error) {
                        $this->line($indent . $indent . '-> ' . $error);
                    }
                }
            }
        }
        catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }

        $this->line("\n" . '... done');
    }
}