<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id('ID_Staff');
            $table->string('Staff_Name');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ID_Study_Program');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ID_Study_Program')->references('ID_study_program')->on('study_programs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};