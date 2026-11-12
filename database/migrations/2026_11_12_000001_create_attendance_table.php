<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete()->restrictOnUpdate();
            $table->date('date');
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('marked_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index('date');
            $table->index(['class_id', 'date']);
            $table->index('marked_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
