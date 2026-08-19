<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `starts_at` and `deadline_at` are derivable from the event, and stored
     * anyway: BR-11 has to find overdue rounds with a SQL predicate after a
     * queue outage, BR-08 copies both onto each lap, and the row is the record
     * of what actually arbitrated the race.
     */
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->unsignedSmallInteger('number');
            $table->dateTime('starts_at');
            $table->dateTime('deadline_at');
            $table->timestamps();

            $table->unique(['event_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
