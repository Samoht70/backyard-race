<?php

use App\Enums\EventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The `singleton` column exists to make the second row impossible. The
     * whole application reads the event with sole() and firstOrFail(), so the
     * invariant was assumed everywhere and enforced nowhere: two concurrent
     * first saves would each have created one.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('singleton')->default(1)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('status', 20)->default(EventStatus::Draft->value);
            $table->dateTime('first_start_at')->nullable();
            $table->unsignedInteger('lap_distance_meters')->nullable();
            $table->unsignedSmallInteger('lap_duration_minutes')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
