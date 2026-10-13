<?php

use Database\Seeders\FinanceNavigationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new FinanceNavigationSeeder)->run();
    }

    public function down(): void
    {
        // Menu order has no schema or financial data to roll back.
    }
};
