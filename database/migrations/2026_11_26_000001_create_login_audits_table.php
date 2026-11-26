<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_audits', function (Blueprint $table) {
            $table->id();
            $table->string('username', 254);
            $table->string('state', 7);
            $table->string('ip_address', 45);
            $table->timestamp('attempted_at');
            $table->index(['username', 'state', 'ip_address']);
            $table->index('attempted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_audits');
    }
};
