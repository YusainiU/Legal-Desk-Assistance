<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->char('billing_currency', 3)->nullable();
            $table->unsignedBigInteger('final_charge_minutes')->nullable();
            $table->unsignedBigInteger('final_charge_minor')->nullable();
        });

        Schema::create('case_time_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('stage');
            $table->string('description');
            $table->unsignedSmallInteger('minutes');
            $table->unsignedInteger('hourly_rate_minor');
            $table->unsignedBigInteger('amount_minor');
            $table->timestamps();
            $table->index(['matter_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_time_charges');

        Schema::table('matters', function (Blueprint $table) {
            $table->dropColumn(['billing_currency', 'final_charge_minutes', 'final_charge_minor']);
        });
    }
};
