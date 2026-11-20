<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('legal_research')) {
            if (Schema::hasTable('legal_researches')) {
                throw new RuntimeException('Both legal_research and legal_researches exist. Resolve duplicate tables manually before migrating.');
            }

            return;
        }

        if (! Schema::hasTable('legal_researches')) {
            throw new RuntimeException('Neither legal_research nor legal_researches exists. Apply the initial legal research migration first.');
        }

        Schema::rename('legal_researches', 'legal_research');
    }

    public function down(): void
    {
        // Existing installs that were renamed by this migration can be reverted
        // explicitly; avoid reversing a table created with the correct name.
    }
};
