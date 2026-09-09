<?php

use App\Enums\LapStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained();
            $table->foreignId('round_id')->constrained();
            $table->string('status', 20)->default(LapStatus::Pending->value);
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'round_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laps');
    }
};
