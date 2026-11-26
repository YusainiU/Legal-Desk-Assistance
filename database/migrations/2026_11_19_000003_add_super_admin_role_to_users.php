<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
        });

        $email = 'legaldesk@admin.test';
        $existing = DB::table('users')->where('email', $email)->first();

        if ($existing) {
            // Existing accounts require explicit elevation through the setup command.

            return;
        }

        DB::table('users')->insert([
            'name' => 'Legal Desk Super Admin',
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'has_access' => false,
            'is_admin' => false,
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
