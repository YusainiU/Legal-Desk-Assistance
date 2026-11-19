<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('address_line_1', 160)->nullable();
            $table->string('address_line_2', 160)->nullable();
            $table->string('address_line_3', 160)->nullable();
            $table->string('town', 120)->nullable();
            $table->string('postcode', 32)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('country', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['address_line_1', 'address_line_2', 'address_line_3', 'town', 'postcode', 'region', 'country']);
        });
    }
};
