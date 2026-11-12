<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->restrictOnDelete()->restrictOnUpdate();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->char('currency', 3);
            $table->string('status')->default('unpaid');
            $table->date('due_date')->nullable();
            $table->dateTime('billed_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'fee_structure_id']);
            $table->index('class_id');
            $table->index('fee_structure_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
