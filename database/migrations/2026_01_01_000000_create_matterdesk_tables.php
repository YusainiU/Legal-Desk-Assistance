<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('matters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->string('client');
            $table->string('party')->nullable();
            $table->string('owner');
            $table->string('practice');
            $table->unsignedTinyInteger('stage')->default(0);
            $table->string('status')->default('Open');
            $table->string('conflict')->default('Pending');
            $table->json('checks')->nullable();
            $table->timestamps();
        });

        Schema::create('matter_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->text('body');
            $table->string('assignee')->nullable();
            $table->date('due_on')->nullable();
            $table->boolean('done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matter_entries');
        Schema::dropIfExists('matters');
        Schema::dropIfExists('users');
    }
};
