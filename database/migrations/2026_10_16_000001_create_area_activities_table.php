<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_activities', function (Blueprint $table) {
            $table->id();
            $table->date('activity_date');
            $table->foreignId('warehouse_area_id')->constrained()->restrictOnDelete();
            $table->string('work_type');
            $table->unsignedInteger('sack_count');
            $table->string('pic');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_activities');
    }
};
