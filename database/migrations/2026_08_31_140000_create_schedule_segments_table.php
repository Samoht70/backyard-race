<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->unsignedSmallInteger('from_round_number');
            $table->unsignedSmallInteger('lap_duration_minutes');
            $table->timestamps();

            $table->unique(['event_id', 'from_round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_segments');
    }
};
