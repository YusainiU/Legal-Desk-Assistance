<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firm_settings', function (Blueprint $table) {
            // NULL means indefinite retention; upgrading does not delete audit records.
            $table->unsignedSmallInteger('case_audit_retention_days')->nullable();
        });

        Schema::table('case_activity_audits', function (Blueprint $table) {
            $table->index(['matter_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('case_activity_audits', function (Blueprint $table) {
            $table->dropIndex(['matter_id', 'occurred_at']);
            $table->dropIndex(['actor_id', 'occurred_at']);
            $table->dropIndex(['action', 'occurred_at']);
        });

        Schema::table('firm_settings', function (Blueprint $table) {
            $table->dropColumn('case_audit_retention_days');
        });
    }
};
