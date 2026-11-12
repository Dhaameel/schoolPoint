<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();

            $table->unique(['class_id', 'subject_id']);
            $table->index('teacher_id');
            $table->index('subject_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_subject');
    }
};
