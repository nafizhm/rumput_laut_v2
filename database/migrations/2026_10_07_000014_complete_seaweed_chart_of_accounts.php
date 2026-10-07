<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\ChartOfAccountSeeder)->run();
    }

    public function down(): void
    {
        // Preserve master accounts that may already be referenced by journals.
    }
};
