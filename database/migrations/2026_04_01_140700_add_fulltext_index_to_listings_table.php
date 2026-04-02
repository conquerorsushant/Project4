<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add fulltext index on listings for search.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE listings ADD FULLTEXT fulltext_search (title, company_name, description, keywords)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE listings DROP INDEX fulltext_search');
    }
};
