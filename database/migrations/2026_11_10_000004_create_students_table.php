<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('admission_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_phone');
            $table->string('photo_path')->nullable();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete()->restrictOnUpdate();
            $table->string('status')->default('active');
            $table->integer('student_year');
            $table->integer('student_sequence');
            $table->timestamps();

            $table->unique(['student_year', 'student_sequence']);
            $table->index(['class_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
