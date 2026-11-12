<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_id_counters', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_counters');
    }
};
