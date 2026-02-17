<?php
namespace App\Models\External\Product\ImportRules;

abstract class BaseImportRule
{
    public static function getErrorPostfix(): string
    {
        return "Import rule violation";
    }
}