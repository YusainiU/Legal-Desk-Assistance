<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('firm_name', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_settings');
    }
};
