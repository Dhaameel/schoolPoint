<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete()->restrictOnUpdate();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['class_id', 'name', 'currency']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
