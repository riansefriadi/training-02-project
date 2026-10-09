<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: aplikasi hanya INSERT.
        // sales_order_id tanpa FK agar jejak audit tetap ada setelah draft dihapus (BR-09, BR-11).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 40);
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sales_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
