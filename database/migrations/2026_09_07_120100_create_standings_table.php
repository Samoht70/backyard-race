<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('participant_id')->constrained();
            $table->unsignedSmallInteger('rank');
            $table->unsignedSmallInteger('bib_number')->nullable();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->unsignedSmallInteger('validated_laps');
            $table->string('exit_reason', 20)->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'participant_id']);
            $table->index(['event_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
