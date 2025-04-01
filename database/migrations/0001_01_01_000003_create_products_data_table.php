<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use \Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblProductData', function (Blueprint $table) {
            $table->unsignedInteger('intProductDataId')->autoIncrement();
            $table->string('strProductName', 50)->nullable(false);
            $table->string('strProductDesc', 255)->nullable(false);
            $table->string('strProductCode', 10)->nullable(false);
            $table->dateTime('dtmAdded')->nullable();
            $table->dateTime('dtmDiscontinued')->nullable();
            $table->timestamp('stmTimestamp')->nullable(false)->default(DB::raw('CURRENT_TIMESTAMP'))->useCurrentOnUpdate();

            $table->primary('intProductDataId');
            $table->unique('strProductCode', 'strProductCode');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblProductData');
    }
};