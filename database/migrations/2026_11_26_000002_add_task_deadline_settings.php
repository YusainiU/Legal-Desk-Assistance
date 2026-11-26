<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matter_entries', function (Blueprint $table) {
            $table->dateTime('due_at')->nullable();
        });
        Schema::table('firm_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('task_warning_hours')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('matter_entries', fn (Blueprint $table) => $table->dropColumn('due_at'));
        Schema::table('firm_settings', fn (Blueprint $table) => $table->dropColumn('task_warning_hours'));
    }
};
