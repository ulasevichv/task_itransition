<?php
use Illuminate\Database\Migrations\Migration;
use \Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('ALTER TABLE `tblProductData` ADD `dcmCost` decimal(8, 2) NOT NULL AFTER `strProductCode`;');
        DB::unprepared('ALTER TABLE `tblProductData` ADD `intStock` mediumint unsigned NOT NULL AFTER `dcmCost`;');
        DB::unprepared('ALTER TABLE `tblProductData` CHANGE `dtmAdded` `dtmAdded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;');
    }

    public function down(): void
    {
        DB::unprepared('ALTER TABLE `tblProductData` DROP COLUMN `dcmCost`;');
        DB::unprepared('ALTER TABLE `tblProductData` DROP COLUMN `intStock`;');
        DB::unprepared('ALTER TABLE `tblProductData` CHANGE `dtmAdded` `dtmAdded` datetime DEFAULT NULL;');
    }
};