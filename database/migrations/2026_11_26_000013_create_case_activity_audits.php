<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_activity_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->unsignedBigInteger('matter_id')->nullable()->index();
            $table->string('record_type', 80);
            $table->unsignedBigInteger('record_id');
            $table->string('action', 40);
            $table->json('changed_fields');
            $table->timestamp('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_activity_audits');
    }
};
