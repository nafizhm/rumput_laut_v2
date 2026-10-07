<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->unsignedInteger('amount');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->unsignedInteger('paid_amount')->default(0)->after('notes');
            }
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid')->after('paid_amount');
            }
        });

        DB::table('invoices')->where('payment_status', 'paid')->chunkById(200, function ($invoices) {
            foreach ($invoices as $invoice) {
                $paidAmount = (int) round((float) $invoice->quantity_kg * (float) $invoice->price_per_kg);

                DB::table('invoice_payments')->insert([
                    'invoice_id' => $invoice->id,
                    'payment_date' => $invoice->updated_at ?? now(),
                    'amount' => $paidAmount,
                    'notes' => 'Pembayaran awal sebelum fitur cicilan invoice.',
                    'created_at' => $invoice->updated_at ?? now(),
                    'updated_at' => $invoice->updated_at ?? now(),
                ]);

                DB::table('invoices')->where('id', $invoice->id)->update([
                    'paid_amount' => $paidAmount,
                    'payment_status' => 'paid',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status']);
        });

        Schema::dropIfExists('invoice_payments');
    }
};
