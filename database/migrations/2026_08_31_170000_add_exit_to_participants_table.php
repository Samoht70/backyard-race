<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->string('exit_reason', 20)->nullable()->after('bib_number');
            $table->dateTime('exited_at')->nullable()->after('exit_reason');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn(['exit_reason', 'exited_at']);
        });
    }
};
