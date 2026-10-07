<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            // All existing sales retain their original stock treatment.
            $table->string('stock_mode', 20)->default('immediate');
        });
        Schema::table('delivery_notes', function (Blueprint $table) {
            // Existing documents are not assumed to be drafts or shipped.
            $table->string('status', 20)->default('legacy')->index();
            $table->foreignId('sales_transaction_id')->nullable()->constrained('sales_transactions')->restrictOnDelete();
            $table->unsignedInteger('sack_count')->default(0);
            $table->date('shipped_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shipped_by_name')->nullable();
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('delivery_note_id')->nullable()->unique()->constrained('delivery_notes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_note_id');
        });
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_transaction_id');
            $table->dropConstrainedForeignId('shipped_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'sack_count', 'shipped_date', 'shipped_at', 'shipped_by_name']);
        });
        Schema::table('sales_transactions', fn (Blueprint $table) => $table->dropColumn('stock_mode'));
    }
};
