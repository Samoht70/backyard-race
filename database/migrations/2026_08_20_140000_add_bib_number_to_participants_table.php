<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->unsignedSmallInteger('bib_number')->nullable()->after('status');

            $table->unique(['event_id', 'bib_number']);
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropUnique(['event_id', 'bib_number']);
            $table->dropColumn('bib_number');
        });
    }
};
