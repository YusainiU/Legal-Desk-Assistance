<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_legal_research', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_research_id')->constrained('legal_research')->cascadeOnDelete();
            $table->string('question', 2000);
            $table->longText('analysis');
            $table->string('analysis_status', 80)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'legal_research_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_legal_research');
    }
};
