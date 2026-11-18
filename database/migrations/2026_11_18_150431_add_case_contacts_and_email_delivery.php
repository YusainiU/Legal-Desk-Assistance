<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('organisation')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'email']);
        });

        Schema::create('contact_matter', function (Blueprint $table) {
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->primary(['matter_id', 'contact_id']);
        });

        Schema::table('matter_entries', function (Blueprint $table) {
            $table->uuid('email_token')->nullable()->unique();
            $table->string('email_status', 24)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('matter_entries', function (Blueprint $table) {
            $table->dropUnique(['email_token']);
            $table->dropColumn(['email_token', 'email_status']);
        });

        Schema::dropIfExists('contact_matter');
        Schema::dropIfExists('contacts');
    }
};
