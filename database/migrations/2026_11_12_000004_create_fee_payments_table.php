<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->constrained('fees')->restrictOnDelete()->restrictOnUpdate();
            $table->string('reference')->unique();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('type');
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('fee_payments')->restrictOnDelete()->restrictOnUpdate();
            $table->text('reason')->nullable();
            $table->dateTime('paid_at');
            $table->string('method')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();

            $table->index('fee_id');
            $table->index('paid_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
