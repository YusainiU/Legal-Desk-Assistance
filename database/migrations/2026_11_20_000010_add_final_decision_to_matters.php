<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->text('final_decision')->nullable();
            $table->foreignId('final_decision_reference_id')->nullable()
                ->constrained('case_references')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('final_decision_reference_id');
            $table->dropColumn('final_decision');
        });
    }
};
