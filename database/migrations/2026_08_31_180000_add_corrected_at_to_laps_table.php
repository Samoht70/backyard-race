<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laps', function (Blueprint $table): void {
            $table->dateTime('corrected_at')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('laps', function (Blueprint $table): void {
            $table->dropColumn('corrected_at');
        });
    }
};
