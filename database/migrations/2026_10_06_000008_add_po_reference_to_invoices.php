<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('po_number', 100)->nullable();
            $table->string('delivery_note_number', 100)->nullable();
            $table->json('delivery_note_references')->nullable();
        });

        DB::table('invoices')->whereNotNull('delivery_note_id')->orderBy('id')->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                $note = DB::table('delivery_notes')->find($invoice->delivery_note_id);
                if (! $note) {
                    continue;
                }
                $po = DB::table('customer_purchase_orders')->find($note->customer_purchase_order_id);
                $notes = $po
                    ? DB::table('delivery_notes')->where('customer_purchase_order_id', $po->id)->orderBy('delivery_date')->orderBy('id')->get()
                    : collect([$note]);
                DB::table('invoices')->where('id', $invoice->id)->update([
                    'customer_purchase_order_id' => $po?->id,
                    'po_number' => $po?->po_number,
                    'delivery_note_number' => $note->number,
                    'delivery_note_references' => json_encode($notes->map(fn ($item) => [
                        'id' => $item->id, 'number' => $item->number,
                        'date' => \Illuminate\Support\Carbon::parse($item->delivery_date)->format('d/m/Y'),
                    ])->values()->all()),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_purchase_order_id');
            $table->dropColumn(['po_number', 'delivery_note_number', 'delivery_note_references']);
        });
    }
};
