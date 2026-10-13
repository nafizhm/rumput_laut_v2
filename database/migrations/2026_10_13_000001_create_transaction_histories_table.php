<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            // Keep the audit trail after the transaction or user is deleted.
            $table->unsignedBigInteger('transaction_id')->index();
            $table->string('action', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->text('reason')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
