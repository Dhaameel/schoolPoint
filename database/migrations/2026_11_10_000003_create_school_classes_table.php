<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->foreignId('class_teacher_id')->nullable()->constrained('teachers')->restrictOnDelete()->restrictOnUpdate();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('class_teacher_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
