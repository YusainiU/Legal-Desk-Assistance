<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->string('title', 200);
            $table->string('citation', 200)->nullable();
            $table->string('jurisdiction', 120)->nullable();
            $table->text('source_url')->nullable();
            $table->text('applicability');
            $table->timestamps();
            $table->index(['matter_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_references');
    }
};
