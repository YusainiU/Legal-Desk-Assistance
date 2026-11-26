<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('permission_group_user', function (Blueprint $table) {
            $table->foreignId('permission_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_group_id', 'user_id']);
        });
        Schema::create('page_permissions', function (Blueprint $table) {
            $table->string('page')->primary();
            $table->boolean('restricted')->default(false);
        });
        Schema::create('page_permission_user', function (Blueprint $table) {
            $table->string('page');
            $table->foreign('page')->references('page')->on('page_permissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['page', 'user_id']);
        });
        Schema::create('page_permission_group', function (Blueprint $table) {
            $table->string('page');
            $table->foreign('page')->references('page')->on('page_permissions')->cascadeOnDelete();
            $table->foreignId('permission_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['page', 'permission_group_id']);
        });
    }
    public function down(): void
    {
        foreach (['page_permission_group', 'page_permission_user', 'page_permissions', 'permission_group_user', 'permission_groups'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
