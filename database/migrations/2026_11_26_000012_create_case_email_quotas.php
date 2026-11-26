<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_email_quotas', function (Blueprint $table) {
            $table->string('bucket', 100)->primary();
            $table->unsignedInteger('used')->default(0);
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_email_quotas');
    }
};
