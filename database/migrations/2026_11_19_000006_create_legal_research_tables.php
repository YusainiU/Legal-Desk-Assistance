<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title', 240);
            $table->string('court', 60);
            $table->date('decision_date')->nullable();
            $table->string('citation', 200)->nullable()->index();
            $table->string('jurisdiction', 40)->default('Malaysia');
            $table->text('source_url')->nullable();
            $table->string('source_type', 30)->default('uploaded');
            $table->string('source_checksum', 64)->nullable();
            $table->string('verification_status', 30)->default('unverified');
            $table->text('headnote')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['court', 'decision_date']);
        });

        Schema::create('legal_case_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->string('citation', 200);
            $table->string('reporter', 80)->nullable();
            $table->timestamps();
            $table->unique(['legal_case_id', 'citation']);
            $table->index('citation');
        });

        Schema::create('legal_case_passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('paragraph_label', 80)->nullable();
            $table->longText('content');
            $table->timestamps();
            $table->unique(['legal_case_id', 'sequence']);
        });

        Schema::create('legal_case_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citing_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('cited_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->string('treatment', 30);
            $table->text('supporting_passage')->nullable();
            $table->string('verification_status', 30)->default('unverified');
            $table->timestamps();
            $table->unique(['citing_case_id', 'cited_case_id', 'treatment'], 'legal_treatment_unique');
        });

        Schema::create('legal_research', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->nullable()->constrained('matters')->nullOnDelete();
            $table->text('question');
            $table->string('court_filter', 60)->default('all');
            $table->string('status', 30)->default('completed');
            $table->longText('analysis')->nullable();
            $table->string('analysis_status', 30)->default('not_generated');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('legal_research_authorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_research_id')->constrained('legal_research')->cascadeOnDelete();
            $table->foreignId('legal_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_case_passage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('rank')->default(0);
            $table->timestamps();
            $table->index(['legal_research_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_research_authorities');
        Schema::dropIfExists('legal_research');
        Schema::dropIfExists('legal_researches');
        Schema::dropIfExists('legal_case_treatments');
        Schema::dropIfExists('legal_case_passages');
        Schema::dropIfExists('legal_case_citations');
        Schema::dropIfExists('legal_cases');
    }
};
