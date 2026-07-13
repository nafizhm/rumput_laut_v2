<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements CHANGE type type ENUM('in','out','shrinkage','adjustment','transfer') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements CHANGE type type ENUM('in','out','shrinkage','adjustment') NOT NULL");
    }
};
