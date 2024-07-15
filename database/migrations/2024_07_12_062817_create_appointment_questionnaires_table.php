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
        Schema::create('appointment_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('appointment_id')->unique();
            $table->json('questionnaire');  // lex and artisan response in json format        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_questionnaires');
    }
};
